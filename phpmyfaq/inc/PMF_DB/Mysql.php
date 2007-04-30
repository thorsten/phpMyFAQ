<?php
/**
 * $Id: Mysql.php,v 1.15 2007-04-30 05:42:32 thorstenr Exp $
 *
 * The db_mysql class provides methods and functions for a MySQL 4.0.x
 * and higher database.
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Meikel Katzengreis <meikel@katzengreis.com>
 * @author      Tom Rochester <tom.rochester@gmail.com>
 * @package     db_mysql
 * @since       2003-02-24
 * @copyright   (c) 2003-2007 phpMyFAQ Team
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

class db_mysql
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
     * Connects to the database.
     *
     * This function connects to a MySQL database
     *
     * @param   string $host
     * @param   string $username
     * @param   string $password
     * @param   string $db_name
     * @return  boolean TRUE, if connected, otherwise false
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2003-02-24
     */
    function connect ($host, $user, $passwd, $db)
    {
        $this->conn = mysql_connect($host, $user, $passwd);
        if (empty($db) || $this->conn == false) {
            PMF_Db::errorPage(mysql_error());
            die();
        }
        return mysql_select_db($db, $this->conn);
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
     * @since   2003-02-24
     */
    function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        return mysql_query($query, $this->conn);
    }

    /**
    * Escapes a string for use in a query
    *
    * @param   string
    * @return  string
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2004-12-16
    */
    function escape_string($string)
    {
        return mysql_real_escape_string($string, $this->conn);
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
     * @since   2003-02-24
     */
    function fetch_object($result)
    {
        return mysql_fetch_object($result);
    }



    /**
     * Fetch a result row as an object
     *
     * This function fetches a result as an associative array.
     *
     * @param   mixed $result
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2003-02-24
     */
    function fetch_assoc($result)
    {
        #print '<pre>';
        #debug_print_backtrace();
        return mysql_fetch_assoc($result);
    }

    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2003-02-24
     */
    function num_rows($result)
    {
        return mysql_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2003-02-24
     */
    function sqllog()
    {
        return $this->sqllog;
    }



    /**
     * Generates a result based on search a search string.
     *
     * This function generates a result set based on a search string.
     * FIXME: can extend to handle operands like google
     *
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @author  Matteo scaramuccia <matteo@scaramuccia.com>
     * @since   2004-08-06
     */
    function search($table, $assoc, $joinedTable = '', $joinAssoc = array(), $match = array(), $string = '', $cond = array(), $orderBy = array())
    {
        $string = $this->escape_string(trim($string));
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
                $where .= $field." = ".$data;
            } else {
                $where .= " AND ".$field." = ".$data;
            }
        }

        $match = implode(",", $match);

        $against = "('".$string."' IN BOOLEAN MODE)";

        if (is_numeric($string)) {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." = ".$string;
        } else {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE MATCH (".$match.") AGAINST ".$against;
        }

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

        return $this->query($query);
    }

     /**
     * Returns the error string.
     *
     * This function returns the table status.
     *
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2004-08-06
     */
    function getTableStatus()
    {
        $arr = array();
        $result = $this->query("SHOW TABLE STATUS");
        while ($row = $this->fetch_assoc($result)) {
            $arr[$row["Name"]] = $row["Rows"];
        }
        return $arr;
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
    * @since   2004-11-30
    */
    function nextID($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') as current_id FROM '.$table);
        $currentID = mysql_result($result, 0, 'current_id');
        return ($currentID + 1);
    }

     /**
     * Returns the error string.
     *
     * This function returns the last error string.
     *
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2004-08-06
     */
    function error()
    {
        return mysql_error();
    }

    /**
     * Returns the client version string.
     *
     * This function returns the version string.
     *
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2004-08-06
     */
    function client_version()
    {
        return mysql_get_client_info();
    }

    /**
     * Returns the server version string.
     *
     * This function returns the version string.
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2004-11-12
     */
    function server_version()
    {
        return mysql_get_server_info();
    }

    /**
     * Returns an array with all table names
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2006-08-21
     */
    function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query('SHOW TABLES'.(('' == $prefix) ? '' : ' LIKE \''.$prefix.'%\''));
        while ($row = $this->fetch_object($result)) {
            foreach ($row as $tableName) {
                if (!in_array($tableName, $this->tableNames)) {
                    $this->tableNames[] = $tableName;
                }
            }
        }
    }

    /**
     * Closes the connection to the database.
     *
     * This function closes the connection to the database.
     *
     * @return    void
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2003-02-24
     */
    function dbclose()
    {
        if (is_resource($this->conn)) {
            mysql_close($this->conn);
        }
    }
}
