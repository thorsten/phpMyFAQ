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
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-07-06
 */

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Search\Database\DatabaseInterface;

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
     * @param string[] $searchHandler Array with search handlers, e.g. array('database' => 'mysqli')
     */
    public static function create(Configuration $config, array $searchHandler): DatabaseInterface
    {
        $searchClass = sprintf(
            '\phpMyFAQ\Search\%s\%s',
            ucfirst(key($searchHandler)),
            ucfirst(current($searchHandler))
        );

        return new $searchClass($config);
    }
}
