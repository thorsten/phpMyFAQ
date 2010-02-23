<?php
/**
 * Base interface for database drivers.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Johannes Schl�ter <johannes@php.net>
 * @since      2007-08-19
 * @copyright  2007-2009 phpMyFAQ Team
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

interface PMF_DB_Driver
{
    /**
     * Sends a query to the database.
     *
     * This function sends a query to the database.
     *
     * @param   string $query
     * @return  mixed $result
     */
    public function query($query);

    /**
     * Escapes a string for use in a query
     *
     * @param string
     * 
     * @return string
     */
    public function escapeString($string);

    /**
     * Fetch a result row as an object
     *
     * @param resource $result
     * 
     * @return StdObj
     */
    public function fetchObject($result);

    /**
     * Fetch a result row as an associative array
     *
     * @param mixed $result
     * 
     * @return array
     */
    public function fetch_assoc($result);
    
    /**
     * Fetches a complete result as an object
     *
     * @param  resource $result Resultset
     * 
     * @return array
     */
    public function fetchAll($result);

    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     */
    public function numRows($result);

    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     */
    public function sqllog();

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
    public function search($table, Array $assoc, $joinedTable = '', Array $joinAssoc = array(), $match = array(), $string = '', Array $cond = array(), Array $orderBy = array());

    /**
     * This function returns the table status.
     *
     * @access  public
     * @return  array
     */
    public function getTableStatus();

    /**
     * Returns the next ID of a table
     *
     * This function is a replacement for sqlite's auto-increment so that
     * we don't need it anymore.
     *
     * @param   string      the name of the table
     * @param   string      the name of the ID column
     * @return  int
     */
    public function nextID($table, $id);

    /**
     * Returns the error string.
     *
     * This function returns the last error string.
     *
     * @access  public
     */
    public function error();

    /**
     * Returns the libary version string.
     *
     * This function returns the version string.
     *
     * @access  public
     */
    public function client_version();

    /**
     * Returns the libary version string.
     *
     * This function returns the version string.
     *
     * @access  public
     */
    public function server_version();

    /**
     * Returns an array with all table names
     *
     * @access  public
     */
    public function getTableNames($prefix = '');

    /**
     * Closes the connection to the database.
     *
     * This function closes the connection to the database.
     *
     * @access  public
     */
    public function dbclose();
}
