<?php

/**
 * SQLite specific SQL dialect.
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

class SqliteDialect implements DialectInterface
{
    /**
     * Get the dialect type identifier.
     *
     * @return string The dialect type identifier "sqlite3".
     */
    public function getType(): string
    {
        return 'sqlite3';
    }

    /**
     * Provide the SQLite column type for integers.
     *
     * @return string The SQL type `INTEGER`.
     */
    public function integer(): string
    {
        return 'INTEGER';
    }

    /**
     * Maps a migration "bigInteger" column to SQLite's integer type.
     *
     * @return string The SQL type name `INTEGER`.
     */
    public function bigInteger(): string
    {
        return 'INTEGER';
    }

    /**
     * Provide the SQL column type used for a small integer in SQLite.
     *
     * @return string The SQL type `INTEGER`.
     */
    public function smallInteger(): string
    {
        return 'INTEGER';
    }

    /**
     * Create a VARCHAR type declaration with the given maximum length.
     *
     * @param int $length The maximum character length for the column.
     * @return string The SQL type declaration, e.g. "VARCHAR(255)".
     */
    public function varchar(int $length): string
    {
        return "VARCHAR($length)";
    }

    /**
     * SQLite column type used for storing text.
     *
     * @return string The SQLite type name "TEXT".
     */
    public function text(): string
    {
        return 'TEXT';
    }

    /**
     * Maps a boolean column to SQLite's storage type for boolean values.
     *
     * @return string The SQLite type name 'INTEGER' used to store boolean values (0 or 1).
     */
    public function boolean(): string
    {
        return 'INTEGER';
    }

    /**
     * Provide the SQL type name for timestamp columns in SQLite.
     *
     * @return string The SQL type name used for timestamp columns (`DATETIME`).
     */
    public function timestamp(): string
    {
        return 'DATETIME';
    }

    /**
     * Provide the SQL column type for date values in SQLite.
     *
     * @return string The SQL type name 'DATE'.
     */
    public function date(): string
    {
        return 'DATE';
    }

    /**
     * Produce a SQLite CHAR type declaration for the given length.
     *
     * @param int $length The number of characters for the CHAR type.
     * @return string The SQL type declaration, e.g. "CHAR(10)".
     */
    public function char(int $length): string
    {
        return "CHAR($length)";
    }

    /**
     * SQLite expression that yields the current timestamp.
     *
     * @return string The SQL expression `CURRENT_TIMESTAMP`.
     */
    public function currentTimestamp(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * SQLite expression that yields the current date in YYYY-MM-DD format.
     *
     * @return string The SQL expression "(date('now'))" which evaluates to the current UTC date in YYYY-MM-DD format.
     */
    public function currentDate(): string
    {
        return "(date('now'))";
    }

    /**
     * Build a SQLite column definition that makes the given column an autoincrementing primary key.
     *
     * @param string $columnName The name of the column to define as an autoincrementing primary key.
     * @return string The column definition, e.g. "id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT".
     */
    public function autoIncrement(string $columnName): string
    {
        return "$columnName INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT";
    }

    /**
     * Build the SQL prefix for creating a table in SQLite.
     *
     * @param string $tableName The name of the table.
     * @param bool $ifNotExists If true, include the `IF NOT EXISTS` clause.
     * @return string The `CREATE TABLE` statement prefix for the specified table, optionally containing `IF NOT EXISTS`.
     */
    public function createTablePrefix(string $tableName, bool $ifNotExists = false): string
    {
        $exists = $ifNotExists ? 'IF NOT EXISTS ' : '';
        return "CREATE TABLE {$exists}{$tableName}";
    }

    /**
     * Provides the SQL suffix appended to CREATE TABLE statements for this dialect.
     *
     * SQLite does not require a table suffix.
     *
     * @return string An empty string (no suffix).
     */
    public function createTableSuffix(): string
    {
        return '';
    }

    /**
     * Generate an ALTER TABLE statement to add a new column to a SQLite table.
     *
     * The optional `$after` parameter is ignored because SQLite does not support column positioning.
     *
     * @param string $tableName The name of the table to alter.
     * @param string $columnName The name of the column to add.
     * @param string $type The column type and any constraints (e.g., "INTEGER NOT NULL").
     * @param string|null $after Ignored for SQLite; kept for API compatibility.
     * @return string The SQL statement to add the column (e.g., "ALTER TABLE <table> ADD COLUMN <column> <type>").
     */
    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string
    {
        // SQLite doesn't support AFTER clause
        return "ALTER TABLE $tableName ADD COLUMN $columnName $type";
    }

    /**
     * Indicate that a column's type cannot be modified directly in SQLite.
     *
     * This method always throws a RuntimeException because SQLite does not support altering column types in place;
     * callers must rebuild the table to effect column changes.
     *
     * @param string $tableName The name of the table containing the column.
     * @param string $columnName The name of the column to modify.
     * @param string $newType The desired new column type definition.
     * @throws \RuntimeException Always thrown to signal that column modification is not supported in SQLite.
     */
    public function modifyColumn(string $tableName, string $columnName, string $newType): string
    {
        // SQLite doesn't support ALTER COLUMN directly
        // This would require a table rebuild, which is handled separately
        throw new \RuntimeException('SQLite does not support modifying columns. Use table rebuild pattern.');
    }

    /**
     * Builds a CREATE INDEX SQL statement for the specified table and columns.
     *
     * @param string $indexName The name of the index to create.
     * @param string $tableName The table on which to create the index.
     * @param string[] $columns Ordered list of column names or identifiers to include in the index.
     * @param bool $ifNotExists When true, includes `IF NOT EXISTS` to avoid an error if the index already exists.
     * @return string The SQL CREATE INDEX statement. If `$ifNotExists` is true, the statement includes `IF NOT EXISTS`.
     */
    public function createIndex(string $indexName, string $tableName, array $columns, bool $ifNotExists = false): string
    {
        $columnList = implode(', ', $columns);
        $exists = $ifNotExists ? 'IF NOT EXISTS ' : '';
        return "CREATE INDEX {$exists}$indexName ON $tableName ($columnList)";
    }

    /**
     * Generate SQL to drop an index if it exists.
     *
     * @param string $indexName The name of the index to drop.
     * @param string $tableName The table name (unused; present for interface compatibility).
     * @return string The DROP INDEX statement with IF EXISTS for the specified index.
     */
    public function dropIndex(string $indexName, string $tableName): string
    {
        return "DROP INDEX IF EXISTS $indexName";
    }

    /**
     * Indicates whether this SQL dialect supports specifying column position when altering a table.
     *
     * @return bool `true` if column positioning is supported, `false` otherwise.
     */
    public function supportsColumnPositioning(): bool
    {
        return false;
    }

    /**
     * Quote an SQL identifier for SQLite by wrapping it in double quotes and escaping any embedded double quotes.
     *
     * @param string $identifier The identifier to quote (e.g., table or column name).
     * @return string The identifier wrapped in double quotes with internal double quotes doubled.
     */
    public function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}