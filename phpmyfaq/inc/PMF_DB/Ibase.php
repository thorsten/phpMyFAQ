<?php
/**
 * The PMF_DB_Ibase class provides methods and functions for Firebird/InterBase
 * databases.
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
 * @copyright 2005-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-11-28
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Ibase
 * 
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-11-28
 */
class PMF_DB_Ibase implements PMF_DB_Driver
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
    private $sqllog = '';

    /**
     * Tables
     *
     * @var array
     */
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * @param   string $host
     * @param   string $username
     * @param   string $password
     * @param   string $db_name
     * @return  boolean true, if connected, otherwise false
     */
    public function connect($host, $user, $passwd, $db)
    {
        $this->conn = ibase_connect($db, $user, $passwd);
        if (false == $this->conn) {
            PMF_Db::errorPage(ibase_errmsg());
            die();
        }
        return true;
    }

    /**
     * Sends a query to the database.
     *
     * @param   string $query
     * @return resource
     */
    public function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        return ibase_query($this->conn, $query);
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
     * @return  mixed
     */
    public function fetchObject($result)
    {
        return ibase_fetch_object($result);
    }


    /**
     * This function returns the number of rows in a result.
     *
     * @param   mixed $result
     * @return  mixed
     */
    public function fetch_row($result)
    {
        return ibase_fetch_row($result);
    }

    /**
     * This function fetches a result as an associative array.
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetchArray($result)
    {
        return ibase_fetch_assoc($result);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Ibase
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
        return ibase_num_rows($result);
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
     * TODO: Implement this function
     */
    function version_check($target = "")
    {
        return false;
    }

    /**
    * Returns the error string.
    * 
    * @return array
    */
    function getTableStatus()
    {
        return array();
    }

    /**
    * Returns the next ID of a table
    *
    * @param   string      the name of the table
    * @param   string      the name of the ID column
    * @return  int
    */
    function nextId($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') as current_id FROM '.$table);
        $row    = $this->fetchObject($result);
        return ($row->current_id + 1);
    }

    /**
    * Returns the error string.
    *
    * @return string
    */
    function error()
    {
        return ibase_errmsg();
    }

    /**
    * Returns the client version string.
    *
    * @return string
    */
    function clientVersion()
    {
        return '';
    }

    /**
    * Returns the server version string.
    * 
    * @return string
    */
    function serverVersion()
    {
        return ibase_server_info($this->conn, IBASE_SVC_SERVER_VERSION);
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
        return true;
    }
    
    /**
    * Closes the connection to the database.
    *
    * @return boolean
    */
    function close()
    {
        return ibase_close($this->conn);
    }

}
