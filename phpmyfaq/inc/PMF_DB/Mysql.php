<?php
/**
* $Id: Mysql.php,v 1.3 2006-05-28 17:21:48 thorstenr Exp $
*
* db_mysql
*
* The db_mysql class provides methods and functions for a MySQL 3.23.x
* and 4.0.x database.
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Meikel Katzengreis <meikel@katzengreis.com>
* @author       Tom Rochester <tom.rochester@gmail.com>
* @package      db_mysql
* @since        2003-02-24
*
* Copyright:    (c) 2003-2006 phpMyFAQ Team
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
	var $conn = FALSE;

    /**
     * The query log string
     *
     * @var   string
     * @see   query()
     */
	var $sqllog = "";

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
     * @since   2003-02-24
     */
	function connect ($host, $user, $passwd, $db)
    {
		$this->conn = mysql_pconnect($host, $user, $passwd);
		if (empty($db) OR $this->conn == FALSE) {
            print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
            print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
            print "<head>\n";
            print "    <title>phpMyFAQ Error</title>\n";
            print "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=utf-8\" />\n";
            print "</head>\n";
            print "<body>\n";
            print "<p align=\"center\">The connection to the MySQL server could not be established.</p>\n";
            print "<p align=\"center\">The error message of the MySQL server:<br />".mysql_error()."</p>\n";
            print "</body>\n";
            print "</html>";
            return FALSE;
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
		$this->sqllog .= $query."<br />\n";
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
      if (function_exists('mysql_real_escape_string')) {
          return mysql_real_escape_string($string, $this->conn);
      } else {
          return mysql_escape_string($string);
      }
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
     * Returns the ID of the latest insert
     *
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2003-02-24
     */
	function insert_id($table, $field)
    {
		$result = $this->query('SELECT max('.$field.') AS last_id FROM '.$table);
		return mysql_result($result, 0, 'last_id');
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
     * @access  public
     * @author  Tom Rochester <tom.rochester@gmail.com>
     * @since   2004-08-06
     */
	function search($table, $assoc, $joinedTable = '', $joinAssoc = array(), $match = array(), $string = '', $cond = array())
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

		if (version_compare($this->server_version(), '4.0.1') < 0)  {
            $against = "('".$string."')"; // Search with MySQL 3.23.23+
        } else {
            $against = "('".$string."' IN BOOLEAN MODE)"; // Search with MySQL 4.0.1+
		}

        if (is_numeric($string)) {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." = ".$string;
        } else {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE MATCH (".$match.") AGAINST ".$against;
        }

		if (!empty($where)) {
			$query .= " AND (".$where.")";
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
     * Closes the connection to the database.
     *
     * This function closes the connection to the database.
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2003-02-24
     */
	function dbclose()
    {
		return mysql_close($this->conn);
    }

}