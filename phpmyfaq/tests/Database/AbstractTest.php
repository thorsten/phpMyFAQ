<?php
/**
 * Abstract class for manage different databases.
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

require_once 'PHPUnit/Extensions/Database/TestCase.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/Db.php';

define('SQLPREFIX', '');

/**
 * Database_AbstractTest
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2013 phpMyFAQ Team
 * @since     2009-05-16
 */
abstract class Database_AbstractTest extends PHPUnit_Extensions_Database_TestCase
{
    /**
     * Contain the db connector used in phpMyFAQ.
     *
     * @var PMF_Db
     */
    public $db = null;
    
    /**
     * Database server configuration.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Each Database class must return the connection settings.
     */
    abstract protected function getDbData();

    /**
     * Make the connection in 2 times.
     * 
     * 1. Connect using the phpMyFAQ API for use internally.
     * 2. Connect using the PHPUnit unit for run the tests.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        // Get the settings.
        $this->data = $this->getDbData();

        // phpMyFAQ connection
        $db = PMF_Db::dbSelect($this->data["type"]);
        $db->connect($this->data["server"], $this->data["user"], $this->data["password"], $this->data["db"]);
        $this->db = $db;

        // PHPunit connection
        $pdo = new PDO($this->data["type"] . ":host=" . $this->data["server"]
            . ";dbname=" . $this->data["db"], $this->data["user"], $this->data["password"]);

        return $this->createDefaultDBConnection($pdo, $this->data["db"]);
    }
}
