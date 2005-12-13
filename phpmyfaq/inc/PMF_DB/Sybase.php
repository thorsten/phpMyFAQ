<?php
/**
* $Id: Sybase.php,v 1.1 2005-12-13 19:57:23 thorstenr Exp $
*
* db_sybase
*
* The db_sybase class provides methods and functions for a Sybase database
*
* @author       Adam Greene <phpmyfaq@skippy.fastmail.fm>
* @package      db_sybase
* @since        2004-12-10
* @version      2004-12-10
*
* Copyright:    (c) 2003-2004 phpMyFAQ Team
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

class db_sybase
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
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2003-02-24
     */
	function db_sybase()
    {
        return $this->__construct();
    }
    
    function __construct()
    {
        if (function_exists('sybase_min_client_severity')) {
            sybase_min_client_severity(100);
        }

        if (function_exists('sybase_min_server_severity')) {
            sybase_min_server_severity(9);
        }

        if (function_exists('sybase_min_error_severity')) {
            sybase_min_error_severity(9);
        }

        if (function_exists('sybase_min_message_severity')) {
            sybase_min_message_severity(9);
        }
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
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function connect ($host, $user, $passwd, $db)
    {
		$this->conn = @sybase_pconnect($host, $user, $passwd);
		if (empty($db) OR $this->conn == FALSE) {
            print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
            print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
            print "<head>\n";
            print "    <title>phpMyFAQ Error</title>\n";
            print "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=utf-8\" />\n";
            print "</head>\n";
            print "<body>\n";
            print "<p align=\"center\">The connection to the Sybase server could not be established.</p>\n";
            print "<p align=\"center\">The error message of the Sybase server:<br />".error()."</p>\n";
            print "</body>\n";
            print "</html>";
            return FALSE;
        }
		return @sybase_select_db($db, $this->conn);
    }
	
    /**
     * Sends a query to the database.
     *
     * This function sends a query to the database.
     *
     * @param   string $query
     * @return  mixed $result
     * @access  public
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function query($query)
    {
		$this->sqllog .= $query."<br />\n";
		return @sybase_query($query, $this->conn);
    }
    
    /**
    * Escapes a string for use in a query
    *
    * @param   string
    * @return  string
    * @access  public
    * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
    * @since   2004-12-16
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
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function fetch_object($result)
    {
		return @sybase_fetch_object($result);
    }
	
	
    
    /**
     * Fetch a result row as an array
     *
     * This function fetches a result as an associative array.
     *
     * @param   mixed $result
     * @return  array
     * @access  public
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function fetch_assoc($result)
    {
      if (!function_exists('sybase_fetch_assoc')){
        $rs = @sybase_fetch_array($result);
      } else {
        $rs = @sybase_fetch_assoc($result);
      }
		return $rs;
    }
	
    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function num_rows($result)
    {
		return @sybase_num_rows($result);
    }
	
    /**
     * Returns the ID of the latest insert
     *	
     * @return  integer
     * @access  public
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */

	function insert_id($table, $field)
  {
		$sql = "SELECT max($field) FROM $table ";
 		$rs=sybase_query($sql, $this->conn);
 		$row=sybase_fetch_row($rs);
    if (isset($row[0]) && $row[0] > 0){
      return $row[0] + 1;
    }
		return 1;
  }
    
    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
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
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
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
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function getTableStatus()
	{
	  $select = "SELECT SO.name, STS.rowcnt FROM sysobjects SO ".
      "INNER JOIN systabstats STS ON STS.id = SO.id ".
      "WHERE SO.type = 'U'";
		$arr = Array();
		$result = $this->query($select);
		while ($row = $this->fetch_assoc($result)) {
            $arr[$row["name"]] = intval($row["rowcnt"]);
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
	* @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
	* @since   2004-12-10
	*/
	function nextID($table, $id)
	{
	    $result = $this->query('SELECT max('.$id.') as current_id FROM '.$table);
	    $currentID = sybase_result($result, 0, 'current_id');
	    return ($currentID + 1);
	}
	
	 /**
     * Returns the error string.
     *
     * This function returns the last error string.
     *
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function error()
    {
        return @sybase_get_last_message();
    }

    /**
     * Returns the client version string.
     *
     * This function returns the version string.
     *
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2004-12-10
     */
	function client_version()
    {
        return "Sybase -- Unsupported";
    }

    /**
     * Returns the server version string.
     *
     * This function returns the version string.
     *
     * @access  public
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function server_version()
    {
        $result = $this->query('SELECT @@version AS SERVER_VERSION');
        $errormsg = sybase_result($result, 0, 'SERVER_VERSION');
        if (isset($errormsg)) {
            return $errormsg;
        }
    }

    /**
     * Closes the connection to the database.
     *
     * This function closes the connection to the database.
     *
     * @access  public
     * @author  Adam Greene <phpmyfaq@skippy.fastmail.fm>
     * @since   2004-12-10
     */
	function dbclose()
    {
		return @sybase_close($this->conn);
    }

}
?>