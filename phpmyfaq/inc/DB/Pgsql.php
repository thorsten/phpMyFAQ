<?php
/**
 * The PMF_DB_Pgsql class provides methods and functions for a PostgreSQL
 * database.
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
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Pgsql
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2003-02-24
 */
class PMF_DB_Pgsql implements PMF_DB_Driver
{
    /**
     * The connection object
     *
     * @var object
     */
    private $conn = null;

    /**
     * The query log string
     *
     * @var string
     */
    public $sqllog = '';

    /**
     * Tables
     *
     * @var array
     */
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * @param string $host     Database hostname
     * @param string $username Database username
     * @param string $password Password
     * @param string $database Database name
     *
     * @return boolean true, if connected, otherwise false
     */
    public function connect ($host, $user, $password, $database)
    {
        $connectionString = sprintf(
            'host=%s port=5432 dbname=%s user=%s password=%s',
            $host,
            $database,
            $user,
            $password
        );
        /* if you use mysql_pconnect(), remove the next line: */
        $this->conn = pg_pconnect($connectionString);
        /* comment out for more speed with mod_php or on Windows */
        // $this->conn = @pg_pconnect($connectionString);
        if (empty($database) || $this->conn == false) {
            PMF_Db::errorPage(pg_last_error($this->conn));
            die();
        }
        $this->query("SET standard_conforming_strings=on");
        return true;
    }

    /**
     * Sends a query to the database.
     *
     * @param   string $query
     * @return  mixed $result
     */
    public function query($query)
    {
        if (DEBUG) {
            $this->sqllog .= PMF_Utils::debug($query);
        }
        $result = pg_query($this->conn, $query);
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
        return pg_escape_string($this->conn, $string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  mixed
     */
    public function fetchObject($result)
    {
        return pg_fetch_object($result);
    }



    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetchArray($result)
    {
        return pg_fetch_array($result, NULL, PGSQL_ASSOC);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Pgsql
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
     * @param   mixed $result
     * @return  integer
     */
    public function numRows($result)
    {
        return pg_num_rows($result);
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
     * Returns just one row
     *
     * @param  string
     * @return string
     */
    private function getOne($query)
    {
        $row = pg_fetch_row($this->query($query));
        return $row[0];
    }

    /**
     * This function returns the table status.
     *
     * @return array
     */
    public function getTableStatus()
    {
        $select = "SELECT relname FROM pg_stat_user_tables ORDER BY relname;";
        $arr = array();
        $result = $this->query($select);
        while ($row = $this->fetchArray($result)) {
            $count = $this->getOne("SELECT count(1) FROM ".$row["relname"].";");
            $arr[$row["relname"]] = $count;
        }
        return $arr;
    }

    /**
     * Returns the next ID of a table
     *
     * @param string $table the name of the table
     * @param string $id    the name of the ID column
     * @return  int
     */
    public function nextId($table, $id)
    {
        $result = $this->query("SELECT nextval('".$table."_".$id."_seq') as current_id;");
        $currentID = pg_result($result, 0, 'current_id');
        return ($currentID);
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error()
    {
        return pg_last_error();
    }

    /**
     * This function returns the client version string.
     *
     * @return string
     */
    public function clientVersion()
    {
        $pg_version = pg_version($this->conn);
        if (isset($pg_version['client'])) {
            return $pg_version['client'];
        } else {
            return 'n/a';
        }
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        $pg_version = pg_version($this->conn);
        if (isset($pg_version['server_version'])) {
            return $pg_version['server_version'];
        } else {
            return 'n/a';
        }
    }

    /**
     * Returns an array with all table names
     *
     * @param string $prefix Table prefix
     *
     * @return array
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        if ('' !== $prefix) {
            $prefix = "LIKE '" . $prefix . "%'";
        }

        $query = sprintf(
            'SELECT relname FROM pg_stat_user_tables WHERE relname %s ORDER BY relname',
            $prefix
        );

        $result = $this->query($query);
        while ($row = $this->fetchObject($result)) {
            foreach ($row as $tableName) {
                if (!in_array($tableName, $this->tableNames)) {
                    $this->tableNames[] = $tableName;
                }
            }
        }
    }

    /**
     * Closes the connection to the database.
     *
     * @return boolean
     */
    public function close()
    {
        return pg_close($this->conn);
    }
    
}