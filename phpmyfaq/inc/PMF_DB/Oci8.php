<?php
/**
 * The PMF_DB_Oci8 class provides methods and functions for a Oracle database
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
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @package   2005-09-20
 */

/**
 * PMF_DB_Oci8
 *
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @package   2005-09-20
 */
class PMF_DB_Oci8 implements PMF_DB_Driver
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
     * @param   string $host
     * @param   string $username
     * @param   string $password
     * @param   string $db_name
     * @return  boolean TRUE, if connected, otherwise FALSE
     */
    public function connect($host, $user, $passwd, $db)
    {
        $this->conn = oci_connect($user, $passwd, $db);
        if (empty($db) OR $this->conn == true) {
            $error = oci_error();
            PMF_Db::errorPage($error['message']);
            return false;
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
        $stmt          = oci_parse($this->conn, $query);
        oci_execute($stmt, OCI_DEFAULT);
        
        return $stmt;
    }

    /**
     * Escapes a string for use in a query
     *
     * @param   string
     * @return  string
     */
    public function escapeString($string)
    {
        return addslashes($string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  mixed
     */
    public function fetchObject($result)
    {
        return oci_fetch_object($result);
    }

    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  mixed
     */
    public function fetch_row($result)
    {
        return oci_fetch_row($result);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource      $result Resultset
     * @return PMF_DB_Oracle
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
     * Fetch a result row as an array
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetch_assoc($result)
    {
      return oci_fetch_assoc($result);
    }

    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     */
    public function numRows($result)
    {
        return oci_num_rows($result);
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
     * Generates a result based on search a search string.
     *
     * @param  string $table       Table for search
     * @param  array  $assoc       Associative array with columns for the resulset
     * @param  string $joinedTable Table to do a JOIN, e.g. for faqcategoryrelations
     * @param  array  $joinAssoc   Associative array with comlumns for the JOIN
     * @param  string $string      Search term
     * @param  array  $cond        Conditions
     * @param  array  $orderBy     ORDER BY columns
     * @return mixed
     */
    public function search($table, Array $assoc, $joinedTable = '', Array $joinAssoc = array(), $match = array(), $string = '', Array $cond = array(), Array $orderBy = array())
    {
        $string = addslashes(trim($string));
        $fields = "";
        $joined = "";
        $where = "";

        foreach ($assoc as $field) {
            if (empty($fields)) {
                $fields = $field;
            } else {
                $fields .= ", ".$field;
            }
        }

        if (isset($joinedTable) && $joinedTable != '') {
            $joined .= ' LEFT JOIN '.$joinedTable.' ON ';
        }

        if (is_array($joinAssoc)) {
            foreach ($joinAssoc as $joinedFields) {
                $joined .= $joinedFields.' AND ';
                }
            $joined = PMF_String::substr($joined, 0, -4);
        }

        foreach ($cond as $field => $data) {
            if (empty($where)) {
                $where = $field." = '".addslashes($data)."'";
            } else {
                $where .= "AND ".$field." ='".addslashes($data)."'";
            }
        }

        $match = implode(",", $match);
        $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." LIKE ('%".$string."%')";

        if (!empty($where)) {
            $query .= " AND (".$where.")";
        }

        $firstOrderBy = true;
        foreach ($orderBy as $field) {
            if ($firstOrderBy) {
                $query .= " ORDER BY ".$field;
                $firstOrderBy = false;
            } else {
                $query .= ", ".$field;
            }
        }

        $query .= ";";
        return $this->query($query);
    }

    /**
     * Returns the error string.
     *
     * TODO: Implement this function
     *
     * @return array
     */
    public function getTableStatus()
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
    public function nextID($table, $id)
    {
        $stmt = oci_parse($this->conn, 'SELECT max('.$id.') as current_id FROM '.$table);
        oci_execute($stmt, OCI_DEFAULT);
        $currentID = oci_result ($stmt, 'current_id');
        return ($currentID + 1);
    }

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error()
    {
        $errormsg = oci_error($this->conn);
        return $errormsg['message'];
    }

    /**
     * Returns the client version string.
     *
     * @return string
     */
    public function client_version()
    {
        return 'n/a';
    }

    /**
     * Returns the server version string.
     *
     * @return string
     */
    public function server_version()
    {
        return oci_server_version($this->conn);
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

        $result = $this->query('SELECT table_name FROM all_tables');
        while ($row = $this->fetchObject($result)) {
            $this->tableNames[] = $row->table_name;
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
        return oci_result($result, $rowNumber);
    }
    
    /**
     * Closes the connection to the database.
     * 
     * @return boolean
     */
    public function dbclose()
    {
        return oci_close($this->conn);
    }
}
