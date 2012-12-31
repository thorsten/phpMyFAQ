<?php
/**
 * The PMF_DB_Sqlite class provides methods and functions for a SQLite v2
 * database. This class is deprecated for PHP 5.3 and PHP 5.4.
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
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Johannes Schlüter <johannes@php.net>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-06-27
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Sqlite
 *
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Johannes Schlüter <johannes@php.net>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-06-27
 */
class PMF_DB_Sqlite implements PMF_DB_Driver
{
    /**
     * The connection object
     *
     * @var   mixed
     * @see   connect(), query(), dbclose()
     */
    private $conn = false;

    /**
     * The query log string
     *
     * @var   string
     * @see   query()
     */
    private $sqllog = '';

    /**
     * Tables
     *
     * @var     array
     */
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * @param   string
     * @return  boolean
     */
    public function connect($host, $user = false, $passwd = false, $db = false)
    {
        $this->conn = sqlite_open($host, 0666);
        if (!$this->conn) {
            PMF_Db::errorPage(sqlite_error_string(sqlite_last_error($this->conn)));
            die();
        }
        return true;
    }

    /**
     * Sends a query to the database.
     *
     * @param string $query SQL query
     * 
     * @return mixed $result
     */
    public function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        $result = sqlite_query($this->conn, $query);
        if (!$result) {
            $this->sqllog .= $this->error();
        }
        return $result;
    }

    /**
     * Escapes a string for use in a query
     *
     * @param   string
     * @return  string
     */
    public function escape_string($string)
    {
      return sqlite_escape_string($string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return object
     */
    public function fetch_object($result)
    {
        return sqlite_fetch_object($result);
    }



    /**
     * Fetch a result row as an associate array
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetch_assoc($result)
    {
        return sqlite_fetch_array($result, SQLITE_ASSOC);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource      $result Resultset
     * @return PMF_DB_Sqlite
     */
    public function fetchAll($result)
    {
        $ret = array();
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }
        
        while ($row = $this->fetch_object($result)) {
            $ret[] = $row;
        }
        
        return $ret;
    }
    
    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     */
    public function num_rows($result)
    {
        return sqlite_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     */
    public function sqllog()
    {
        return $this->sqllog;
    }

    /**
     * This function returns the table status.
     *
     * @return  array
     */
    public function getTableStatus()
    {
        $arr = array();

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        while ($row = $this->fetch_assoc($result)) {
            $num_result = $this->query('SELECT * FROM '.$row['name']);
            $arr[$row['name']] = $this->num_rows($num_result);
        }

        return $arr;
    }

    /**
     * Returns the next ID of a table
     *
     * @param   string      the name of the table
     * @param   string      the name of the ID column
     * @return  int
     */
    public function nextID($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') AS current_id FROM '.$table);
        $currentID = intval(sqlite_fetch_single($result));
        return ($currentID + 1);
    }

    /**
     * Returns the error string.
     * 
     * @return string
     */
    public function error()
    {
        if (0 == sqlite_last_error($this->conn)) {
            return;
        }
        return sqlite_error_string(sqlite_last_error($this->conn));
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function client_version()
    {
        return 'SQLite '.sqlite_libversion();
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function server_version()
    {
        return $this->client_version();
    }

    /**
     * Returns an array with all table names
     *
     * @return array
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ".(('' == $prefix) ? '':  "AND name LIKE '".$prefix."%' ")."ORDER BY name");
        while ($row = $this->fetch_object($result)) {
            if (!in_array($row->name, $this->tableNames)) {
                $this->tableNames[] = $row->name;
            }
        }
    }

    /**
     * Closes the connection to the database.
     * 
     * @return boolean
     */
    public function dbclose()
    {
        return sqlite_close($this->conn);
    }
}
