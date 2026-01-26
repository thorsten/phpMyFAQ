<?php

/**
 * Contract for database-specific SQL dialects.
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

interface DialectInterface
{
    /**
 * Get the database dialect identifier.
 *
 * @return string The database type identifier, e.g. "mysqli" or "pgsql".
 */
    public function getType(): string;

    /**
 * Get the SQL type used for an INTEGER column.
 *
 * @return string The SQL type definition for an INTEGER column (for example, "INT").
 */
    public function integer(): string;

    /**
 * Get the SQL type definition for a BIGINT column.
 *
 * @return string The SQL column type used for big integers (e.g., "BIGINT").
 */
    public function bigInteger(): string;

    /**
 * Get the SQL type for a SMALLINT column.
 *
 * @return string The SQL type for a SMALLINT column.
 */
    public function smallInteger(): string;

    /**
 * Produce the SQL type definition for a VARCHAR column of the specified length.
 *
 * @param int $length The maximum character length of the VARCHAR column.
 * @return string The SQL type string for a VARCHAR column of the given length.
 */
    public function varchar(int $length): string;

    /**
 * Get the SQL type for a TEXT column.
 *
 * @return string The SQL type string used for a TEXT column.
 */
    public function text(): string;

    /**
 * Get the SQL column type used to store boolean values.
 *
 * @return string The SQL type declaration for a boolean column (for example `BOOLEAN` or `TINYINT(1)`).
 */
    public function boolean(): string;

    /**
 * Get the SQL column type used for timestamp/datetime values.
 *
 * @return string The SQL type for a TIMESTAMP or DATETIME column.
 */
    public function timestamp(): string;

    /**
 * Provide the SQL type used for DATE columns.
 *
 * @return string The SQL type definition for a DATE column.
 */
    public function date(): string;

    / **
 * Get the SQL declaration for a CHAR column of the specified length.
 *
 * @param int $length The number of characters for the CHAR column.
 * @return string The SQL type declaration for a CHAR column with the given length.
 */
    public function char(int $length): string;

    /**
 * Provide the SQL expression used to represent the current timestamp.
 *
 * @return string The SQL expression or default value to use for a column's current timestamp (e.g. `CURRENT_TIMESTAMP` or a dialect-specific function).
 */
    public function currentTimestamp(): string;

    /**
 * Get the SQL expression that yields the current date for this dialect.
 *
 * @return string The SQL expression or default value used to produce the current date (for example `CURRENT_DATE`).
 */
    public function currentDate(): string;

    /**
 * Provide the SQL fragment that declares the specified column as auto-incrementing.
 *
 * @param string $columnName The column name to mark as auto-increment (unquoted identifier).
 * @return string The SQL fragment used to declare the column as auto-increment.
 */
    public function autoIncrement(string $columnName): string;

    /**
 * Build the initial CREATE TABLE statement for the given table, optionally including an IF NOT EXISTS clause.
 *
 * @param string $tableName The table name to create (identifier will be quoted by the dialect implementation as needed).
 * @param bool $ifNotExists When true, include an IF NOT EXISTS clause to avoid error if the table already exists.
 * @return string The SQL prefix for a CREATE TABLE statement for the specified table.
 */
    public function createTablePrefix(string $tableName, bool $ifNotExists = false): string;

    /**
 * Provide the CREATE TABLE suffix containing engine, charset, and other table-level options for the dialect.
 *
 * @return string The SQL fragment to append to a CREATE TABLE statement (e.g., engine, charset, collation settings), or an empty string if the dialect does not require a suffix.
 */
    public function createTableSuffix(): string;

    /**
 * Build an ALTER TABLE fragment to add a column to a table.
 *
 * @param string $tableName The name of the table to alter.
 * @param string $columnName The name of the column to add.
 * @param string $type The column type definition (including length, nullability, defaults, etc.).
 * @param string|null $after Optional column name after which the new column should be placed; null to omit positioning.
 * @return string The complete ALTER TABLE ADD COLUMN SQL statement for the given table and column.
 */
    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string;

    /**
 * Generate an ALTER TABLE statement that changes the type/definition of a specified column.
 *
 * @param string $tableName The target table name.
 * @param string $columnName The column to modify.
 * @param string $newType The full SQL type/definition to apply to the column (including length, nullability, defaults, etc., as required by the dialect).
 * @return string The SQL statement that alters the table to modify the specified column to the given type.
 */
    public function modifyColumn(string $tableName, string $columnName, string $newType): string;

    /**
     * Builds a CREATE INDEX statement for the given table and columns.
     *
     * @param string $indexName The name of the index to create.
     * @param string $tableName The table on which the index will be created.
     * @param string[] $columns Ordered list of column identifiers to include in the index.
     * @param bool $ifNotExists Whether to include an IF NOT EXISTS clause when supported.
     * @return string The CREATE INDEX SQL statement.
     */
    public function createIndex(
        string $indexName,
        string $tableName,
        array $columns,
        bool $ifNotExists = false,
    ): string;

    /**
 * Builds a DROP INDEX statement for the specified index on a table.
 *
 * @param string $indexName The index name.
 * @param string $tableName The table name.
 * @return string The DROP INDEX SQL statement targeting the given table and index.
 */
    public function dropIndex(string $indexName, string $tableName): string;

    /**
 * Indicates whether the dialect supports specifying a column position with an AFTER clause when adding a column.
 *
 * @return bool `true` if the dialect supports an AFTER clause for column positioning, `false` otherwise.
 */
    public function supportsColumnPositioning(): bool;

    /**
 * Quote a SQL identifier (such as a table or column name) for safe use in queries.
 *
 * @param string $identifier The identifier to quote; may include a schema or table prefix.
 * @return string The quoted identifier. */
    public function quoteIdentifier(string $identifier): string;
}