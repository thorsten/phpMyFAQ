<?php
/**
 * Base interface for database drivers.
 * 
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-08-19
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_DB_Driver
 * 
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-08-19
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
     * Fetches a complete result as an object
     *
     * @param  resource      $result Resultset
     * @return PMF_DB_Driver
     */
    public function fetchAll($result);

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
     * Move internal result pointer
     *
     * Moves the pointer within the query result to a specified location, or
     * to the beginning if nothing is specified.
     *
     * @param resource $result    Resultset
     * @param integer  $rowNumber Row number
     * 
     * @return boolean
     */
    public function resultSeek($result, $rowNumber);
    
    /**
     * Closes the connection to the database.
     *
     * This function closes the connection to the database.
     *
     * @access  public
     */
    public function dbclose();
}
