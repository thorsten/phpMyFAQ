<?php

/**
 * PostgreSQL specific SQL dialect.
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

class PostgresDialect implements DialectInterface
{
    /**
     * Identify the SQL dialect as PostgreSQL.
     *
     * @return string The dialect identifier 'pgsql'.
     */
    public function getType(): string
    {
        return 'pgsql';
    }

    /**
     * Provide the PostgreSQL integer column type.
     *
     * @return string The SQL type name 'INTEGER'.
     */
    public function integer(): string
    {
        return 'INTEGER';
    }

    /**
     * Get the PostgreSQL column type used for big integers.
     *
     * @return string The PostgreSQL type name for big integers (`BIGINT`).
     */
    public function bigInteger(): string
    {
        return 'BIGINT';
    }

    /**
     * Provide the PostgreSQL SQL type name for a small integer.
     *
     * @return string The SQL type `SMALLINT`.
     */
    public function smallInteger(): string
    {
        return 'SMALLINT';
    }

    /**
     * Produces a VARCHAR column type declaration with the specified maximum length.
     *
     * @param int $length The maximum number of characters for the VARCHAR column.
     * @return string The VARCHAR type declaration formatted as `VARCHAR($length)`.
     */
    public function varchar(int $length): string
    {
        return "VARCHAR($length)";
    }

    /**
     * Returns the PostgreSQL column type for variable-length text.
     *
     * @return string The SQL type declaration `TEXT`.
     */
    public function text(): string
    {
        return 'TEXT';
    }

    /**
     * Provide the SQL type used to represent boolean values in this dialect.
     *
     * @return string The SQL type name used for boolean values (e.g., "SMALLINT").
     */
    public function boolean(): string
    {
        return 'SMALLINT';
    }

    /**
     * Provide the PostgreSQL column type for timestamp values.
     *
     * @return string The SQL type name 'TIMESTAMP'.
     */
    public function timestamp(): string
    {
        return 'TIMESTAMP';
    }

    /**
     * Get the SQL DATE type name used by the PostgreSQL dialect.
     *
     * @return string The SQL type name 'DATE'.
     */
    public function date(): string
    {
        return 'DATE';
    }

    / **
     * Get a fixed-length CHAR type declaration for the specified length.
     *
     * @param int $length The number of characters for the CHAR type.
     * @return string The SQL type declaration formatted as "CHAR(<length>)".
     */
    public function char(int $length): string
    {
        return "CHAR($length)";
    }

    /**
     * Return the SQL expression that yields the current timestamp in PostgreSQL.
     *
     * @return string The SQL expression CURRENT_TIMESTAMP.
     */
    public function currentTimestamp(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * SQL expression for the current date in PostgreSQL.
     *
     * @return string The SQL expression `CURRENT_DATE`.
     */
    public function currentDate(): string
    {
        return 'CURRENT_DATE';
    }

    /**
     * Produce a PostgreSQL auto-increment column definition for the given column name.
     *
     * @param string $columnName The column name to define as auto-increment.
     * @return string The SQL fragment for the column definition using `SERIAL NOT NULL`.
     */
    public function autoIncrement(string $columnName): string
    {
        return "$columnName SERIAL NOT NULL";
    }

    /**
     * Builds the prefix for a CREATE TABLE statement for the given table.
     *
     * @param string $tableName The name of the table to create (unquoted).
     * @param bool $ifNotExists When true, includes the `IF NOT EXISTS` clause.
     * @return string The CREATE TABLE statement prefix (e.g. `CREATE TABLE IF NOT EXISTS my_table` or `CREATE TABLE my_table`).
     */
    public function createTablePrefix(string $tableName, bool $ifNotExists = false): string
    {
        $exists = $ifNotExists ? 'IF NOT EXISTS ' : '';
        return "CREATE TABLE {$exists}{$tableName}";
    }

    /**
     * Provide the suffix appended to CREATE TABLE statements.
     *
     * @return string An empty string (no suffix) for CREATE TABLE statements.
     */
    public function createTableSuffix(): string
    {
        return '';
    }

    /**
     * Generate an ALTER TABLE statement to add a column to a PostgreSQL table.
     *
     * @param string $tableName The name of the table to alter.
     * @param string $columnName The name of the column to add.
     * @param string $type The SQL type declaration for the new column.
     * @param string|null $after Ignored for PostgreSQL (present for interface compatibility).
     * @return string The SQL statement, e.g. "ALTER TABLE <table> ADD COLUMN <column> <type>".
     */
    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string
    {
        // PostgreSQL doesn't support AFTER clause
        return "ALTER TABLE $tableName ADD COLUMN $columnName $type";
    }

    /**
     * Builds an ALTER TABLE statement to change a column's data type.
     *
     * @param string $tableName The name of the table containing the column.
     * @param string $columnName The name of the column to modify.
     * @param string $newType The new SQL type declaration for the column (e.g. "VARCHAR(255)").
     * @return string The SQL ALTER TABLE statement that changes the column's type.
     */
    public function modifyColumn(string $tableName, string $columnName, string $newType): string
    {
        return "ALTER TABLE $tableName ALTER COLUMN $columnName TYPE $newType";
    }

    /****
     * Builds a PostgreSQL CREATE INDEX statement for the given table and columns.
     *
     * @param string $indexName The name of the index to create.
     * @param string $tableName The table on which to create the index.
     * @param string[] $columns  An ordered list of column names to include in the index.
     * @param bool $ifNotExists When true, includes the IF NOT EXISTS clause to avoid error if the index already exists.
     * @return string The SQL CREATE INDEX statement.
     */
    public function createIndex(string $indexName, string $tableName, array $columns, bool $ifNotExists = false): string
    {
        $columnList = implode(', ', $columns);
        $exists = $ifNotExists ? 'IF NOT EXISTS ' : '';
        return "CREATE INDEX {$exists}$indexName ON $tableName ($columnList)";
    }

    /**
     * Build a PostgreSQL DROP INDEX statement for the given index.
     *
     * @param string $indexName The name of the index to drop.
     * @param string $tableName The table name (ignored for PostgreSQL; present for interface compatibility).
     * @return string The DROP INDEX SQL statement.
     */
    public function dropIndex(string $indexName, string $tableName): string
    {
        return "DROP INDEX $indexName";
    }

    /**
     * Indicates whether the dialect supports column positioning (for example an `AFTER` clause) in DDL statements.
     *
     * @return bool `true` if column positioning is supported, `false` otherwise.
     */
    public function supportsColumnPositioning(): bool
    {
        return false;
    }

    /**
     * Quote an SQL identifier for use in PostgreSQL statements.
     *
     * @param string $identifier The identifier to quote (e.g., table or column name).
     * @return string The identifier wrapped in double quotes with any embedded double quotes escaped by doubling.
     */
    public function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}