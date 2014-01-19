<?php
/**
 * The PMF_DB_Pdo_Mysql class is a PDO wrapper class for MySQL
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2014-01-19
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Pdo_mysql
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2014-01-19
 */
class PMF_DB_Pdo_Mysql extends PMF_DB_Pdo implements PMF_DB_Driver
{
    /**
     * Connects to the database.
     *
     * @param string $host     Hostname
     * @param string $user     Username
     * @param string $password Password
     * @param string $database Database name
     *
     * @return  boolean true, if connected, otherwise false
     */
    public function connect($host, $user, $password, $database = '')
    {
        try {
            $this->conn = new PDO('mysql:host=' . $host . ';dbname=' . $database . ';charset=UTF8', $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            PMF_Db::errorPage('Database connection failed: ' . $e->getMessage());
        }



        return true;
    }
}
