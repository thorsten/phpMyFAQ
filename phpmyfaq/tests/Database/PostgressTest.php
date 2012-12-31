<?php
/**
 * Postgress connector.
 * 
 * PHP Version 5.2
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-03
 */

require_once 'AbstractTest.php';

/**
 * Database_PostgressTest
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2013 phpMyFAQ Team
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
