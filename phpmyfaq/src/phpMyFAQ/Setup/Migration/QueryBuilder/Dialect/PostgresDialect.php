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
    public function getType(): string
    {
        return 'pgsql';
    }

    public function integer(): string
    {
        return 'INTEGER';
    }

    public function bigInteger(): string
    {
        return 'BIGINT';
    }

    public function smallInteger(): string
    {
        return 'SMALLINT';
    }

    public function varchar(int $length): string
    {
        return "VARCHAR($length)";
    }

    public function text(): string
    {
        return 'TEXT';
    }

    public function boolean(): string
    {
        return 'SMALLINT';
    }

    public function timestamp(): string
    {
        return 'TIMESTAMP';
    }

    public function date(): string
    {
        return 'DATE';
    }

    public function char(int $length): string
    {
        return "CHAR($length)";
    }

    public function currentTimestamp(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    public function currentDate(): string
    {
        return 'CURRENT_DATE';
    }

    public function autoIncrement(string $columnName): string
    {
        return "$columnName SERIAL NOT NULL";
    }

    public function createTablePrefix(string $tableName, bool $ifNotExists = false): string
    {
        $exists = $ifNotExists ? 'IF NOT EXISTS ' : '';
        return "CREATE TABLE {$exists}{$tableName}";
    }

    public function createTableSuffix(): string
    {
        return '';
    }

    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string
    {
        // PostgreSQL doesn't support AFTER clause
        return "ALTER TABLE $tableName ADD COLUMN $columnName $type";
    }

    public function modifyColumn(string $tableName, string $columnName, string $newType): string
    {
        return "ALTER TABLE $tableName ALTER COLUMN $columnName TYPE $newType";
    }

    public function createIndex(string $indexName, string $tableName, array $columns, bool $ifNotExists = false): string
    {
        $columnList = implode(', ', $columns);
        $exists = $ifNotExists ? 'IF NOT EXISTS ' : '';
        return "CREATE INDEX {$exists}$indexName ON $tableName ($columnList)";
    }

    public function dropIndex(string $indexName, string $tableName): string
    {
        return "DROP INDEX $indexName";
    }

    public function supportsColumnPositioning(): bool
    {
        return false;
    }

    public function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
