<?php
/**
 * This class provides methods and functions for a Sybase database
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
 * @author    Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2004-12-10
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Sybase
 * 
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2004-12-10
 */
class PMF_DB_Sybase implements PMF_DB_Driver
{
    /**
     * The connection object
     *
     * @var resource
     */
    private $conn = false;

    /**
     * The query log string
     *
     * @var string
     */
    private $sqllog = "";

    /**
     * Tables
     *
     * @var     array
     */
    public $tableNames = array();

    /**
     * Constructor
     *
     * @return PMF_DB_Sybase
     */
    public function __construct()
    {
        if (function_exists('sybase_min_client_severity')) {
            sybase_min_client_severity(100);
        }

        if (function_exists('sybase_min_server_severity')) {
            sybase_min_server_severity(9);
        }

        if (function_exists('sybase_min_error_severity')) {
            sybase_min_error_severity(9);
        }

        if (function_exists('sybase_min_message_severity')) {
            sybase_min_message_severity(9);
        }
    }

    /**
     * Connects to the database.
     *
     * This function connects to a MySQL database
     *
     * @param   string $host
     * @param   string $username
     * @param   string $password
     * @param   string $db_name
     * @return  boolean TRUE, if connected, otherwise FALSE
     */
    function connect($host, $user, $passwd, $db)
    {
        $this->conn = @sybase_pconnect($host, $user, $passwd);
        if (empty($db) || $this->conn === false) {
            PMF_Db::errorPage('An unspecified error occurred.');
            die();
        }
        return sybase_select_db($db, $this->conn);
    }

    /**
     * Sends a query to the database.
     *
     * This function sends a query to the database.
     *
     * @param   string $query
     * @return  mixed $result
     */
    function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        return sybase_query($query, $this->conn);
    }

    /**
    * Escapes a string for use in a query
    *
    * @param   string
    * @return  string
    */
    function escape_string($string)
    {
        return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  mixed
     */
    function fetch_object($result)
    {
        return sybase_fetch_object($result);
    }

    /**
     * This function fetches a result as an associative array.
     *
     * @param   mixed $result
     * @return  array
     */
    function fetch_assoc($result)
    {
      return sybase_fetch_assoc($result);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource      $result Resultset
     * @return PMF_DB_Sybase
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
    function num_rows($result)
    {
        return sybase_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
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
                AND obj.xtype = 'u'";
        $result = $this->query($query);

        while ($row = $this->fetch_object($result)) {
            if ('dtproperties' != $row->table_name) {
                $tables[$row->table_name] = $row->table_rows;
            }
        }
        
        ksort($tables);

        return $tables;
    }

    /**
    * Returns the next ID of a table
    *
    * This function is a replacement for MySQL's auto-increment so that
    * we don't need it anymore.
    *
    * @param   string      the name of the table
    * @param   string      the name of the ID column
    * @return  int
    */
    function nextID($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') as current_id FROM '.$table);
        $currentID = sybase_result($result, 0, 'current_id');
        return ($currentID + 1);
    }

     /**
     * Returns the error string.
     * 
     * @return string
     */
    function error()
    {
        return sybase_get_last_message();
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
        $result   = $this->query('SELECT @@version AS SERVER_VERSION');
        $version = sybase_result($result, 0, 'SERVER_VERSION');
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

        $result = $this->query('SELECT name FROM sysobjects WHERE type = \'u\''.(('' == $prefix) ? '' : ' AND name LIKE \''.$prefix.'%\''));
        while ($row = $this->fetch_object($result)) {
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
        return sybase_data_seek($result, $rowNumber);
    }
    
    /**
     * Closes the connection to the database.
     * 
     * @return boolean
     */
    function dbclose()
    {
        return sybase_close($this->conn);
    }
}
