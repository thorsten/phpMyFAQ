<?php

/**
 * Installs the database schema using the dialect-agnostic DatabaseSchema.
 *
 * Iterates over each table definition from DatabaseSchema, builds CREATE TABLE
 * and CREATE INDEX statements, and executes them via the database connection.
 * Implements DriverInterface for backward compatibility with the existing
 * Instance\Database factory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Installation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Instance\Database\DriverInterface;
use phpMyFAQ\Setup\Migration\QueryBuilder\DialectFactory;
use phpMyFAQ\Setup\Migration\QueryBuilder\DialectInterface;

class SchemaInstaller implements DriverInterface
{
    private readonly DialectInterface $dialect;

    private readonly DatabaseSchema $schema;

    /** @var string[] Collected SQL for dry-run */
    public array $collectedSql = [];

    public bool $dryRun = false;

    public function __construct(
        private readonly Configuration $configuration,
        ?DialectInterface $dialect = null,
    ) {
        $this->dialect = $dialect ?? DialectFactory::create();
        $this->schema = new DatabaseSchema($this->dialect);
    }

    /**
     * Returns the DatabaseSchema instance.
     */
    public function getSchema(): DatabaseSchema
    {
        return $this->schema;
    }

    /**
     * Executes all CREATE TABLE and CREATE INDEX statements.
     *
     * @param string $prefix Table prefix to apply. The previous prefix is restored after execution.
     * @param string|null $schema Schema or database name for schema/database-based tenant isolation.
     *                            For MySQL: creates and switches to a database.
     *                            For PostgreSQL: creates and switches to a schema.
     */
    public function createTables(string $prefix = '', ?string $schema = null): bool
    {
        $previousPrefix = Database::getTablePrefix();

        if ($prefix !== '') {
            Database::setTablePrefix($prefix);
        }

        $this->collectedSql = [];

        try {
            if ($schema !== null && $schema !== '') {
                if (!$this->createAndUseSchema($schema)) {
                    return false;
                }
            }

            foreach ($this->schema->getAllTables() as $tableBuilder) {
                $createTableSql = $tableBuilder->build();

                if (!$this->executeSql($createTableSql)) {
                    return false;
                }

                foreach ($tableBuilder->buildIndexStatements() as $indexSql) {
                    if ($this->executeSql($indexSql)) {
                        continue;
                    }

                    return false;
                }
            }

            return true;
        } finally {
            if ($prefix !== '') {
                Database::setTablePrefix($previousPrefix ?? '');
            }
        }
    }

    /**
     * Creates a schema/database and switches to it.
     *
     * For MySQL: CREATE DATABASE + USE.
     * For PostgreSQL: CREATE SCHEMA + SET search_path.
     */
    private function createAndUseSchema(string $schema): bool
    {
        $dialectClass = $this->dialect::class;

        if (str_contains($dialectClass, 'Mysql')) {
            return (
                $this->executeSql(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $schema))
                && $this->executeSql(sprintf('USE `%s`', $schema))
            );
        }

        if (str_contains($dialectClass, 'Pgsql')) {
            return (
                $this->executeSql(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $schema))
                && $this->executeSql(sprintf('SET search_path TO "%s"', $schema))
            );
        }

        if (str_contains($dialectClass, 'Sqlsrv')) {
            return $this->executeSql(sprintf(
                "IF NOT EXISTS (SELECT * FROM sys.schemas WHERE name = '%s') EXEC('CREATE SCHEMA [%s]')",
                $schema,
                $schema,
            ));
        }

        return true;
    }

    /**
     * Executes all DROP TABLE statements for the schema tables.
     */
    public function dropTables(string $prefix = ''): bool
    {
        if ($prefix === '') {
            $prefix = Database::getTablePrefix() ?? '';
        }

        foreach ($this->schema->getTableNames() as $tableName) {
            $sql = sprintf('DROP TABLE %s%s', $prefix, $tableName);
            $result = $this->configuration->getDb()->query($sql);

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    private function executeSql(string $sql): bool
    {
        $this->collectedSql[] = $sql;

        if ($this->dryRun) {
            return true;
        }

        return (bool) $this->configuration->getDb()->query($sql);
    }
}
