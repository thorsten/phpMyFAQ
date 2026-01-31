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
    public function getType(): string
    {
        return 'sqlsrv';
    }

    public function integer(): string
    {
        return 'INT';
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
        return "NVARCHAR($length)";
    }

    public function text(): string
    {
        return 'NVARCHAR(MAX)';
    }

    public function boolean(): string
    {
        return 'TINYINT';
    }

    public function timestamp(): string
    {
        return 'DATETIME';
    }

    public function date(): string
    {
        return 'DATE';
    }

    public function char(int $length): string
    {
        return "NCHAR($length)";
    }

    public function currentTimestamp(): string
    {
        return 'GETDATE()';
    }

    public function currentDate(): string
    {
        return 'GETDATE()';
    }

    public function autoIncrement(string $columnName): string
    {
        return "$columnName INT IDENTITY(1,1) NOT NULL";
    }

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

    public function createTableSuffix(): string
    {
        return '';
    }

    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string
    {
        // SQL Server doesn't support AFTER clause, and uses different syntax
        return "ALTER TABLE $tableName ADD $columnName $type";
    }

    public function modifyColumn(string $tableName, string $columnName, string $newType): string
    {
        return "ALTER TABLE $tableName ALTER COLUMN $columnName $newType";
    }

    public function createIndex(string $indexName, string $tableName, array $columns, bool $ifNotExists = false): string
    {
        $columnList = implode(', ', $columns);
        if ($ifNotExists) {
            return (
                "IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = '$indexName'"
                . " AND object_id = OBJECT_ID(N'$tableName')) "
                . "CREATE INDEX $indexName ON $tableName ($columnList)"
            );
        }
        return "CREATE INDEX $indexName ON $tableName ($columnList)";
    }

    public function dropIndex(string $indexName, string $tableName): string
    {
        return "DROP INDEX $indexName ON $tableName";
    }

    public function supportsColumnPositioning(): bool
    {
        return false;
    }

    public function supportsCombinedAlter(): bool
    {
        return false;
    }

    public function quoteIdentifier(string $identifier): string
    {
        return '[' . str_replace(']', ']]', $identifier) . ']';
    }
}
