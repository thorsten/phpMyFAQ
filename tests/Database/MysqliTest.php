<?php
/**
 * Mysql connector.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @package   PMF_Tests
 * @author Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2010 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-01-03
 */

/**
 * Class Database_MysqliTest
 */
abstract class Database_MysqliTest extends Database_AbstractTest
{
    /**
     * Return the MySQL server settings.
     *
     * @return array
     */
    protected function getDbData()
    {
        return [
            'server'   => 'localhost',
            'user'     => '-',
            'password' => '-',
            'db'       => 'phpmyfaqtest',
            'prefix'   => '',
            'type'     => 'mysqli',
            'port'     => 3306
        ];
    }
}
