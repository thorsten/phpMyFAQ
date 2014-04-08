<?php
/**
 * The PMF_DB_Sqlite class provides methods and functions for a SQLite v2
 * database. This class is deprecated for PHP 5.3 and PHP 5.4.
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
 * @author    Johannes Schlüter <johannes@php.net>
 * @copyright 2005-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Johannes Schlüter <johannes@php.net>
 * @copyright 2005-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-06-27
 */
class PMF_DB_Sqlite implements PMF_DB_Driver
{
    /**
     * The connection object
     *
     * @var   mixed
     * @see   connect(), query(), close()
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
    public $tableNames = [];

    /**
     * Connects to the database.
     *
     * @param string $host
     * @param string
     * @param string
     * @param string
     *
     * @return  boolean
     */
    public function connect($host, $user, $passwd, $db = '')
    {
        $this->conn = sqlite_open($host, 0666);
        if (!$this->conn) {
            PMF_Db::errorPage(sqlite_error_string(sqlite_last_error($this->conn)));
            die();
        }
        return true;
    }

    /**
     * This function sends a query to the database.
     *
     * @param string  $query
     * @param integer $offset
     * @param integer $rowcount
     *
     * @return  mixed $result
     */
    public function query($query, $offset = 0, $rowcount = 0)
    {
        if (DEBUG) {
            $this->sqllog .= PMF_Utils::debug($query);
        }

        if (0 < $rowcount) {
            $query .= sprintf(' LIMIT %d,%d', $offset, $rowcount);
        }

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
    public function escape($string)
    {
      return sqlite_escape_string($string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return object
     */
    public function fetchObject($result)
    {
        return sqlite_fetch_object($result);
    }



    /**
     * Fetch a result row as an associate array
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetchArray($result)
    {
        return sqlite_fetch_array($result, SQLITE_ASSOC);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource      $result Resultset
     *
     * @throws Exception
     *
     * @return array
     */
    public function fetchAll($result)
    {
        $ret = [];
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
     * @param   mixed $result
     * @return  integer
     */
    public function numRows($result)
    {
        return sqlite_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     */
    public function log()
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
        $arr = [];

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        while ($row = $this->fetchArray($result)) {
            $num_result = $this->query('SELECT * FROM '.$row['name']);
            $arr[$row['name']] = $this->numRows($num_result);
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
    public function nextId($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') AS current_id FROM '.$table);
        $currentID = intval(sqlite_fetch_single($result));
        return ($currentID + 1);
    }

    /**
     * Returns the error string.
     *
     * @return void|string
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
    public function clientVersion()
    {
        return 'SQLite '.sqlite_libversion();
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        return $this->clientVersion();
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
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ".(('' == $prefix) ? '':  "AND name LIKE '".$prefix."%' ")."ORDER BY name");
        while ($row = $this->fetchObject($result)) {
            if (!in_array($row->name, $this->tableNames)) {
                $this->tableNames[] = $row->name;
            }
        }

        return $this->tableNames;
    }

    /**
     * Closes the connection to the database.
     *
     * @return boolean
     */
    public function close()
    {
        return sqlite_close($this->conn);
    }
}
