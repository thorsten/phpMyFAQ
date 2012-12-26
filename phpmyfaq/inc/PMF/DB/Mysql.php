<?php
/**
 * The PMF_DB_Mysql class provides methods and functions for MySQL 5.0.x,
 * 5.1.x, and 5.5.x databases.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Mysql
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2003-02-24
 */
class PMF_DB_Mysql implements PMF_DB_Driver 
{
    /**
     * The connection object
     *
     * @var mixed
     */
    private $conn = false;

    /**
     * The query log string
     *
     * @var string
     */
    private $sqllog = '';

    /**
     * Tables
     *
     * @var array
     */
    public $tableNames = array();

    /**
     * Connects to the database
     *
     * @param string $host     MySQL Hostname
     * @param string $username MySQL Username
     * @param string $password MySQL Password
     * @param string $db_name  MySQL Database name
     *
     * @return boolean
     */
    public function connect ($host, $user, $password, $db)
    {
        $this->conn = mysql_connect($host, $user, $password);
        if (empty($db) || $this->conn == false) {
            PMF_Db::errorPage($this->error());
            die();
        }
        
        mysql_set_charset('utf8', $this->conn);
        
        return mysql_select_db($db, $this->conn);
    }

    /**
     * This function sends a query to the database.
     *
     * @param  string $query Query string
     * @return mixed
     */
    public function query($query)
    {
        if (DEBUG) {
            $this->sqllog .= PMF_Utils::debug($query);
        }
        $result = mysql_query($query, $this->conn);
        if (!$result) {
            $this->sqllog .= $this->error();
        }
        return $result;
    }

    /**
     * Escapes a string for use in a query
     *
     * @param  string $string String
     * @return string
     */
    public function escape($string)
    {
        return mysql_real_escape_string($string, $this->conn);
    }

    /**
     * Fetch a result row as an object
     *
     * @param  mixed $result Resultset
     * @return mixed
     */
    public function fetchObject($result)
    {
        return mysql_fetch_object($result);
    }

    /**
     * Fetch a result row as an object
     *
     * @param  mixed $result Resultset
     * @return mixed
     */
    public function fetchArray($result)
    {
        return mysql_fetch_assoc($result);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Mysql
     */
    public function fetchAll($result)
    {
        $ret = array();
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }
        
        while ($row = $this->fetchObject($result)) {
            $ret[] = $row;
        }
        
        return $ret;
    }
    
    /**
     * Number of rows in a result
     *
     * @param  mixed   $result Resultset
     * @return integer
     */
    public function numRows($result)
    {
        return mysql_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @return string
     */
    public function log()
    {
        return $this->sqllog;
    }

    /**
     * Returns the table status.
     *
     * @return array
     */
    public function getTableStatus()
    {
        $arr = array();
        $result = $this->query("SHOW TABLE STATUS");
        while ($row = $this->fetchArray($result)) {
            $arr[$row["Name"]] = $row["Rows"];
        }
        return $arr;
    }

    /**
     * Returns the next ID of a table. This function is a replacement for MySQL's 
     * auto-increment so that we don't need it anymore.
     *
     * @param  string  $table The name of the table
     * @param  string  $id    The name of the ID column
     * @return integer
     */
    public function nextId($table, $id)
    {
        $select = sprintf("
         SELECT
             MAX(%s) AS current_id
         FROM
             %s",
           $id,
           $table);
           
        $result    = $this->query($select);
        $currentId = mysql_result($result, 0, 'current_id');

        return ($currentId + 1);
    }

     /**
      * Returns the error string.
      *
      * @return string
      */
    public function error()
    {
        return mysql_error();
    }

    /**
     * Returns the client version string.
     *
     * @return string
     */
    public function clientVersion()
    {
        return mysql_get_client_info();
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        return mysql_get_server_info();
    }

    /**
     * Returns an array with all table names
     *
     * @param  string $prefix Table prefix
     *
     * @return array
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix . 'faquser';

        $result = $this->query('SHOW TABLES'.(('' == $prefix) ? '' : ' LIKE \''.$prefix.'%\''));
        while ($row = $this->fetchObject($result)) {
            foreach ($row as $tableName) {
                if (!in_array($tableName, $this->tableNames)) {
                    $this->tableNames[] = $tableName;
                }
            }
        }

        return $this->tableNames;
    }

    /**
     * Closes the connection to the database.
     *
     * @return void
     */
    public function close()
    {
        if (is_resource($this->conn)) {
            mysql_close($this->conn);
        }
    }
}
