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
use RuntimeException;

class MysqlDialect implements DialectInterface
{
    public function getType(): string
    {
        return 'mysqli';
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
        return "VARCHAR($length)";
    }

    public function text(): string
    {
        return 'TEXT';
    }

    public function longText(): string
    {
        return 'LONGTEXT';
    }

    public function blob(): string
    {
        return 'BLOB';
    }

    public function boolean(): string
    {
        return 'TINYINT(1)';
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
        return 'CURDATE()';
    }

    public function autoIncrement(string $columnName): string
    {
        return "$columnName INT NOT NULL PRIMARY KEY AUTO_INCREMENT";
    }

    public function createTablePrefix(string $tableName, bool $ifNotExists = false): string
    {
        $exists = $ifNotExists ? 'IF NOT EXISTS ' : '';
        return "CREATE TABLE {$exists}{$tableName}";
    }

    public function createTableSuffix(): string
    {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB';
    }

    public function addColumn(string $tableName, string $columnName, string $type, ?string $after = null): string
    {
        $afterClause = $after !== null ? " AFTER $after" : '';
        return "ALTER TABLE $tableName ADD COLUMN $columnName $type$afterClause";
    }

    public function modifyColumn(string $tableName, string $columnName, string $newType): string
    {
        return "ALTER TABLE $tableName MODIFY $columnName $newType";
    }

    public function createIndex(string $indexName, string $tableName, array $columns, bool $ifNotExists = false): string
    {
        if ($ifNotExists) {
            throw new RuntimeException(
                'MySQL/MariaDB does not support IF NOT EXISTS for CREATE INDEX. '
                . 'Check for index existence manually before creating it.',
            );
        }

        $columnList = implode(', ', $columns);
        return "CREATE INDEX $indexName ON $tableName ($columnList)";
    }

    public function dropIndex(string $indexName, string $tableName): string
    {
        return "DROP INDEX $indexName ON $tableName";
    }

    public function supportsColumnPositioning(): bool
    {
        return true;
    }

    public function supportsCombinedAlter(): bool
    {
        return true;
    }

    public function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
