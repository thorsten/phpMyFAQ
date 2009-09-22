<?php
/**
 * The PMF_DB_Mysql class provides methods and functions for a MySQL 4.0.x
 * and higher database.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Meikel Katzengreis <meikel@katzengreis.com>
 * @author     Tom Rochester <tom.rochester@gmail.com>
 * @package    2003-02-24
 * @copyright  2003-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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
 * PMF_DB_Mysql
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Meikel Katzengreis <meikel@katzengreis.com>
 * @author     Tom Rochester <tom.rochester@gmail.com>
 * @package    2003-02-24
 * @copyright  2003-2009 phpMyFAQ Team
 * @version    SVN: $Id$
 */
class PMF_DB_Mysql implements PMF_DB_Driver 
{
    /**
     * The connection object
     *
     * @var mixed
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
     * This function connects to a MySQL database
     *
     * @param  string  $host     Hostname
     * @param  string  $username Username
     * @param  string  $password Password
     * @param  string  $db_name  Database name
     * @return boolean TRUE, if connected, otherwise false
     */
    public function connect ($host, $user, $password, $db)
    {
        $this->conn = mysql_connect($host, $user, $password);
        if (empty($db) || $this->conn == false) {
            PMF_Db::errorPage($this->error());
            die();
        }
        return mysql_select_db($db, $this->conn);
    }

    /**
     * This function sends a query to the database.
     *
     * @param  string $query Query string
     * @return mixed
     */
    public function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        $result = mysql_query($query, $this->conn);
        if (!$result) {
        	$this->sqllog .= $this->error();
        }
        
        return $result;
    }

    /**
     * Escapes a string for use in a query
     *
     * @param  string $string String
     * @return string
     */
    public function escape_string($string)
    {
        return mysql_real_escape_string($string, $this->conn);
    }

    /**
     * Fetch a result row as an object
     *
     * @param  mixed $result Resultset
     * @return mixed
     */
    public function fetch_object($result)
    {
        return mysql_fetch_object($result);
    }
    /**
     * Fetch a result row as an object
     *
     * @param  mixed $result Resultset
     * @return mixed
     */
    public function fetch_assoc($result)
    {
        return mysql_fetch_assoc($result);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Mysql
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
     * @param  mixed   $result Resultset
     * @return integer
     */
    public function num_rows($result)
    {
        return mysql_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @return string
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
     * Returns the table status.
     *
     * @return array
     */
    public function getTableStatus()
    {
        $arr = array();
        $result = $this->query("SHOW TABLE STATUS");
        while ($row = $this->fetch_assoc($result)) {
            $arr[$row["Name"]] = $row["Rows"];
        }
        return $arr;
    }

    /**
     * Returns the next ID of a table. This function is a replacement for MySQL's 
     * auto-increment so that we don't need it anymore.
     *
     * @param  string  $table The name of the table
     * @param  string  $id    The name of the ID column
     * @return integer
     */
    public function nextID($table, $id)
    {
    	$select = sprintf("
    	   SELECT
    	       MAX(%s) AS current_id
    	   FROM
    	       %s",
    	   $id,
    	   $table);
    	   
        $result    = $this->query($select);
        $currentID = mysql_result($result, 0, 'current_id');
        return ($currentID + 1);
    }

     /**
      * Returns the error string.
      * 
      * @return void
      */
    public function error()
    {
        return mysql_error();
    }

    /**
     * Returns the client version string.
     * 
     * @return void
     */
    public function client_version()
    {
        return mysql_get_client_info();
    }

    /**
     * Returns the server version string.
     *
     * @return void
     */
    public function server_version()
    {
        return mysql_get_server_info();
    }

    /**
     * Returns an array with all table names
     *
     * @param  string $prefix Table prefix
     * @return void
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix . 'faquser';

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
     * @return void
     */
    public function dbclose()
    {
        if (is_resource($this->conn)) {
            mysql_close($this->conn);
        }
    }
}
