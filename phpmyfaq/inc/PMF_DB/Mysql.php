<?php
/**
 * The PMF_DB_Mysql class provides methods and functions for a MySQL 4.0.x
 * and higher database.
 * 
 * PHP Version 5.2.3
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
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
     * @return boolean TRUE, if connected, otherwise false
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
        $this->sqllog .= pmf_debug($query);
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
      * @return void
      */
    public function error()
    {
        return mysql_error();
    }

    /**
     * Returns the client version string.
     * 
     * @return void
     */
    public function clientVersion()
    {
        return mysql_get_client_info();
    }

    /**
     * Returns the server version string.
     *
     * @return void
     */
    public function serverVersion()
    {
        return mysql_get_server_info();
    }

    /**
     * Returns an array with all table names
     *
     * @param  string $prefix Table prefix
     * @return void
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
    }

    /**
     * Move internal result pointer
     *
     * Moves the pointer within the query result to a specified location, or
     * to the beginning if nothing is specified.
     *
     * @param resource $result    Resultset
     * @param integer  $rowNumber Row number
     * 
     * @return boolean
     */
    public function resultSeek($result, $rowNumber)
    {
        return mysql_data_seek($result, $rowNumber);
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
