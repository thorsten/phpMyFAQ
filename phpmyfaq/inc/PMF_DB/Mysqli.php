<?php
/**
 * The PMF_DB_Mysqli class provides methods and functions for a MySQL 5.0.x,
 * 5.1.x, 5.5.x and 5.6.x databases.
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
 * @author    David Soria Parra <dsoria@gmx.net>
 * @copyright 2005-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @package   2005-02-21
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Mysqli
 *
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    David Soria Parra <dsoria@gmx.net>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @package   2005-02-21
 */
class PMF_DB_Mysqli implements PMF_DB_Driver
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
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * This function connects to a MySQL database
     *
     * @param   string $host
     * @param   string $username
     * @param   string $password
     * @param   string $db_name
     * @return  boolean true, if connected, otherwise false
     */
    public function connect($host, $user, $passwd, $db)
    {
        $this->conn = new mysqli($host, $user, $passwd, $db);
        if (mysqli_connect_errno()) {
            PMF_Db::errorPage(mysqli_connect_error());
            die();
        }
        
        /* change character set to UTF-8 */
        if (!$this->conn->set_charset('utf8')) {
            PMF_Db::errorPage($this->error());
        }
        
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
        $this->sqllog .= pmf_debug($query);
        return $this->conn->query($query);
    }



    /**
    * Escapes a string for use in a query
    *
    * @param   string
    * @return  string
    */
    public function escape($string)
    {
      return $this->conn->real_escape_string($string);
    }



    /**
     * Fetch a result row as an object
     *
     * This function fetches a result row as an object.
     *
     * @param   mixed $result
     * @return  mixed
     */
    public function fetchObject($result)
    {
        return $result->fetch_object();
    }


    /**
     * Fetch a result row as an object
     *
     * This function fetches a result as an associative array.
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetchArray($result)
    {
        return $result->fetch_assoc();
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource      $result Resultset
     * @return PMF_DB_Mysqli
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
        return $result->num_rows;
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
        $result = $this->query("SHOW TABLE STATUS");
        while ($row = $this->fetchArray($result)) {
            $arr[$row["Name"]] = $row["Rows"];
        }
        return $arr;
    }

    /**
     * This function is a replacement for MySQL's auto-increment so that
     * we don't need it anymore.
     *
     * @param   string      the name of the table
     * @param   string      the name of the ID column
     * @return  int
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
           
        $result  = $this->query($select);
        $current = $result->fetch_row();
        return $current[0] + 1;
    }

     /**
      * Returns the error string.
      *
      * @return string
      */
    public function error()
    {
        return $this->conn->error;
    }

    /**
     * Returns the client version string.
     *
     * @return string
     */
    public function clientVersion()
    {
        return $this->conn->get_client_info();
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function serverVersion()
    {
        return $this->conn->get_server_info();
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
        return mysqli_data_seek($result, $rowNumber);
    }
    
    /**
     * Closes the connection to the database.
     * 
     * @return void
     */
    public function close()
    {
        if (is_resource($this->conn)) {
            $this->conn->close();
        }
    }
}
