<?php

/**
 * Factory class for phpMyFAQ search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-07-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;

/**
 * Class SearchFactory
 *
 * @package phpMyFAQ\Search
 */
class SearchFactory
{
    /**
     * Factory for generating search instances.
     *
     * @param string[] $searchHandler Array with search handlers, e.g., array ('database' => 'mysqli')
     */
    public static function create(Configuration $configuration, array $searchHandler): SearchDatabase
    {
        $type = (string) current($searchHandler);
        $connector = ucfirst((string) key($searchHandler));
        if ($type === '') {
            $type = self::resolveDatabaseType($configuration->getDb());
        }

        if (str_starts_with($type, 'pdo_')) {
            $searchClass = sprintf('\phpMyFAQ\Search\%s\Pdo%s', $connector, ucfirst(substr(string: $type, offset: 4)));
            return new $searchClass($configuration);
        }

        $searchClass = sprintf('\phpMyFAQ\Search\%s\%s', $connector, ucfirst($type));
        return new $searchClass($configuration);
    }

    private static function resolveDatabaseType(DatabaseDriver $databaseDriver): string
    {
        $classNameParts = explode('\\', $databaseDriver::class);
        $driverClass = strtolower((string) end($classNameParts));

        return match ($driverClass) {
            'pdomysql' => 'pdo_mysql',
            'pdopgsql' => 'pdo_pgsql',
            'pdosqlite' => 'pdo_sqlite',
            'pdosqlsrv' => 'pdo_sqlsrv',
            default => $driverClass,
        };
    }
}
