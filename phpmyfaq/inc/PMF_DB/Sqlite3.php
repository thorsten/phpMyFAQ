<?php
/**
 * The PMF_DB_Sqlite3 class provides methods and functions for a SQLite v3
 * database
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-02
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Sqlite3
 *
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-02
 */
class PMF_DB_Sqlite3 implements PMF_DB_Driver
{
    /**
     * The connection object
     *
     * @var   SQLite3
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
        $this->conn = new SQLite3($host);
        if (!$this->conn) {
            PMF_Db::errorPage($this->conn->lastErrorMsg());
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
        $result = $this->conn->query($query);
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
        return SQLite3::escapeString($string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return object
     */
    public function fetchObject($result)
    {
        $array = $this->fetch_assoc($result);

        $object    = new stdClass();
        $reflector = new ReflectionObject($object);

        for($i = 0; $i < $result->numColumns(); $i++) {
            $name  = $result->columnName($i);
            $value = $array[$name];

            try {
                $attribute = $reflector->getProperty($name);
                $attribute->setAccessible(TRUE);
                $attribute->setValue($object, $value);
            } catch (ReflectionException $e) {
                $object->$name = $value;
            }
        }

        return $object;
    }

    /**
     * Fetch a result row as an array
     *
     * @param   SQLite3Result $result
     *
     * @return  array
     */
    public function fetchArray($result)
    {
        return $result->fetchArray();
    }

    /**
     * Fetch a result row as an associate array
     *
     * @param   SQLite3Result $result
     *
     * @return  array
     */
    public function fetchAssoc($result)
    {
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource      $result Resultset
     *
     * @return array
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
     * @param   SQLite3Result $result
     * @return  integer
     */
    public function numRows($result)
    {
        return count($result->fetchArray());
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
     * @return array
     */
    public function getTableStatus()
    {
        $arr = array();

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        while ($row = $this->fetchAssoc($result)) {
            $num_result = $this->query('SELECT * FROM '.$row['name']);
            $arr[$row['name']] = $this->numRows($num_result);
        }

        return $arr;
    }

    /**
     * This function returns the table status.
     *
     * @return  array
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query(
            "SELECT name FROM sqlite_master WHERE type='table' ".(('' == $prefix) ? '': "AND name LIKE '".$prefix."%' ")."ORDER BY name"
        );
        while ($row = $this->fetchObject($result)) {
            if (!in_array($row->name, $this->tableNames)) {
                $this->tableNames[] = $row->name;
            }
        }
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
        $result = (int)$this->conn->querySingle(
            sprintf(
                'SELECT max(%s) AS current_id FROM %s'
            ),
            $id,
            $table
        );
        return ($result + 1);
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error()
    {
        if (0 === $this->conn->lastErrorCode()) {
            return '';
        }
        return $this->conn->lastErrorMsg();
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function clientVersion()
    {
        $version = $this->conn->version();

        return $version['versionString'];
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
     * Closes the connection to the database.
     *
     * @return boolean
     */
    public function close()
    {
        return $this->conn->close();
    }
}
