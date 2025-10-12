<?php

declare(strict_types=1);

/**
 * Factory class for phpMyFAQ search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-07-06
 */

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;

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
        $type = current($searchHandler);
        $connector = ucfirst((string) key($searchHandler));
        if (str_starts_with(current($searchHandler), 'pdo_')) {
            $searchClass = sprintf('\phpMyFAQ\Search\%s\Pdo%s', $connector, ucfirst(substr($type, 4)));
        } else {
            $searchClass = sprintf('\phpMyFAQ\Search\%s\%s', $connector, ucfirst($type));
        }

        return new $searchClass($configuration);
    }
}
