<?php
/**
 * Abstract class for manage different databases.
 *
 * 
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
 * @link      https://www.phpmyfaq.de
 * @since     2010-01-03
 */

/**
 * Database_AbstractTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @copyright 2009-2019 phpMyFAQ Team
 * @since     2009-05-16
 */
abstract class Database_AbstractTest extends PHPUnit_Extensions_Database_TestCase
{
    /**
     * Contain the db connector used in phpMyFAQ.
     *
     * @var phpMyFAQ\Db
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
        $db = phpMyFAQ\Db::factory($this->data["type"]);
        $db->connect($this->data["server"], $this->data["user"], $this->data["password"], $this->data["db"]);
        $this->db = $db;

        // PHPunit connection
        $pdo = new PDO($this->data["type"] . ":host=" . $this->data["server"]
            . ";dbname=" . $this->data["db"], $this->data["user"], $this->data["password"]);

        return $this->createDefaultDBConnection($pdo, $this->data["db"]);
    }
}
