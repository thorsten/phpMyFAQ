<?php
/**
 * $Id: DB.php,v 1.1 2007-08-19 21:34:56 johannes Exp $
 *
 * Base interface for database drivers.
 *
 * @author      Johannes Schlüter <johannes@php.net>
 * @package     db_sqlite
 * @since       2007-08-19
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

interface PMF_DB
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
     * @param   string
     * @return  string
     */
    public function escape_string($string);

    /**
     * Fetch a result row as an object
     *
     * This function fetches a result row as an object.
     *
     * @param   mixed $result
     * @return  mixed
     */
    public function fetch_object($result);

    /**
     * Fetch a result row as an object
     *
     * This function fetches a result as an associative array.
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetch_assoc($result);

    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     */
    public function num_rows($result);

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
     * This function generates a result set based on a search string.
     *
     * @access  public
     */
    public function search($table, $assoc, $joinedTable = '', $joinAssoc = array(), $match = array(), $string = '', $cond = array(), $orderBy = array());

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
