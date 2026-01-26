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
    public array $dryRunQueries = [];

    /** @var MigrationResult[] */
    public array $migrationResults = [];

    private ?string $backupFilename = null;

    private MigrationRegistry $migrationRegistry;

    private MigrationTracker $migrationTracker;

    private MigrationExecutor $migrationExecutor;

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
     * @throws Exception
     * @throws \Exception
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
        $allSucceeded = $this->allMigrationsSucceeded();

        // If dry-run, collect all SQL queries for backward compatibility
        if ($this->dryRun) {
            $this->collectDryRunQueries($pendingMigrations);
            return $allSucceeded;
        }

        if (!$allSucceeded) {
            return false;
        }

        // Special handling for migrations that require immediate execution
        $this->runPostMigrationTasks();
        $this->optimizeTables();
        $this->executeQueries();
        $this->updateVersion();

        return true;
    }

    /**
     * Returns true if all migrations succeeded.
     */
    private function allMigrationsSucceeded(): bool
    {
        return array_all($this->migrationResults, static fn($result) => $result->isSuccess());
    }

    /**
     * Collects SQL queries from migrations for dry-run backward compatibility.
     *
     * @param array<string, MigrationInterface> $migrations
     */
    private function collectDryRunQueries(array $migrations): void
    {
        $report = $this->migrationExecutor->generateDryRunReport($migrations);

        foreach ($report['migrations'] as $migrationData) {
            foreach ($migrationData['operations'] as $operation) {
                if ($operation['type'] !== 'sql') {
                    continue;
                }

                $this->dryRunQueries[] = $operation['query'];
            }
        }
    }

    /**
     * Run any post-migration tasks that can't be handled by the migration system.
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
     * Insert form inputs (special handling required due to complex business logic).
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
     * Returns detailed dry-run results including all operation types.
     *
     * @return array<string, mixed>
     */
    public function getDryRunResults(): array
    {
        $pendingMigrations = $this->migrationRegistry->getPendingMigrations($this->version);
        return $this->migrationExecutor->generateDryRunReport($pendingMigrations);
    }

    /**
     * Returns the formatted dry-run report as a string.
     */
    public function getFormattedDryRunReport(): string
    {
        $pendingMigrations = $this->migrationRegistry->getPendingMigrations($this->version);
        $report = $this->migrationExecutor->generateDryRunReport($pendingMigrations);
        return $this->migrationExecutor->formatDryRunReport($report);
    }

    /**
     * @throws Exception
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
                    if ($entity->getHash() !== null) {
                        continue;
                    }

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
            } catch (\Exception $e) {
                $this->configuration->getLogger()->error('Admin log hash migration failed: ' . $e->getMessage());
            }
        }
    }
}
