<?php

/**
 * The Update class updates phpMyFAQ. Classy.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Administration\AdminLogRepository;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Filesystem\Filesystem;
use phpMyFAQ\Forms;
use phpMyFAQ\Setup\Migration\MigrationExecutor;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\MigrationRegistry;
use phpMyFAQ\Setup\Migration\MigrationResult;
use phpMyFAQ\Setup\Migration\MigrationTracker;
use phpMyFAQ\System;
use Random\RandomException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use ZipArchive;

class Update extends AbstractSetup
{
    public string $version {
        set {
            $this->version = $value;
        }
    }

    /** @var string[] Legacy queries array for backward compatibility */
    private array $queries = [];

    public bool $dryRun = false {
        set {
            $this->dryRun = $value;
        }
    }

    /** @var string[] Legacy dry-run queries for backward compatibility */
    public array $dryRunQueries = [] {
        get {
            return $this->dryRunQueries;
        }
    }

    /** @var MigrationResult[] */
    public array $migrationResults = [] {
        get {
            return $this->migrationResults;
        }
    }

    private ?string $backupFilename = null;

    private MigrationRegistry $migrationRegistry {
        get {
            return $this->migrationRegistry;
        }
    }
    private MigrationTracker $migrationTracker {
        get {
            return $this->migrationTracker;
        }
    }
    private MigrationExecutor $migrationExecutor;

    /**
     * Create an Update instance and initialize migration components.
     *
     * Initializes the Update setup with the provided System and Configuration objects
     * and constructs the MigrationRegistry, MigrationTracker, and MigrationExecutor
     * used during the update process.
     *
     * @param System $system Application system utilities (environment and version access).
     * @param Configuration $configuration Configuration and database access used by migrations.
     */
    public function __construct(
        protected System $system,
        private readonly Configuration $configuration,
    ) {
        parent::__construct($this->system);

        $this->migrationRegistry = new MigrationRegistry($this->configuration);
        $this->migrationTracker = new MigrationTracker($this->configuration);
        $this->migrationExecutor = new MigrationExecutor(
            $this->configuration,
            $this->migrationTracker,
            new Filesystem(PMF_ROOT_DIR),
        );
    }

    /**
     * Checks if the "faqconfig" table is available
     */
    public function isConfigTableNotAvailable(DatabaseDriver $databaseDriver): bool
    {
        $query = sprintf('SELECT * FROM %s%s', Database::getTablePrefix(), 'faqconfig');
        $result = $databaseDriver->query($query);
        return $databaseDriver->numRows($result) === 0;
    }

    /**
     * Creates a backup of the current config files
     * @throws Exception
     * @throws RandomException
     */
    public function createConfigBackup(string $configDir): string
    {
        $outputZipFile = $configDir . DIRECTORY_SEPARATOR . $this->getBackupFilename();

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($outputZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Cannot create config backup file.');
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($configDir),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo) {
                $filePath = $file->getRealPath() ?: $file->getPathname();
                $isDir = $file->isDir();
                $isFile = $file->isFile();
            } else {
                $filePath = is_string($file) ? $file : (string) $file;
                $filePath = realpath($filePath) ?: $filePath;
                $isDir = is_dir($filePath);
                $isFile = is_file($filePath);
            }
            if ($filePath === false) {
                continue;
            }
            if ($filePath === null) {
                continue;
            }
            if ($filePath === '') {
                continue;
            }

            // Exclude the zip we are currently writing
            if ($filePath === $outputZipFile) {
                continue;
            }

            // Only include entries inside the config directory
            if (!str_contains($filePath, $configDir . DIRECTORY_SEPARATOR) && $filePath !== $configDir) {
                continue;
            }

            // Compute a relative path inside the archive
            $relativePath = str_replace($configDir . DIRECTORY_SEPARATOR, '', $filePath);
            $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);

            if ($isDir) {
                // Ensure directory entries end with a slash
                $zipArchive->addEmptyDir(rtrim($relativePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
            } elseif ($isFile) {
                $zipArchive->addFile($filePath, $relativePath);
            }
        }

        $zipArchive->close();

        if (!file_exists($outputZipFile)) {
            throw new Exception('Cannot store config backup file.');
        }

        return $this->configuration->getDefaultUrl() . 'content/core/config/' . $this->getBackupFilename();
    }

    /**
     * @throws Exception
     */
    public function checkInitialRewriteBasePath(Request $request): bool
    {
        $basePath = $request->getBasePath();
        $basePath = rtrim($basePath, 'update');

        $htaccessPath = PMF_ROOT_DIR . '/.htaccess';

        $htaccessUpdater = new HtaccessUpdater();
        return $htaccessUpdater->updateRewriteBase($htaccessPath, $basePath);
    }

    /**
     * Run pending migrations for the configured version and perform related post-migration work.
     *
     * When dry-run mode is enabled, migrations are not applied and SQL statements from pending
     * migrations and legacy queries are collected instead of executed. When not in dry-run mode,
     * migration tracking is ensured, post-migration tasks are run, tables are optimized, legacy
     * queries are executed, and the stored version values are updated.
     *
     * @return bool `true` if all migrations succeeded, `false` otherwise.
     * @throws Exception If migration execution or any post-migration step fails.
     */
    public function applyUpdates(): bool
    {
        // Ensure the migration tracking table exists (only when not in dry-run mode)
        if (!$this->dryRun) {
            $this->migrationTracker->ensureTableExists();
        }

        // Get pending migrations based on a version
        $pendingMigrations = $this->migrationRegistry->getPendingMigrations($this->version);

        // Set dry-run mode
        $this->migrationExecutor->setDryRun($this->dryRun);

        // Execute migrations
        $this->migrationResults = $this->migrationExecutor->executeMigrations($pendingMigrations);

        // If dry-run, collect all SQL queries for backward compatibility
        if ($this->dryRun) {
            $this->collectDryRunQueries($pendingMigrations);
        } else {
            // Special handling for migrations that require immediate execution
            // (like admin log hash migration in 4.2.0-alpha)
            $this->runPostMigrationTasks();

            // Optimize the tables
            $this->optimizeTables();

            // Execute legacy queries (if any)
            $this->executeQueries();
        }

        // Always the last step: Update version number
        if (!$this->dryRun) {
            $this->updateVersion();
        }

        return $this->allMigrationsSucceeded();
    }

    /**
     * Determine whether every recorded migration result indicates success.
     *
     * @return bool `true` if all migrations succeeded, `false` otherwise.
     */
    private function allMigrationsSucceeded(): bool
    {
        foreach ($this->migrationResults as $result) {
            if (!$result->isSuccess()) {
                return false;
            }
        }
        return true;
    }

    /**
         * Collect SQL statements produced by the given migrations and append them to the object's dry-run query list.
         *
         * @param array<string, MigrationInterface> $migrations Migrations to inspect for SQL operations.
         */
    private function collectDryRunQueries(array $migrations): void
    {
        $report = $this->migrationExecutor->generateDryRunReport($migrations);

        foreach ($report['migrations'] as $migrationData) {
            foreach ($migrationData['operations'] as $operation) {
                if ($operation['type'] === 'sql') {
                    $this->dryRunQueries[] = $operation['query'];
                }
            }
        }
    }

    /**
     * Perform post-migration maintenance steps that are not implemented as migrations.
     *
     * Executes version-gated tasks:
     * - Migrates admin log entry hashes when updating from a version earlier than 4.2.0-alpha.
     * - Inserts form input definitions when updating from a version earlier than 4.0.0-alpha.2.
     */
    private function runPostMigrationTasks(): void
    {
        // Handle admin log hash migration for 4.2.0-alpha
        if (version_compare($this->version, '4.2.0-alpha', '<')) {
            $this->migrateAdminLogHashes();
        }

        // Insert form inputs for 4.0.0-alpha.2
        if (version_compare($this->version, '4.0.0-alpha.2', '<')) {
            $this->insertFormInputs();
        }
    }

    /**
     * Generate INSERT queries for installer-defined form inputs and append them to the internal query queue.
     *
     * If generation fails (for example because the inputs already exist), any thrown exception is caught and ignored.
     */
    private function insertFormInputs(): void
    {
        try {
            $forms = new Forms($this->configuration);
            $installer = new Installer(new System());
            foreach ($installer->formInputs as $input) {
                $this->queries[] = $forms->getInsertQueries($input);
            }
        } catch (\Exception) {
            // Form inputs may already exist
        }
    }

    /**
     * Prepare appropriate database maintenance queries for the current DB type and append them to the internal query queue.
     *
     * For MySQL (`mysqli`) this adds an `OPTIMIZE TABLE <table>` statement for each table in the configured database.
     * For PostgreSQL (`pgsql`) this adds a `VACUUM ANALYZE;` statement.
     */
    public function optimizeTables(): void
    {
        switch (Database::getType()) {
            case 'mysqli':
                $this->configuration->getDb()->getTableNames(Database::getTablePrefix());
                foreach ($this->configuration->getDb()->tableNames as $tableName) {
                    $this->queries[] = 'OPTIMIZE TABLE ' . $tableName;
                }

                break;
            case 'pgsql':
                $this->queries[] = 'VACUUM ANALYZE;';
                break;
        }
    }

    /**
     * Generate a detailed dry-run report for pending migrations against the current target version.
     *
     * @return array<string, mixed> Associative report keyed by migration identifiers containing operation entries (including SQL and non-SQL operation types) and their metadata.
     */
    public function getDryRunResults(): array
    {
        $pendingMigrations = $this->migrationRegistry->getPendingMigrations($this->version);
        return $this->migrationExecutor->generateDryRunReport($pendingMigrations);
    }

    /**
     * Produce a human-readable formatted dry-run report for migrations pending for the current target version.
     *
     * @return string The formatted dry-run report.
     */
    public function getFormattedDryRunReport(): string
    {
        $pendingMigrations = $this->migrationRegistry->getPendingMigrations($this->version);
        $report = $this->migrationExecutor->generateDryRunReport($pendingMigrations);
        return $this->migrationExecutor->formatDryRunReport($report);
    }

    /**
     * Execute collected SQL queries or record them for a dry run.
     *
     * When dry-run mode is enabled, appends each query to the dry-run query list.
     * When not in dry-run mode, executes each query against the configured database.
     *
     * @throws Exception If executing any query fails; the thrown exception contains the original error message.
     */
    private function executeQueries(): void
    {
        if ($this->dryRun) {
            foreach ($this->queries as $query) {
                $this->dryRunQueries[] = $query;
            }
        } else {
            foreach ($this->queries as $query) {
                try {
                    $this->configuration->getDb()->query($query);
                } catch (Exception $exception) {
                    throw new Exception($exception->getMessage());
                }
            }
        }
    }

    private function updateVersion(): void
    {
        $this->configuration->update(['main.currentApiVersion' => System::getApiVersion()]);
        $this->configuration->update(['main.currentVersion' => System::getVersion()]);
    }

    /**
     * @throws RandomException
     */
    private function getBackupFilename(): string
    {
        if ($this->backupFilename === null) {
            $randomHash = bin2hex(random_bytes(4)); // 8-character hex string
            $this->backupFilename = sprintf('phpmyfaq-config-backup.%s.%s.zip', date(format: 'Y-m-d'), $randomHash);
        }

        return $this->backupFilename;
    }

    private function migrateAdminLogHashes(): void
    {
        if (version_compare($this->version, '4.2.0-alpha', '<')) {
            $repository = new AdminLogRepository($this->configuration);

            try {
                $entries = $repository->getAll();
                $previousHash = null;

                foreach ($entries as $entity) {
                    if ($entity->getHash() === null) {
                        $entity->setPreviousHash($previousHash);
                        $hash = $entity->calculateHash();

                        // Execute UPDATE directly instead of adding to the queries array
                        $updateQuery = sprintf(
                            "UPDATE %sfaqadminlog SET hash = '%s', previous_hash = %s WHERE id = %d",
                            Database::getTablePrefix(),
                            $this->configuration->getDb()->escape($hash),
                            $previousHash !== null
                                ? "'" . $this->configuration->getDb()->escape($previousHash) . "'"
                                : 'NULL',
                            $entity->getId(),
                        );

                        $this->configuration->getDb()->query($updateQuery);

                        $previousHash = $hash;
                    }
                }
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Admin log hash migration failed: ' . $e->getMessage());
            }
        }
    }
}