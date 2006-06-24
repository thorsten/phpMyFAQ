<?php
/**
* $Id: Ibm_db2.php,v 1.2 2006-06-24 14:09:46 thorstenr Exp $
*
* db_db2
*
* The db_db2 class provides methods and functions for a IBM DB2 Version 8.2
* database. This will only work with the PECL extension.
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Helmut Tessarek <tessus@evermeet.cx>
* @package      db_db2
* @since        2005-04-16
*
* Copyright:    (c) 2006 phpMyFAQ Team
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

class db_ibm_db2
{
    /**
    * The connection object
    *
    * @var  mixed
    * @see  connect(), query(), dbclose()
    */
    var $conn = FALSE;



    /**
    * The query log string
    *
    * @var  string
    * @see  query()
    */
    var $sqllog = "";



    /**
    * The options array for DB2
    *
    * @var  array
    * @see  connect()
    */
    var $options = array('autocommit' => DB2_AUTOCOMMIT_ON);



    /**
    * SQL statement
    *
    */
    var $stmt;



    /**
     * Constructor
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-04-16
     */
    function db_db2()
    {
    }



    /**
    * connect()
    *
    * This function connects to a DB2 database
    *
    * @param   string $host
    * @param   string $username
    * @param   string $password
    * @param   string $db_name
    * @return  boolean TRUE, if connected, otherwise FALSE
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function connect($host, $user, $passwd, $db)
    {
        $this->conn = db2_pconnect($db, $user, $passwd, $this->options);
        if (false == $this->conn) {
            print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
            print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
            print "<head>\n";
            print "    <title>phpMyFAQ Error</title>\n";
            print "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=utf-8\" />\n";
            print "</head>\n";
            print "<body>\n";
            print "<p align=\"center\">The connection to the DB2 server could not be established.</p>\n";
            print "<p align=\"center\">The error message of the DB2 server:<br />".db2_conn_errormsg()."</p>\n";
            print "</body>\n";
            print "</html>";
            return false;
        }
        return true;
    }



    /**
    * query()
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
        return db2_exec($this->conn, $query, array('cursor' => DB2_SCROLLABLE));
    }



    /**
    * escape_string()
    *
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
    * fetch_object()
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
        $_result = db2_fetch_object($result);
        if (is_object($_result)) {
            return (object)array_change_key_case(get_object_vars($_result), CASE_LOWER);
        } else {
            return false;
        }
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
        $_result = db2_fetch_assoc($result);
        if (is_array($_result)) {
            return array_change_key_case($_result, CASE_LOWER);
        } else {
            return false;
        }
    }



    /**
    * num_rows()
    *
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
        return db2_num_rows($result);
    }



    /**
    * insert_id()
    *
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
    * sqllog()
    *
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
    * search()
    *
    * This function generates a result set based on a search string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function search($table, $assoc, $joinedTable = '', $joinAssoc = array(), $match = array(), $string = '', $cond = array())
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
                $where = $where." OR";
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

        foreach ($cond as $field => $data) {
            if (empty($where)) {
                $where .= $field." = ".$data;
            } else {
                $where .= " AND ".$field." = ".$data;
            }
        }

        $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE";

        if (!empty($where)) {
            $query .= " (".$where.")";
        }

        if (is_numeric($string)) {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." = ".$string;
        }

        return $this->query($query);
    }

    /**
    * getTableStatus()
    *
    * This function returns the table status.
    *
    * TODO: Implement this function!
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
    * nextID()
    *
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
        $result = $this->query('SELECT MAX('.$id.') as current_id FROM '.$table);
        $row = $this->fetch_object($result);
        return ($row->current_id + 1);
    }



    /**
    * error()
    *
    * This function returns the last error string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function error()
    {
        return db2_stmt_errormsg();
    }



    /**
    * client_version()
    *
    * This function returns the version string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function client_version()
    {
        $client = db2_client_info($this->conn);
        $ver = $client->DRIVER_NAME.' '.$client->DRIVER_VER;
        return $ver;
    }



    /**
    * server_version()
    *
    * This function returns the version string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function server_version()
    {
        $server = db2_server_info($this->conn);
        $ver = $server->DBMS_NAME.' '.$server->DBMS_VER;
        return $ver;
    }



    /**
    * dbclose()
    *
    * This function closes the connection to the database.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-04-16
    */
    function dbclose()
    {
        return db2_close($this->conn);
    }
}