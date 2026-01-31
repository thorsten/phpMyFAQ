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
     * Returns the database type identifier (e.g., 'mysqli', 'pgsql').
     */
    public function getType(): string;

    /**
     * Returns the INTEGER column type.
     */
    public function integer(): string;

    /**
     * Returns the BIGINT column type.
     */
    public function bigInteger(): string;

    /**
     * Returns the SMALLINT column type.
     */
    public function smallInteger(): string;

    /**
     * Returns the VARCHAR column type with length.
     */
    public function varchar(int $length): string;

    /**
     * Returns the TEXT column type.
     */
    public function text(): string;

    /**
     * Returns the BOOLEAN/TINYINT column type.
     */
    public function boolean(): string;

    /**
     * Returns the TIMESTAMP/DATETIME column type.
     */
    public function timestamp(): string;

    /**
     * Returns the DATE column type.
     */
    public function date(): string;

    /**
     * Returns the CHAR column type with length.
     */
    public function char(int $length): string;

    /**
     * Returns the current timestamp function/default.
     */
    public function currentTimestamp(): string;

    /**
     * Returns the current date function/default.
     */
    public function currentDate(): string;

    /**
     * Returns the auto-increment column definition.
     */
    public function autoIncrement(string $columnName): string;

    /**
     * Returns the CREATE TABLE prefix with options.
     */
    public function createTablePrefix(string $tableName, bool $ifNotExists = false): string;

    /**
     * Returns the CREATE TABLE suffix with engine/charset options.
     */
    public function createTableSuffix(): string;

    /**
     * Returns the ALTER TABLE ADD COLUMN syntax.
     */
    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string;

    /**
     * Returns the ALTER TABLE MODIFY/ALTER COLUMN syntax.
     */
    public function modifyColumn(string $tableName, string $columnName, string $newType): string;

    /**
     * Returns the CREATE INDEX syntax.
     */
    public function createIndex(
        string $indexName,
        string $tableName,
        array $columns,
        bool $ifNotExists = false,
    ): string;

    /**
     * Returns the DROP INDEX syntax.
     */
    public function dropIndex(string $indexName, string $tableName): string;

    /**
     * Returns whether AFTER clause is supported in ALTER TABLE ADD COLUMN.
     */
    public function supportsColumnPositioning(): bool;

    /**
     * Returns whether combined ALTER TABLE statements (multiple clauses in one statement) are supported.
     */
    public function supportsCombinedAlter(): bool;

    /**
     * Quotes an identifier (table name, column name).
     */
    public function quoteIdentifier(string $identifier): string;
}
