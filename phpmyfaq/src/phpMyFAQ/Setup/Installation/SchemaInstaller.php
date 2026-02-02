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
    private array $collectedSql = [];

    private bool $dryRun = false;

    public function __construct(
        private readonly Configuration $configuration,
        ?DialectInterface $dialect = null,
    ) {
        $this->dialect = $dialect ?? DialectFactory::create();
        $this->schema = new DatabaseSchema($this->dialect);
    }

    /**
     * Enables or disables dry-run mode. In dry-run mode, SQL is collected but not executed.
     */
    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Returns collected SQL statements from dry-run mode.
     *
     * @return string[]
     */
    public function getCollectedSql(): array
    {
        return $this->collectedSql;
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
     */
    public function createTables(string $prefix = ''): bool
    {
        $previousPrefix = Database::getTablePrefix();

        if ($prefix !== '') {
            Database::setTablePrefix($prefix);
        }

        $this->collectedSql = [];

        try {
            foreach ($this->schema->getAllTables() as $tableBuilder) {
                $createTableSql = $tableBuilder->build();

                if (!$this->executeSql($createTableSql)) {
                    return false;
                }

                foreach ($tableBuilder->buildIndexStatements() as $indexSql) {
                    if (!$this->executeSql($indexSql)) {
                        return false;
                    }
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

        $result = $this->configuration->getDb()->query($sql);

        if (!$result) {
            return false;
        }

        return true;
    }
}
