<?php

/**
 * SQL Server specific SQL dialect.
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

class SqlServerDialect implements DialectInterface
{
    /**
     * Provide the dialect identifier for SQL Server.
     *
     * @return string The dialect identifier 'sqlsrv'.
     */
    public function getType(): string
    {
        return 'sqlsrv';
    }

    /**
     * SQL Server column type for a standard integer.
     *
     * @return string The SQL Server type `INT`.
     */
    public function integer(): string
    {
        return 'INT';
    }

    /**
     * Get the SQL Server column type for big integers.
     *
     * @return string The SQL Server data type for 64-bit integers: 'BIGINT'.
     */
    public function bigInteger(): string
    {
        return 'BIGINT';
    }

    /**
     * SQL Server column type for a small integer.
     *
     * @return string The SQL Server type `SMALLINT`.
     */
    public function smallInteger(): string
    {
        return 'SMALLINT';
    }

    /**
     * Generates a Unicode variable-length character type declaration for SQL Server using the specified length.
     *
     * @param int $length The maximum number of characters.
     * @return string The NVARCHAR type declaration (e.g. "NVARCHAR(255)").
     */
    public function varchar(int $length): string
    {
        return "NVARCHAR($length)";
    }

    /**
     * Provides the SQL Server column type for large Unicode text columns.
     *
     * @return string The SQL Server column type for large Unicode text (NVARCHAR(MAX)).
     */
    public function text(): string
    {
        return 'NVARCHAR(MAX)';
    }

    /**
     * SQL Server column type used to store boolean values.
     *
     * @return string The SQL Server type used to represent booleans (e.g. 'TINYINT').
     */
    public function boolean(): string
    {
        return 'TINYINT';
    }

    /**
     * SQL Server column type for timestamp values.
     *
     * @return string The SQL Server type name for timestamp columns (`DATETIME`).
     */
    public function timestamp(): string
    {
        return 'DATETIME';
    }

    /**
     * Provide the SQL Server column type for date values.
     *
     * @return string The SQL Server DATE type.
     */
    public function date(): string
    {
        return 'DATE';
    }

    /**
     * Get the SQL Server fixed-length Unicode character type for a given length.
     *
     * @param int $length The number of characters for the NCHAR type.
     * @return string The SQL Server type declaration `NCHAR(length)`.
     */
    public function char(int $length): string
    {
        return "NCHAR($length)";
    }

    /**
     * Provide the SQL Server expression that yields the current date and time.
     *
     * @return string The SQL expression `GETDATE()` which returns the current date and time in SQL Server.
     */
    public function currentTimestamp(): string
    {
        return 'GETDATE()';
    }

    /**
     * SQL Server expression that yields the current date and time.
     *
     * @return string The SQL expression `GETDATE()` which evaluates to the current date and time.
     */
    public function currentDate(): string
    {
        return 'GETDATE()';
    }

    /**
     * Generate a SQL Server column definition for an auto-incrementing integer column.
     *
     * @param string $columnName The name of the column.
     * @return string The SQL fragment defining the column as `INT IDENTITY(1,1) NOT NULL`.
     */
    public function autoIncrement(string $columnName): string
    {
        return "$columnName INT IDENTITY(1,1) NOT NULL";
    }

    / **
     * Generate the SQL prefix used to create a table in SQL Server.
     *
     * @param string $tableName The name of the table to create.
     * @param bool $ifNotExists If true, prepend a conditional existence check using `sysobjects` so the table is created only when it does not already exist.
     * @return string The SQL statement prefix for creating the specified table; when `$ifNotExists` is true the prefix includes an `IF NOT EXISTS` check.
     */
    public function createTablePrefix(string $tableName, bool $ifNotExists = false): string
    {
        if ($ifNotExists) {
            return (
                "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='$tableName' AND xtype='U') "
                . "CREATE TABLE $tableName"
            );
        }
        return "CREATE TABLE $tableName";
    }

    /**
     * SQL Server-specific suffix appended after a CREATE TABLE statement.
     *
     * @return string An empty string indicating no suffix is appended.
     */
    public function createTableSuffix(): string
    {
        return '';
    }

    /**
     * Generate an ALTER TABLE statement to add a column to a SQL Server table.
     *
     * @param string $tableName The target table name.
     * @param string $columnName The name of the column to add.
     * @param string $type The column type definition (for example, 'INT' or 'NVARCHAR(255)').
     * @param string|null $after Ignored — provided for interface compatibility; SQL Server does not support column positioning.
     * @return string The SQL Server `ALTER TABLE ... ADD ...` statement for adding the column.
     */
    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string
    {
        // SQL Server doesn't support AFTER clause, and uses different syntax
        return "ALTER TABLE $tableName ADD $columnName $type";
    }

    /**
     * Generate SQL to alter a column's type in a table.
     *
     * @param string $tableName The name of the table containing the column.
     * @param string $columnName The name of the column to modify.
     * @param string $newType The new SQL type and modifiers for the column (e.g., `NVARCHAR(255) NOT NULL`).
     * @return string The ALTER TABLE statement that changes the column's type to the specified definition.
     */
    public function modifyColumn(string $tableName, string $columnName, string $newType): string
    {
        return "ALTER TABLE $tableName ALTER COLUMN $columnName $newType";
    }

    /**
     * Builds a SQL statement to create an index on a table.
     *
     * @param string $indexName The name of the index to create.
     * @param string $tableName The table on which to create the index.
     * @param string[] $columns The list of column names to include in the index, in order.
     * @param bool $ifNotExists When true, include a conditional existence check so the index is only created if it does not already exist.
     * @return string The SQL statement that creates the index; if `$ifNotExists` is true the statement includes a preceding `IF NOT EXISTS` check.
     */
    public function createIndex(string $indexName, string $tableName, array $columns, bool $ifNotExists = false): string
    {
        $columnList = implode(', ', $columns);
        if ($ifNotExists) {
            return (
                "IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = '$indexName') "
                . "CREATE INDEX $indexName ON $tableName ($columnList)"
            );
        }
        return "CREATE INDEX $indexName ON $tableName ($columnList)";
    }

    /**
     * Generates a SQL Server statement to drop an index from a table.
     *
     * @param string $indexName The name of the index to drop.
     * @param string $tableName The table containing the index.
     * @return string The SQL statement that drops the specified index on the given table.
     */
    public function dropIndex(string $indexName, string $tableName): string
    {
        return "DROP INDEX $indexName ON $tableName";
    }

    /**
     * Indicates whether this dialect supports specifying column position when adding or modifying columns.
     *
     * @return bool `true` if column positioning is supported, `false` otherwise.
     */
    public function supportsColumnPositioning(): bool
    {
        return false;
    }

    / **
     * Quote an SQL Server identifier with square brackets, escaping any closing brackets.
     *
     * @param string $identifier The identifier to quote; any `]` characters will be escaped by doubling them.
     * @return string The identifier wrapped in `[` and `]` with internal `]` characters replaced by `]]`.
     */
    public function quoteIdentifier(string $identifier): string
    {
        return '[' . str_replace(']', ']]', $identifier) . ']';
    }
}