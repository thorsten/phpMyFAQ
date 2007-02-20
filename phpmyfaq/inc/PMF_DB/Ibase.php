<?php
/**
 * $Id: Ibase.php,v 1.7 2007-02-20 20:11:15 thorstenr Exp $
 *
 * db_ibase
 *
 * The db_ibase class provides methods and functions for Firebird/InterBase
 * databases.
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @package     db_ibase
 * @since       2005-11-28
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

class db_ibase
{
    /**
    * The connection object
    *
    * @var  mixed
    * @see  connect(), query(), dbclose()
    */
    var $conn = false;

    /**
    * The query log string
    *
    * @var  string
    * @see  query()
    */
    var $sqllog = '';

    /**
     * Tables
     *
     * @var     array
     */
    var $tableNames = array();

    /**
    * Connects to the database.
    *
    * This function connects to a ibase database
    *
    * @param   string $host
    * @param   string $username
    * @param   string $password
    * @param   string $db_name
    * @return  boolean true, if connected, otherwise false
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function connect($host, $user, $passwd, $db)
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
    * This function sends a query to the database.
    *
    * @param   string $query
    * @return  mixed $result
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        return ibase_query($this->conn, $query);
    }

    /**
    * Escapes a string for use in a query
    *
    * @param   string
    * @return  string
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function escape_string($string)
    {
      return str_replace("'", "''", $string);
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
    * @author  Helmut Tessarek <tessus@evermeet.cx>
    * @since   2005-04-16
    */
    function fetch_object($result)
    {
        return ibase_fetch_object($result);
    }


    /**
    * fetch_row()
    *
    * This function returns the number of rows in a result.
    *
    * @param   mixed $result
    * @return  mixed
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function fetch_row($result)
    {
        return ibase_fetch_row($result);
    }

    /**
    * fetch_assoc()
    *
    * This function fetches a result as an associative array.
    *
    * @param   mixed $result
    * @return  array
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function fetch_assoc($result)
    {
        if (function_exists('ibase_fetch_assoc')) {
            return ibase_fetch_assoc($result);
        } else {
            return get_object_vars(ibase_fetch_object($result));
        }
    }

    /**
    * Number of rows in a result
    *
    * @param   mixed $result
    * @return  integer
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function num_rows($result)
    {
        return ibase_num_rows($result);
    }

    /**
    * Returns the ID of the latest insert
    *
    * @return  integer
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function insert_id($table, $field)
    {
        $result = $this->query('SELECT max('.$field.') AS last_id FROM '.$table);
        $row = $this->fetch_object($result);
        return $row->last_id;
    }

    /**
    * Logs the queries
    *
    * @param   mixed $result
    * @return  integer
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function sqllog()
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
     * Generates a result based on search a search string.
     *
     * This function generates a result set based on a search string.
     * FIXME: can extend to handle operands like google
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo scaramuccia <matteo@scaramuccia.com>
     * @since   2005-04-16
     */
    function search($table, $assoc, $joinedTable = '', $joinAssoc = array(), $match = array(), $string = '', $cond = array(), $orderBy = array())
    {
        $string = trim($string);
        $fields = '';
        $join = '';
        $joined = '';
        $where = '';

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
                $join .= $joinedFields.' AND ';
                }
            $joined .= substr($join, 0, -4);
        }

        $keys = preg_split("/\s+/", $string);
        $numKeys = count($keys);
        $numMatch = count($match);

        for ($i = 0; $i < $numKeys; $i++) {
            if (strlen($where) != 0 ) {
                $where = $where;
            }
            $where = $where." (";
            for ($j = 0; $j < $numMatch; $j++) {
                if ($j != 0) {
                    $where = $where." OR ";
                }
                $where = $where.$match[$j]." LIKE '%".$keys[$i]."%'";
            }

            $where .= ")";
        }

        foreach($cond as $field => $data) {
            if (empty($where)) {
                $where .= $field." = '".$data."'";
            } else {
                $where .= " AND ".$field." = '".$data."'";
            }
        }

        $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE";

        if (!empty($where)) {
            $query .= " (".$where.")";
        }

        if (is_numeric($string)) {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." = ".$string;
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

        return $this->query($query);
    }

    /**
    * Returns the error string.
    *
    * This function returns the table status.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function getTableStatus()
    {
        return;
    }

    /**
    * Returns the next ID of a table
    *
    * @param   string      the name of the table
    * @param   string      the name of the ID column
    * @return  int
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function nextID($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') as current_id FROM '.$table);
        $row = $this->fetch_object($result);
        return ($row->current_id + 1);
    }

    /**
    * Returns the error string.
    *
    * This function returns the last error string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function error()
    {
        return ibase_errmsg();
    }

    /**
    * Returns the client version string.
    *
    * This function returns the version string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function client_version()
    {
        return;
    }

    /**
    * Returns the server version string.
    *
    * This function returns the version string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function server_version()
    {
        return ibase_server_info();
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
    * @since   2005-04-16
    */
    function dbclose()
    {
        return ibase_close($this->conn);
    }

}
