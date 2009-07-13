<?php
/**
 * The db_pgsql class provides methods and functions for a PostgreSQL
 * database.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
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

class PMF_DB_Pgsql implements PMF_DB_Driver
{
    /**
     * The connection object
     *
     * @var object
     */
    private $conn = null;

    /**
     * The query log string
     *
     * @var string
     */
    public $sqllog = '';

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
     * @param   string $host
     * @param   string $username
     * @param   string $password
     * @param   string $db_name
     * @return  boolean true, if connected, otherwise false
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2003-02-24
     */
    public function connect ($host, $user, $passwd, $db)
    {
        /* if you use mysql_pconnect(), remove the next line: */
        $this->conn = pg_pconnect('host='.$host.' port=5432 dbname='.$db.' user='.$user.' password='.$passwd);
        /* comment out for more speed with mod_php or on Windows */
        // $this->conn = @pg_pconnect("host=$host port=5432 dbname=$db user=$user password=$passwd");
        if (empty($db) || $this->conn == false) {
            PMF_Db::errorPage(pg_last_error($this->conn));
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
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2003-02-24
     */
    public function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        return pg_query($this->conn, $query);
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
    public function escape_string($string)
    {
        return pg_escape_string($this->conn, $string);
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
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2003-02-24
     */
    public function fetch_object($result)
    {
        return pg_fetch_object($result);
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
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2003-02-24
     */
    public function fetch_assoc($result)
    {
        return pg_fetch_array($result, NULL, PGSQL_ASSOC);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Pgsql
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
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2003-02-24
     */
    public function num_rows($result)
    {
        return pg_num_rows($result);
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
    public function sqllog()
    {
        return $this->sqllog;
    }

    /**
     * Closes the connection to the database.
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2003-02-24
     */
    public function dbclose()
    {
        return pg_close($this->conn);
    }

    /**
     * Returns just one row
     *
     * @param  string
     * @return string
     * @author Tom Rochester <tom.rochester@gmail.com>
     * @since  2004-08-06
     */
    private function getOne($query)
    {
        $row = pg_fetch_row($this->query($query));
        return $row[0];
    }

    /**
     * This function returns the table status.
     *
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2004-08-06
     */
    public function getTableStatus()
    {
        $select = "SELECT relname FROM pg_stat_user_tables ORDER BY relname;";
        $arr = array();
        $result = $this->query($select);
        while ($row = $this->fetch_assoc($result)) {
            $count = $this->getOne("SELECT count(1) FROM ".$row["relname"].";");
            $arr[$row["relname"]] = $count;
        }
        return $arr;
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
    public function search($table, Array $assoc, $joinedTable = '', Array $joinAssoc = array(), $match = array(), $string = '', Array $cond = array(), Array $orderBy = array())
    {
        $string = pg_escape_string(trim($string));
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

        $match = implode("|| ' ' ||", $match);

        if (is_numeric($string)) {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." = ".$string;
        } else {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE (".$match.") ILIKE ('%".$string."%')";

            if (!empty($where)) {
                $query .= " AND (".$where.")";
            }
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
    public function nextID($table, $id)
    {
        $result = $this->query("SELECT nextval('".$table."_".$id."_seq') as current_id;");
        $currentID = pg_result($result, 0, 'current_id');
        return ($currentID);
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
    public function error()
    {
        return pg_last_error();
    }

    /**
     * This function returns the client version string.
     *
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2004-08-06
     */
    public function client_version()
    {
        $pg_version = pg_version($this->conn);
        if (isset($pg_version['client'])) {
            return $pg_version['client'];
        } else {
            return 'n/a';
        }
    }

    /**
     * Returns the server version string.
     *
     * @access  public
     * @author  Thorsten Rinne
     * @since   2004-11-12
     */
    public function server_version()
    {
        $pg_version = pg_version($this->conn);
        if (isset($pg_version['server_version'])) {
            return $pg_version['server_version'];
        } else {
            return 'n/a';
        }
    }

    /**
     * Returns an array with all table names
     *
     * @access  public
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2006-08-25
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query('SELECT relname FROM pg_stat_user_tables '.(('' == $prefix) ? '' : 'LIKE \''.$prefix.'%\' ').'ORDER BY relname');
        while ($row = $this->fetch_object($result)) {
            foreach ($row as $tableName) {
                if (!in_array($tableName, $this->tableNames)) {
                    $this->tableNames[] = $tableName;
                }
            }
        }
    }
}
