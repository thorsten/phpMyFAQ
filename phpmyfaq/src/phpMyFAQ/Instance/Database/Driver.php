<?php

/**
 * The phpMyFAQ instances database interface.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-04-06
 */

namespace phpMyFAQ\Instance\Database;

/**
 * Interface Driver
 *
 * @package phpMyFAQ\Instance\Database
 */
interface Driver
{
    /**
     * Executes all CREATE TABLE and CREATE INDEX statements.
     *
     *
     */
    public function createTables(string $prefix = ''): bool;
}
