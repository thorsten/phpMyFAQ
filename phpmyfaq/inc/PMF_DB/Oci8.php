<?php
/**
 * The PMF_DB_Oci8 class provides methods and functions for a Oracle database
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @package    2005-09-20
 * @version    SVN: $Id$
 * @copyright  2005-2009 phpMyFAQ Team
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
 */

/**
 * PMF_DB_Oci8
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @package    2005-09-20
 * @version    SVN: $Id$
 * @copyright  2005-2009 phpMyFAQ Team
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
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
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
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        $stmt = oci_parse($this->conn, $query);
        oci_execute($stmt, OCI_DEFAULT);
    }

    /**
     * Escapes a string for use in a query
     *
     * @param   string
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function escape_string($string)
    {
        return addslashes($string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return  mixed
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function fetch_object($result)
    {
        return oci_fetch_object($result);
    }

    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  mixed
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
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
        
        while ($row = $this->fetch_object($result)) {
            $ret[] = $row;
        }
        
        return $ret;
    }
    
    /**
     * Fetch a result row as an array
     *
     * @param   mixed $result
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
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
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function num_rows($result)
    {
        return oci_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function sqllog()
    {
        return $this->sqllog;
    }

    /**
     * Generates a result based on search a search string.
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo scaramuccia <matteo@scaramuccia.com>
     * @since   2005-09-20
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
     * This function returns the table status.
     *
     * TODO: Implement this function
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function getTableStatus()
    {
        return null;
    }

    /**
     * Returns the next ID of a table
     *
     * @param   string      the name of the table
     * @param   string      the name of the ID column
     * @return  int
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
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
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function error()
    {
        $errormsg = oci_error($this->conn);
        return $errormsg['message'];
    }

    /**
     * Returns the client version string.
     *
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function client_version()
    {
        return 'n/a';
    }

    /**
     * Returns the server version string.
     *
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function server_version()
    {
        return oci_server_version($this->conn);
    }

    /**
     * Returns an array with all table names
     *
     * @access  public
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2006-08-26
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query('SELECT table_name FROM all_tables');
        while ($row = $this->fetch_object($result)) {
            $this->tableNames[] = $row->table_name;
        }
    }

    /**
     * Closes the connection to the database.
     *
     * This function closes the connection to the database.
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    public function dbclose()
    {
        return oci_close($this->conn);
    }
}
