<?php
/**
 * Postgress connector.
 *
 * PHP Version 5.5
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

/**
 * Database_PostgressTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2017 phpMyFAQ Team
 * @since     2009-05-16
 */
abstract class Database_PostgressTest extends Database_AbstractTest 
{
    /**
     * Return the Postgress server settings.
     *
     * @return array
     */    
    protected function getDbData()
    {
        return array(
            "server"   => 'localhost',
            "user"     => 'postgres',
            "password" => '-',
            "db"       => 'phpmyfaqtest',
            "prefix"   => '',
            "type"     => 'pgsql');
    }
}
