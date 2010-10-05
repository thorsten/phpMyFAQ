<?php
/**
 * The db_mssql class provides methods and functions for a Microsoft SQL Server
 * database.
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
 * @author    Daniel Hoechst <dhoechst@petzl.com>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Daniel Hoechst <dhoechst@petzl.com>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @package   2005-01-11
 */
class PMF_DB_Mssql implements PMF_DB_Driver 
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
     * @param   string $username
     * @param   string $password
     * @param   string $db_name
     * @return  boolean TRUE, if connected, otherwise FALSE
     */
    function connect ($host, $user, $passwd, $db)
    {
        $this->conn = mssql_pconnect($host, $user, $passwd);
        if (empty($db) OR $this->conn == false) {
            PMF_Db::errorPage(mssql_get_last_message());
            die();
        }
        return mssql_select_db($db, $this->conn);
    }

    /**
     * Sends a query to the database.
     *
     * @param string $query Query
     * 
     * @return resource
     */
    function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        return mssql_query($query, $this->conn);
    }

    /**
    * Escapes a string for use in a query
    *
    * @param   string
    * @return  string
    */
    function escapeString($string)
    {
        return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  object
     */
    function fetchObject($result)
    {
        return mssql_fetch_object($result);
    }



    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  array
     */
    function fetch_assoc($result)
    {
        return mssql_fetch_assoc($result);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Mssql
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
    function numRows($result)
    {
        return mssql_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @return string
     */
    function sqllog()
    {
        return $this->sqllog;
    }

    /**
     * This function returns the table status.
     *
     * @return array
     */
    function getTableStatus()
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
    function nextID($table, $id)
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
    function error()
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
    function client_version()
    {
         return '';
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    function server_version()
    {
        $result = $this->query('SELECT @@version AS SERVER_VERSION');
        $version = mssql_result($result, 0, 'SERVER_VERSION');
        if (isset($version)) {
            return $version;
        }
    }

    /**
     * Returns an array with all table names
     *
     * @return void
     */
    function getTableNames($prefix = '')
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
        return mssql_data_seek($result, $rowNumber);
    }
    
    /**
     * Closes the connection to the database.
     *
     * @return boolean
     */
    function dbclose()
    {
        return mssql_close($this->conn);
    }
}
