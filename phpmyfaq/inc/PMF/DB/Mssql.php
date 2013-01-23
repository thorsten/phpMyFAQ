<?php
/**
 * The PMF_DB_Mssql class provides methods and functions for a Microsoft SQL
 * Server database.
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
 * @author    Daniel Hoechst <dhoechst@petzl.com>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2005-01-11
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Mssql
 *
 * @category  phpMyFAQ
 * @package   DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Daniel Hoechst <dhoechst@petzl.com>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @package   2005-01-11
 */
class PMF_DB_Mssql implements PMF_DB_Driver 
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
    private $sqllog = "";

    /**
     * Tables
     *
     * @var     array
     */
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * @param   string $host
     * @param   string $user
     * @param   string $password
     * @param   string $database
     *
     * @return  boolean TRUE, if connected, otherwise FALSE
     */
    public function connect($host, $user, $password, $database = '')
    {
        $this->conn = mssql_pconnect($host, $user, $password);

        if ($this->conn === false) {
            PMF_Db::errorPage(mssql_get_last_message());
            die();
        }

        if ('' !== $database) {
            return $this->selectDb($database);
        }

        return true;
    }

    /**
     * Connects to a given database
     *
     * @param string $database Database name
     *
     * @return boolean
     */
    public function selectDb($database)
    {
        return mssql_select_db($database, $this->conn);
    }

    /**
     * Sends a query to the database.
     *
     * @param string $query Query
     *
     * @return resource
     */
    public function query($query)
    {
        if (DEBUG) {
            $this->sqllog .= PMF_Utils::debug($query);
        }
        $result = mssql_query($query, $this->conn);
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
        return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  object
     */
    public function fetchObject($result)
    {
        return mssql_fetch_object($result);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetchArray($result)
    {
        return mssql_fetch_assoc($result);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param resource $result Resultset
     *
     * @throws Exception
     *
     * @return array
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
        return mssql_num_rows($result);
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
     * This function returns the table status.
     *
     * @return array
     */
    public function getTableStatus()
    {
        $tables = array();

        $query = "
            SELECT
                obj.name AS table_name,
                idx.rows AS table_rows
            FROM
                sysobjects obj, sysindexes idx
            WHERE
                    idx.id = OBJECT_ID(obj.name)
                AND idx.indid < 2
                AND obj.xtype = 'u'
            ORDER BY obj.name";
        $result = $this->query($query);

        while ($row = $this->fetchObject($result)) {
            if ('dtproperties' != $row->table_name) {
                $tables[$row->table_name] = $row->table_rows;
            }
        }

        return $tables;
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
        $result = $this->query('SELECT max('.$id.') as current_id FROM '.$table);
        $currentID = mssql_result($result, 0, 'current_id');
        return ($currentID + 1);
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error()
    {
        $result = $this->query('SELECT @@ERROR AS ErrorCode');
        $errormsg = mssql_result($result, 0, 'ErrorCode');
        if ($errormsg != 0) {
            return $errormsg;
        }
    }

    /**
     * Returns the client version string.
     *
     * @return string
     */
    public function clientVersion()
    {
         return '';
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        $result  = $this->query('SELECT @@version AS SERVER_VERSION');
        $version = mssql_result($result, 0, 'SERVER_VERSION');
        if (isset($version)) {
            return $version;
        }

        return 42;
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

        $result = $this->query('SELECT name FROM sysobjects WHERE type = \'u\''.(('' == $prefix) ? '' : ' AND name LIKE \''.$prefix.'%\' ORDER BY name'));
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
     * @return boolean
     */
    public function close()
    {
        return mssql_close($this->conn);
    }
}
