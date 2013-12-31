<?php
/**
 * Mysql connector.
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-03
 */

require_once 'AbstractTest.php';

/**
 * Database_MysqlTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2014 phpMyFAQ Team
 * @since     2009-05-16
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
        return array(
            "server"   => 'localhost',
            "user"     => '-',
            "password" => '-',
            "db"       => 'phpmyfaqtest',
            "prefix"   => '',
            "type"     => 'mysqli');
    }
}
