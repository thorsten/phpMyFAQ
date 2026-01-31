<?php

/**
 * Factory for creating database-specific dialect instances.
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
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\MysqlDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\PostgresDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqlServerDialect;

class DialectFactory
{
    /**
     * Creates the appropriate dialect for the current database type.
     */
    public static function create(): DialectInterface
    {
        return self::createForType(Database::getType());
    }

    /**
     * Creates a dialect for the specified database type.
     */
    public static function createForType(string $dbType): DialectInterface
    {
        return match ($dbType) {
            'mysqli', 'pdo_mysql' => new MysqlDialect(),
            'pgsql', 'pdo_pgsql' => new PostgresDialect(),
            'sqlite3', 'pdo_sqlite' => new SqliteDialect(),
            'sqlsrv', 'pdo_sqlsrv' => new SqlServerDialect(),
            default => throw new \InvalidArgumentException("Unsupported database type: $dbType"),
        };
    }
}
