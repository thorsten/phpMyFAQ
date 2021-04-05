<?php

/**
 * Factory class for phpMyFAQ search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-07-06
 */

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Search\Database\Mysqli;
use phpMyFAQ\Search\Database\Pgsql;
use phpMyFAQ\Search\Database\Sqlite3;
use phpMyFAQ\Search\Database\Sqlsrv;

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
     * @param Configuration $config
     * @param string[] $searchHandler Array with search handlers, e.g. array('database' => 'mysqli')
     *
     * @return Mysqli|Pgsql|Sqlite3|Sqlsrv
     */
    public static function create(Configuration $config, array $searchHandler)
    {
        $searchClass = sprintf(
            '\phpMyFAQ\Search\%s\%s',
            ucfirst(key($searchHandler)),
            ucfirst(current($searchHandler))
        );

        return new $searchClass($config);
    }
}
