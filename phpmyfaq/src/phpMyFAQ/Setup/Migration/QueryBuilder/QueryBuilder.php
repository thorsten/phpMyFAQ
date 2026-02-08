<?php

/**
 * Main query builder providing access to table and alter table builders.
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
 * @since     2026-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\QueryBuilder;

use phpMyFAQ\Database;

class QueryBuilder
{
    private DialectInterface $dialect;
    private string $tablePrefix;

    public function __construct(?DialectInterface $dialect = null)
    {
        $this->dialect = $dialect ?? DialectFactory::create();
        $this->tablePrefix = Database::getTablePrefix();
    }

    /**
     * Creates a new TableBuilder for CREATE TABLE statements.
     */
    public function createTable(string $tableName, bool $withPrefix = true): TableBuilder
    {
        $builder = new TableBuilder($this->dialect);
        return $builder->table($tableName, $withPrefix);
    }

    /**
     * Creates a new TableBuilder with IF NOT EXISTS.
     */
    public function createTableIfNotExists(string $tableName, bool $withPrefix = true): TableBuilder
    {
        $builder = new TableBuilder($this->dialect);
        return $builder->table($tableName, $withPrefix)->ifNotExists();
    }

    /**
     * Creates a new AlterTableBuilder for ALTER TABLE statements.
     */
    public function alterTable(string $tableName, bool $withPrefix = true): AlterTableBuilder
    {
        $builder = new AlterTableBuilder($this->dialect);
        return $builder->table($tableName, $withPrefix);
    }

    /**
     * Creates a DROP TABLE statement.
     */
    public function dropTable(string $tableName, bool $withPrefix = true): string
    {
        $fullName = $withPrefix ? $this->tablePrefix . $tableName : $tableName;
        return "DROP TABLE {$fullName}";
    }

    /**
     * Creates a DROP TABLE IF EXISTS statement.
     */
    public function dropTableIfExists(string $tableName, bool $withPrefix = true): string
    {
        $fullName = $withPrefix ? $this->tablePrefix . $tableName : $tableName;
        return "DROP TABLE IF EXISTS {$fullName}";
    }

    /**
     * Creates a CREATE INDEX statement.
     *
     * @param string|string[] $columns
     */
    public function createIndex(
        string $indexName,
        string $tableName,
        string|array $columns,
        bool $withPrefix = true,
    ): string {
        $fullTableName = $withPrefix ? $this->tablePrefix . $tableName : $tableName;
        return $this->dialect->createIndex($indexName, $fullTableName, (array) $columns, false);
    }

    /**
     * Creates a CREATE INDEX IF NOT EXISTS statement.
     *
     * @param string|string[] $columns
     */
    public function createIndexIfNotExists(
        string $indexName,
        string $tableName,
        string|array $columns,
        bool $withPrefix = true,
    ): string {
        $fullTableName = $withPrefix ? $this->tablePrefix . $tableName : $tableName;
        return $this->dialect->createIndex($indexName, $fullTableName, (array) $columns, true);
    }

    /**
     * Creates a DROP INDEX statement.
     */
    public function dropIndex(string $indexName, string $tableName, bool $withPrefix = true): string
    {
        $fullTableName = $withPrefix ? $this->tablePrefix . $tableName : $tableName;
        return $this->dialect->dropIndex($indexName, $fullTableName);
    }

    /**
     * Gets the current dialect.
     */
    public function getDialect(): DialectInterface
    {
        return $this->dialect;
    }

    /**
     * Gets the table prefix.
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Returns a prefixed table name.
     */
    public function table(string $name): string
    {
        return $this->tablePrefix . $name;
    }
}
