<?php

/**
 * Interface for phpMyFAQ database-dependent search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-12-23
 */

namespace phpMyFAQ\Search\Database;

use Exception;

interface DatabaseInterface
{
    /**
     * Prepares the search and executes it.
     *
     * @param  string $searchTerm Search term
     * @throws Exception
     */
    public function search(string $searchTerm): mixed;
}
