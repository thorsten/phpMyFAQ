<?php
/**
 * $Id: Oracle.php,v 1.7 2007-02-20 20:11:15 thorstenr Exp $
 *
 * db_oracle
 *
 * The db_oracle class provides methods and functions for a Oracle database
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @package     db_oracle
 * @since       2005-09-20
 * @copyright   (c) 2005-2007 phpMyFAQ Team
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

class db_oracle
{
    /**
     * The connection object
     *
     * @var   mixed
     * @see   connect(), query(), dbclose()
     */
    var $conn = false;

    /**
     * The query log string
     *
     * @var   string
     * @see   query()
     */
    var $sqllog = "";

    /**
     * Tables
     *
     * @var     array
     */
    var $tableNames = array();

    /**
     * Constructor
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    function db_oracle()
    {
    }

    function __construct()
    {
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
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    function connect ($host, $user, $passwd, $db)
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
     * This function sends a query to the database.
     *
     * @param   string $query
     * @return  mixed $result
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    function query($query)
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
    function escape_string($string)
    {
        return addslashes($string);
    }

    /**
     * Fetch a result row as an object
     *
     * This function fetches a result row as an object.
     *
     * @param   mixed $result
     * @return  mixed
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    function fetch_object($result)
    {
        return oci_fetch_object($result);
    }

    /**
     * Number of rows in a result
     *
     * This function returns the number of rows in a result.
     *
     * @param   mixed $result
     * @return  mixed
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    function fetch_row($result)
    {
        return oci_fetch_row($result);
    }

    /**
     * Fetch a result row as an array
     *
     * This function fetches a result as an associative array.
     *
     * @param   mixed $result
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    function fetch_assoc($result)
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
    function num_rows($result)
    {
        return oci_num_rows($result);
    }

    /**
     * Returns the ID of the latest insert
     *
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-09-20
     */
    function insert_id($table, $field)
    {
        $query = sprintf('SELECT max(%s) FROM %s', $field, $table);
         $result = $this->query($query);
         $row = $this->fetch_row($result);
        if (isset($row[0]) && $row[0] > 0){
            return $row[0] + 1;
        } else {
            return 1;
        }
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
    function sqllog()
    {
        return $this->sqllog;
    }

    function version_check($target = "")
    {
      return true;
    }

     /**
     * Generates a result based on search a search string.
     *
     * This function generates a result set based on a search string.
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo scaramuccia <matteo@scaramuccia.com>
     * @since   2005-09-20
     */
    function search($table, $assoc, $joinedTable = '', $joinAssoc = array(), $match = array(), $string = '', $cond = array(), $orderBy = array())
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
            $joined = substr($joined, 0, -4);
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
    function getTableStatus()
    {
        return null;
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
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-09-20
    */
    function nextID($table, $id)
    {
        $stmt = oci_parse($this->conn, 'SELECT max('.$id.') as current_id FROM '.$table);
        oci_execute($stmt, OCI_DEFAULT);
        $currentID = oci_result ($stmt, 'current_id');
        return ($currentID + 1);
    }

    /**
    * Returns the error string.
    *
    * This function returns the last error string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-09-20
    */
    function error()
    {
        $errormsg = oci_error($this-conn);
        return $errormsg['message'];
    }

    /**
    * Returns the client version string.
    *
    * This function returns the version string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-09-20
    */
    function client_version()
    {
        return null;
    }

    /**
    * Returns the server version string.
    *
    * This function returns the version string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-09-20
    */
    function server_version()
    {
        return oci_server_version($this->conn);
    }

    /**
     * Returns an array with all table names
     *
     * FIXME: Implement it.
     *
     * @access  public
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2006-08-26
     */
    function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';
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
    function dbclose()
    {
        return oci_close($this->conn);
    }
}
