<?php
/**
* $Id: Sqlite.php,v 1.2 2006-01-02 16:51:29 thorstenr Exp $
*
* db_sqlite
*
* The db_sqlite class provides methods and functions for a sqlite database.
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Johannes Schlüter <johannes@php.net>
* @package      db_sqlite
* @since        2005-06-27
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

class db_sqlite
{
    /**
     * The connection object
     *
     * @var   mixed
     * @see   connect(), query(), dbclose()
     */
	var $conn = FALSE;
    
    /**
     * The query log string
     *
     * @var   string
     * @see   query()
     */
	var $sqllog = "";
	
    /**
     * Constructor
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-06-27
     */
	function db_sqlite()
    {
    }
    
    function __construct()
    {
    }
    
    /**
     * Connects to the database.
     *
     * This function connects to a sqlite database
     *
     * @param   string
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-06-27
     */
	function connect ($host, $user = false, $passwd = false, $db = false)
    {
		$this->conn = sqlite_open($host, 0666);
		if (!$this->conn) {
            print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
            print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
            print "<head>\n";
            print "    <title>phpMyFAQ Error</title>\n";
            print "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=utf-8\" />\n";
            print "</head>\n";
            print "<body>\n";
            print "<p align=\"center\">The connection to the sqlite server could not be established.</p>\n";
            print "<p align=\"center\">The error message of the sqlite server:<br />".sqlite_error_string(sqlite_last_error($this->conn))."</p>\n";
            print "</body>\n";
            print "</html>";
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
     * @since   2005-06-27
     */
	function query($query)
    {
		$this->sqllog .= $query."<br />\n";
		return sqlite_query($this->conn, $query);
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
      return sqlite_escape_string($string);
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
     * @since   2005-06-27
     */
	function fetch_object($result)
    {
		return sqlite_fetch_object($result);
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
     * @since   2005-06-27
     */
	function fetch_assoc($result)
    {
		return sqlite_fetch_array($result, SQLITE_ASSOC);
    }
	
    
    
    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-06-27
     */
	function num_rows($result)
    {
		return sqlite_num_rows($result);
    }
	
    /**
     * Returns the ID of the latest insert
     *
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-06-27
     */
	function insert_id($table, $field)
    {
		return sqlite_last_insert_rowid($this->conn);
    }
    
    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-06-27
     */
	function sqllog()
    {
		return $this->sqllog;
    }
	
    
    
	/**
    * Generates a result based on search a search string.
    *
    * This function generates a result set based on a search string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-06-27
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
        
        return $this->query($query);
	}

	/**
    * Returns the error string.
    *
    * This function returns the table status.
    *
    * @access  public
    * @return  array
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-06-27
    */
	function getTableStatus() 
	{
		$arr = array();
        // don't know if that works?
		$result = $this->query("SHOW TABLE STATUS");
		while ($row = $this->fetch_assoc($result)) {
            $arr[$row["Name"]] = $row["Rows"];
        }
		return $arr;
	}

	/**
	* Returns the next ID of a table
	*
	* This function is a replacement for sqlite's auto-increment so that
	* we don't need it anymore.
	*
	* @param   string      the name of the table
	* @param   string      the name of the ID column
	* @return  int
	* @access  public
	* @author  Thorsten Rinne <thorsten@phpmyfaq.de>
	* @since   2005-06-27
	*/
    function nextID($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') AS current_id FROM '.$table);
        $currentID = intval(sqlite_fetch_single($result));
        return ($currentID + 1);
    }
	
	/**
    * Returns the error string.
    *
    * This function returns the last error string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-06-27
    */
	function error()
    {
        return sqlite_error_string(sqlite_last_error($this->conn));
    }

    /**
    * Returns the libary version string.
    *
    * This function returns the version string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-06-27
    */
	function client_version()
    {
        return sqlite_libversion();
    }

    /**
    * Returns the libary version string.
    *
    * This function returns the version string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-06-27
    */
	function server_version()
    {
        return $this->client_version();
    }

    /**
    * Closes the connection to the database.
    *
    * This function closes the connection to the database.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-06-27
    */
	function dbclose()
    {
		return sqlite_close($this->conn);
    }
}