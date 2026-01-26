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

    /**
     * Initialize the QueryBuilder with a SQL dialect and configured table prefix.
     *
     * @param DialectInterface|null $dialect Optional SQL dialect to use. If null, a default dialect is created.
     */
    public function __construct(?DialectInterface $dialect = null)
    {
        $this->dialect = $dialect ?? DialectFactory::create();
        $this->tablePrefix = Database::getTablePrefix();
    }

    /**
         * Create a TableBuilder configured for the given table name.
         *
         * @param string $tableName The table name (without prefix).
         * @param bool $withPrefix Whether to prepend the configured table prefix.
         * @return TableBuilder The configured TableBuilder for building a CREATE TABLE statement.
         */
    public function createTable(string $tableName, bool $withPrefix = true): TableBuilder
    {
        $builder = new TableBuilder($this->dialect);
        return $builder->table($tableName, $withPrefix);
    }

    /**
     * Create a TableBuilder configured for the given table with "IF NOT EXISTS" enabled.
     *
     * @param string $tableName The name of the table to build.
     * @param bool $withPrefix Whether to apply the configured table prefix.
     * @return TableBuilder The configured TableBuilder with IF NOT EXISTS set.
     */
    public function createTableIfNotExists(string $tableName, bool $withPrefix = true): TableBuilder
    {
        $builder = new TableBuilder($this->dialect);
        return $builder->table($tableName, $withPrefix)->ifNotExists();
    }

    /**
     * Create an AlterTableBuilder configured for the specified table.
     *
     * @param string $tableName The target table name (provided without prefix).
     * @param bool $withPrefix Whether to prepend the configured table prefix to $tableName.
     * @return AlterTableBuilder The builder configured for the resolved table name.
     */
    public function alterTable(string $tableName, bool $withPrefix = true): AlterTableBuilder
    {
        $builder = new AlterTableBuilder($this->dialect);
        return $builder->table($tableName, $withPrefix);
    }

    /**
         * Builds a DROP TABLE SQL statement for the specified table.
         *
         * @param string $tableName The table name (without prefix).
         * @param bool $withPrefix If true, prepends the configured table prefix to `$tableName`.
         * @return string The generated `DROP TABLE` statement targeting the resolved table name.
         */
    public function dropTable(string $tableName, bool $withPrefix = true): string
    {
        $fullName = $withPrefix ? $this->tablePrefix . $tableName : $tableName;
        return "DROP TABLE $fullName";
    }

    /**
         * Build a DROP TABLE IF EXISTS SQL statement for the specified table.
         *
         * @param string $tableName The name of the table (without prefix).
         * @param bool $withPrefix Whether to prepend the configured table prefix to the table name.
         * @return string The SQL statement "DROP TABLE IF EXISTS <fullTableName>".
         */
    public function dropTableIfExists(string $tableName, bool $withPrefix = true): string
    {
        $fullName = $withPrefix ? $this->tablePrefix . $tableName : $tableName;
        return "DROP TABLE IF EXISTS $fullName";
    }

    /**
     * Builds a CREATE INDEX SQL statement for the specified table and columns.
     *
     * @param string $indexName The name of the index.
     * @param string $tableName The table name; the configured table prefix is prepended when $withPrefix is true.
     * @param string|array $columns A column name or an array of column names to include in the index.
     * @param bool $withPrefix Whether to prepend the configured table prefix to $tableName.
     * @return string The generated CREATE INDEX SQL statement.
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
     * Builds a CREATE INDEX IF NOT EXISTS SQL statement for the specified table.
     *
     * @param string $indexName The name of the index to create.
     * @param string $tableName The table name to create the index on.
     * @param string|string[] $columns Column name or array of column names to include in the index.
     * @param bool $withPrefix Whether to prepend the configured table prefix to $tableName.
     * @return string The generated CREATE INDEX IF NOT EXISTS SQL statement.
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
         * Builds a DROP INDEX SQL statement for an index on a table.
         *
         * @param string $indexName The name of the index to drop.
         * @param string $tableName The table name (without prefix).
         * @param bool $withPrefix Whether to prepend the configured table prefix to $tableName.
         * @return string The generated DROP INDEX SQL statement.
         */
    public function dropIndex(string $indexName, string $tableName, bool $withPrefix = true): string
    {
        $fullTableName = $withPrefix ? $this->tablePrefix . $tableName : $tableName;
        return $this->dialect->dropIndex($indexName, $fullTableName);
    }

    /**
     * Provides the SQL dialect used to generate SQL statements.
     *
     * @return DialectInterface The dialect instance used for SQL generation.
     */
    public function getDialect(): DialectInterface
    {
        return $this->dialect;
    }

    /**
     * Return the configured database table name prefix used by this QueryBuilder.
     *
     * @return string The table prefix (possibly an empty string) that is prepended to table names.
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
         * Compute the table name using the configured table prefix.
         *
         * @param string $name The base table name without prefix.
         * @return string The table name prefixed with the configured table prefix.
         */
    public function table(string $name): string
    {
        return $this->tablePrefix . $name;
    }
}