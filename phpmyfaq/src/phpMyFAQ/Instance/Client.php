<?php

/**
 * The main phpMyFAQ instances class for instance clients.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\TenantIsolationMode;
use phpMyFAQ\Filesystem\Filesystem;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Database as InstanceDatabase;

/**
 * Class Client
 *
 * @package phpMyFAQ\Instance
 */
class Client extends Instance
{
    private ?Filesystem $filesystem = null;

    private readonly string $clientFolder;

    private string $clientUrl;

    /**
     * Constructor.
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);

        $this->clientFolder = PMF_ROOT_DIR . '/multisite/';
    }

    /**
     * Sets client URL.
     */
    public function setClientUrl(string $clientUrl): void
    {
        $this->clientUrl = $clientUrl;
    }

    /**
     * Sets the Filesystem.
     */
    public function setFileSystem(Filesystem $fileSystem): void
    {
        $this->filesystem = $fileSystem;
    }

    public function createClient(Instance $instance): void
    {
        $instance->addConfig('isMaster', 'false');
    }

    /**
     * Adds a new folder named by the given hostname in /path/to/faq/multisite/.
     *
     * @param string $hostname Hostname of the client instance
     */
    public function createClientFolder(string $hostname): bool
    {
        if (!$this->isMultiSiteWriteable()) {
            return false;
        }

        return $this->filesystem->createDirectory($this->clientFolder . $hostname);
    }

    /**
     * Creates client database isolation based on the configured tenant isolation mode.
     *
     * Supports three isolation strategies:
     * - prefix: Table prefix isolation in a shared database (default, delegates to createClientTables)
     * - schema: Schema-per-tenant in a shared database
     * - database: Separate database per tenant
     *
     * @param string $tenantIdentifier The prefix, schema name, or database name for the tenant
     * @param TenantIsolationMode|null $mode Isolation mode defaults to reading from PMF_TENANT_ISOLATION_MODE env var
     */
    public function createClientDatabase(string $tenantIdentifier, ?TenantIsolationMode $mode = null): void
    {
        $envValue = getenv('PMF_TENANT_ISOLATION_MODE');
        $mode ??=
            TenantIsolationMode::tryFrom($envValue !== false && $envValue !== '' ? $envValue : 'prefix')
            ?? TenantIsolationMode::PREFIX;

        match ($mode) {
            TenantIsolationMode::PREFIX => $this->createClientTables($tenantIdentifier),
            TenantIsolationMode::SCHEMA => $this->createClientTablesWithSchema($tenantIdentifier),
            TenantIsolationMode::DATABASE => $this->createClientTablesWithDatabase($tenantIdentifier),
        };
    }

    /**
     * Creates all tables in a dedicated schema or database for tenant isolation.
     *
     * @param string $schema Schema or database name for the tenant
     * @throws Exception
     */
    private function createClientTablesWithSchema(string $schema): void
    {
        try {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $schema)) {
                throw new Exception('Invalid tenant schema identifier.');
            }

            $instanceDatabase = InstanceDatabase::factory($this->configuration, Database::getType());
            if (!$instanceDatabase->createTables('', $schema)) {
                throw new Exception('Failed to create tenant tables in schema.');
            }

            $this->copyBaseDataToSchema($schema);
        } catch (Exception $exception) {
            $this->configuration->getLogger()->error('Failed to create tenant schema tables.', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'schema' => $schema,
            ]);
            throw $exception;
        }
    }

    /**
     * Creates all tables in a dedicated database for tenant isolation.
     *
     * Supported drivers: PostgreSQL, SQL Server.
     *
     * @throws Exception
     */
    private function createClientTablesWithDatabase(string $databaseName): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
            throw new Exception('Invalid tenant database identifier.');
        }

        $dbType = strtolower(Database::getType());
        if (!str_contains($dbType, 'pgsql') && !str_contains($dbType, 'sqlsrv')) {
            throw new Exception(sprintf(
                'Database-per-tenant isolation is not supported for driver "%s". Use PostgreSQL or SQL Server.',
                Database::getType(),
            ));
        }

        $credentials = $this->getDatabaseCredentials();
        if ($credentials === null) {
            return;
        }

        $sourcePrefix = Database::getTablePrefix();
        $targetPrefix = $sourcePrefix ?? '';
        $seedRows = $this->collectSeedRows($sourcePrefix ?? '');
        $sourceDatabase = $credentials['database'];

        try {
            if (!InstanceDatabase::createTenantDatabase($this->configuration, Database::getType(), $databaseName)) {
                return;
            }

            if (!$this->configuration->getDb()->connect(
                $credentials['server'],
                $credentials['user'],
                $credentials['password'],
                $databaseName,
                $credentials['port'],
            )) {
                return;
            }

            $instanceDatabase = InstanceDatabase::factory($this->configuration, Database::getType());
            if (!$instanceDatabase->createTables($targetPrefix)) {
                return;
            }

            $this->insertSeedRows($targetPrefix, $seedRows);
        } catch (Exception) {
        } finally {
            $this->configuration->getDb()->connect(
                $credentials['server'],
                $credentials['user'],
                $credentials['password'],
                $sourceDatabase,
                $credentials['port'],
            );
        }
    }

    /**
     * Copies base configuration, rights, and user data into a tenant's schema/database.
     *
     * @throws Exception
     */
    private function copyBaseDataToSchema(string $schema): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $schema)) {
            throw new Exception('Invalid tenant schema identifier.');
        }

        $dbType = Database::getType();
        $sourcePrefix = Database::getTablePrefix();
        $escapedClientUrl = $this->configuration->getDb()->escape($this->clientUrl);

        $targetPrefix = sprintf('`%s`.', $schema);

        if (str_contains($dbType, 'pgsql') || str_contains($dbType, 'Pgsql')) {
            $targetPrefix = sprintf('"%s".', $schema);
            $this->configuration->getDb()->query(sprintf('SET search_path TO "%s"', $schema));
        } elseif (str_contains($dbType, 'sqlsrv') || str_contains($dbType, 'Sqlsrv')) {
            $targetPrefix = sprintf('[%s].', $schema);
        }

        $this->configuration
            ->getDb()
            ->query(sprintf('INSERT INTO %sfaqconfig SELECT * FROM %sfaqconfig', $targetPrefix, $sourcePrefix));

        $this->configuration
            ->getDb()
            ->query(sprintf(
                "UPDATE %sfaqconfig SET config_value = '%s' WHERE config_name = 'main.referenceURL'",
                $targetPrefix,
                $escapedClientUrl,
            ));

        $this->configuration
            ->getDb()
            ->query(sprintf('INSERT INTO %sfaqright SELECT * FROM %sfaqright', $targetPrefix, $sourcePrefix));

        $this->configuration
            ->getDb()
            ->query(sprintf(
                'INSERT INTO %sfaquser_right SELECT * FROM %sfaquser_right WHERE user_id = 1',
                $targetPrefix,
                $sourcePrefix,
            ));
    }

    private function getDatabaseCredentials(): ?array
    {
        $databaseFile = PMF_CONFIG_DIR . '/database.php';
        if (!file_exists($databaseFile)) {
            return null;
        }

        $DB = [];
        include $databaseFile;

        if (!isset($DB['server'], $DB['user'], $DB['password'], $DB['db'])) {
            return null;
        }

        return [
            'server' => (string) $DB['server'],
            'port' => ($DB['port'] ?? '') === '' ? null : (int) $DB['port'],
            'user' => (string) $DB['user'],
            'password' => (string) $DB['password'],
            'database' => (string) $DB['db'],
        ];
    }

    /**
     * Reads seed data from source database before switching to the tenant database.
     */
    private function collectSeedRows(string $prefix): array
    {
        $tables = [
            'faqconfig' => sprintf('SELECT * FROM %sfaqconfig', $prefix),
            'faqright' => sprintf('SELECT * FROM %sfaqright', $prefix),
            'faquser_right' => sprintf('SELECT * FROM %sfaquser_right WHERE user_id = 1', $prefix),
        ];

        $rows = [];
        foreach ($tables as $table => $query) {
            $result = $this->configuration->getDb()->query($query);
            $rows[$table] = $result === false ? [] : $this->configuration->getDb()->fetchAll($result) ?? [];
        }

        return $rows;
    }

    private function insertSeedRows(string $prefix, array $seedRows): void
    {
        $this->insertRows($prefix . 'faqconfig', $seedRows['faqconfig'] ?? []);
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "UPDATE %sfaqconfig SET config_value = '%s' WHERE config_name = 'main.referenceURL'",
                $prefix,
                $this->configuration->getDb()->escape($this->clientUrl),
            ));

        $this->insertRows($prefix . 'faqright', $seedRows['faqright'] ?? []);
        $this->insertRows($prefix . 'faquser_right', $seedRows['faquser_right'] ?? []);
    }

    private function insertRows(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            $rowData = (array) $row;
            $quotedColumns = array_map($this->quoteIdentifier(...), array_keys($rowData));
            $values = array_map(fn(mixed $value): string => $value === null
                ? 'NULL'
                : sprintf("'%s'", $this->configuration->getDb()->escape((string) $value)), array_values($rowData));

            $query = sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $table,
                implode(', ', $quotedColumns),
                implode(', ', $values),
            );

            $result = $this->configuration->getDb()->query($query);

            if ($result === false) {
                $dbError = $this->configuration->getDb()->error();
                $this->configuration->getLogger()->error('Failed to insert row into tenant table.', [
                    'table' => $table,
                    'query' => $query,
                    'error' => $dbError,
                ]);

                throw new \RuntimeException(sprintf('Failed to insert row into %s: %s', $table, $dbError));
            }
        }
    }

    /**
     * Quotes a column or table identifier for the current database driver.
     */
    private function quoteIdentifier(string $name): string
    {
        $dbType = Database::getType();

        if (str_contains($dbType, 'sqlsrv') || str_contains($dbType, 'Sqlsrv')) {
            return sprintf('[%s]', str_replace(']', ']]', $name));
        }

        if (str_contains($dbType, 'pgsql') || str_contains($dbType, 'Pgsql') || str_contains($dbType, 'sqlite')) {
            return sprintf('"%s"', str_replace('"', '""', $name));
        }

        return sprintf('`%s`', str_replace('`', '``', $name));
    }

    /**
     * Creates all tables with the given table prefix from the primary tables.
     *
     * @param string $prefix SQL table prefix
     * @throws Exception
     */
    public function createClientTables(string $prefix): void
    {
        try {
            // First, create the client tables
            $instanceDatabase = InstanceDatabase::factory($this->configuration, Database::getType());
            $instanceDatabase->createTables($prefix);

            // Then, copy data from the tables "faqconfig", "faqright" and "faquser_right"
            $this->configuration
                ->getDb()
                ->query(sprintf(
                    'INSERT INTO %sfaqconfig SELECT * FROM %sfaqconfig',
                    $prefix,
                    Database::getTablePrefix(),
                ));
            $this->configuration
                ->getDb()
                ->query(sprintf(
                    "UPDATE %sfaqconfig SET config_value = '%s' WHERE config_name = 'main.referenceURL'",
                    $prefix,
                    $this->clientUrl,
                ));
            $this->configuration
                ->getDb()
                ->query(sprintf(
                    'INSERT INTO %sfaqright SELECT * FROM %sfaqright',
                    $prefix,
                    Database::getTablePrefix(),
                ));
            $this->configuration
                ->getDb()
                ->query(sprintf(
                    'INSERT INTO %sfaquser_right SELECT * FROM %sfaquser_right WHERE user_id = 1',
                    $prefix,
                    Database::getTablePrefix(),
                ));
        } catch (Exception $exception) {
            $this->configuration->getLogger()->error('Failed to create tenant prefix tables.', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'prefix' => $prefix,
            ]);
            throw $exception;
        }
    }

    /**
     * Copies the config/constants.php file to a new client instance.
     *
     * @param string $destination Destination file
     * @throws Exception
     */
    public function copyConstantsFile(string $destination): bool
    {
        return $this->filesystem->copy(
            $this->filesystem->getRootPath() . '/content/core/config/constants.php',
            $destination,
        );
    }

    /**
     * Copies a defined template folder to a new client instance; by default,
     * the default template located at ./assets/templates/default/ will be copied.
     *
     * @param string $destination Destination folder
     * @param string $templateDir Template folder
     * @throws Exception
     */
    public function copyTemplateFolder(string $destination, string $templateDir = 'default'): void
    {
        $sourceTpl = $this->filesystem->getRootPath() . '/assets/templates/' . $templateDir;
        $destTpl = $destination . '/assets/templates/';

        $this->filesystem->recursiveCopy($sourceTpl, $destTpl);
    }

    /**
     * Moves the entire folder to the new destination
     */
    public function moveClientFolder(string $sourceUrl, string $destinationUrl): bool
    {
        if (!$this->isMultiSiteWriteable()) {
            return false;
        }

        $sourcePath = str_replace('https://', '', $sourceUrl);
        $destinationPath = str_replace('https://', '', $destinationUrl);

        return $this->filesystem->moveDirectory(
            $this->clientFolder . $sourcePath,
            $this->clientFolder . $destinationPath,
        );
    }

    /**
     * Deletes the given client folder
     */
    public function deleteClientFolder(string $sourceUrl): bool
    {
        if (!$this->isMultiSiteWriteable()) {
            return false;
        }

        $sourcePath = str_replace('https://', '', $sourceUrl);
        return $this->filesystem->deleteDirectory($this->clientFolder . $sourcePath);
    }

    /**
     * Checks if the multisite/ folder is writeable
     */
    public function isMultiSiteWriteable(): bool
    {
        return is_writable($this->clientFolder);
    }
}
