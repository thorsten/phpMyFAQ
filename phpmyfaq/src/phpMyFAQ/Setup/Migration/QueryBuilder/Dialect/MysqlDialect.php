<?php

/**
 * MySQL/MariaDB specific SQL dialect.
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

namespace phpMyFAQ\Setup\Migration\QueryBuilder\Dialect;

use phpMyFAQ\Setup\Migration\QueryBuilder\DialectInterface;

class MysqlDialect implements DialectInterface
{
    /**
     * Provide the driver type identifier for this dialect.
     *
     * @return string The driver type identifier — `'mysqli'`.
     */
    public function getType(): string
    {
        return 'mysqli';
    }

    /**
     * Get the SQL type for integer columns.
     *
     * @return string The SQL type 'INT'.
     */
    public function integer(): string
    {
        return 'INT';
    }

    /**
     * Provides the SQL type name for a big integer column.
     *
     * @return string The SQL type `BIGINT`.
     */
    public function bigInteger(): string
    {
        return 'BIGINT';
    }

    /**
     * Provides the SQL type for a small integer column.
     *
     * @return string The SQL type `SMALLINT`.
     */
    public function smallInteger(): string
    {
        return 'SMALLINT';
    }

    /**
     * Builds a VARCHAR type declaration with the specified length.
     *
     * @param int $length The maximum character length for the VARCHAR column.
     * @return string The VARCHAR type declaration (e.g. `VARCHAR(255)`).
     */
    public function varchar(int $length): string
    {
        return "VARCHAR($length)";
    }

    /**
     * Provide the SQL type for a variable-length text column.
     *
     * @return string The SQL type 'TEXT'.
     */
    public function text(): string
    {
        return 'TEXT';
    }

    /**
     * SQL type used to represent boolean values in MySQL.
     *
     * @return string The SQL type 'TINYINT(1)' used to store boolean values.
     */
    public function boolean(): string
    {
        return 'TINYINT(1)';
    }

    /**
     * Get the SQL type name for timestamp columns.
     *
     * @return string The SQL type 'TIMESTAMP'.
     */
    public function timestamp(): string
    {
        return 'TIMESTAMP';
    }

    /**
     * Get the SQL type for date columns.
     *
     * @return string The SQL type 'DATE'.
     */
    public function date(): string
    {
        return 'DATE';
    }

    /**
     * Create a CHAR type definition for the given length.
     *
     * @param int $length The character length for the CHAR column.
     * @return string The SQL type definition `CHAR($length)`.
     */
    public function char(int $length): string
    {
        return "CHAR($length)";
    }

    /**
     * SQL expression for the current timestamp.
     *
     * @return string The SQL expression `CURRENT_TIMESTAMP`.
     */
    public function currentTimestamp(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * SQL expression for retrieving the current date.
     *
     * @return string The SQL expression `CURDATE()` which yields the current date.
     */
    public function currentDate(): string
    {
        return 'CURDATE()';
    }

    /**
     * Builds a column definition fragment for an auto-incrementing integer column.
     *
     * @param string $columnName The name of the column.
     * @return string The SQL fragment for the column definition (e.g. "`id` INT NOT NULL AUTO_INCREMENT").
     */
    public function autoIncrement(string $columnName): string
    {
        return "$columnName INT NOT NULL AUTO_INCREMENT";
    }

    /**
     * Builds the prefix of a CREATE TABLE statement for the specified table.
     *
     * @param string $tableName The table name to create.
     * @param bool $ifNotExists If true, includes 'IF NOT EXISTS' after 'CREATE TABLE'.
     * @return string The SQL prefix: 'CREATE TABLE {IF NOT EXISTS }{tableName}'.
     */
    public function createTablePrefix(string $tableName, bool $ifNotExists = false): string
    {
        $exists = $ifNotExists ? 'IF NOT EXISTS ' : '';
        return "CREATE TABLE {$exists}{$tableName}";
    }

    /**
     * Provides the default table options fragment used when creating MySQL/MariaDB tables.
     *
     * @return string The SQL fragment specifying DEFAULT CHARACTER SET utf8mb4, COLLATE utf8mb4_unicode_ci, and ENGINE = InnoDB.
     */
    public function createTableSuffix(): string
    {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB';
    }

    /**
     * Builds an ALTER TABLE statement that adds a column to a table.
     *
     * @param string $tableName The target table name.
     * @param string $columnName The name of the column to add.
     * @param string $type The SQL type definition for the new column (e.g., 'INT NOT NULL').
     * @param string|null $after Column name after which the new column should be placed; if null the column is appended.
     * @return string The generated ALTER TABLE ... ADD COLUMN SQL statement.
     */
    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string
    {
        $afterClause = $after !== null ? " AFTER $after" : '';
        return "ALTER TABLE $tableName ADD COLUMN $columnName $type$afterClause";
    }

    /**
     * Builds an ALTER TABLE statement to change a column's type.
     *
     * @param string $tableName The name of the table containing the column.
     * @param string $columnName The name of the column to modify.
     * @param string $newType The new column type and any modifiers (e.g., "INT NOT NULL").
     * @return string The ALTER TABLE SQL statement that modifies the column type.
     */
    public function modifyColumn(string $tableName, string $columnName, string $newType): string
    {
        return "ALTER TABLE $tableName MODIFY $columnName $newType";
    }

    /**
     * Builds a CREATE INDEX statement for a MySQL table.
     *
     * @param string $indexName Name of the index.
     * @param string $tableName Name of the table to create the index on.
     * @param string[] $columns List of column names to include in the index, in order.
     * @param bool $ifNotExists Ignored for MySQL (parameter retained for API compatibility).
     * @return string The SQL `CREATE INDEX` statement for the given table and columns.
     */
    public function createIndex(string $indexName, string $tableName, array $columns, bool $ifNotExists = false): string
    {
        $columnList = implode(', ', $columns);
        // MySQL doesn't support IF NOT EXISTS for CREATE INDEX directly
        return "CREATE INDEX $indexName ON $tableName ($columnList)";
    }

    /**
     * Build a DROP INDEX statement for the given index on a table.
     *
     * @returns string The SQL statement that drops the specified index from the specified table (e.g. "DROP INDEX index_name ON table_name").
     */
    public function dropIndex(string $indexName, string $tableName): string
    {
        return "DROP INDEX $indexName ON $tableName";
    }

    /**
     * Indicates whether this SQL dialect supports specifying column position (for example using `AFTER`) in ALTER statements.
     *
     * @return bool `true` if the dialect supports column positioning, `false` otherwise.
     */
    public function supportsColumnPositioning(): bool
    {
        return true;
    }

    /**
     * Escape backticks in an SQL identifier and wrap it in backticks for safe quoting.
     *
     * @param string $identifier The identifier to quote (e.g., table or column name).
     * @return string The identifier with internal backticks doubled and surrounded by backticks (e.g. `escaped``name`).
     */
    public function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}