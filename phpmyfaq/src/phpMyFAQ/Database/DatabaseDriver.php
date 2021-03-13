<?php

/**
 * Interface for database drivers.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-08-19
 */

namespace phpMyFAQ\Database;

/**
 * Interface DatabaseDriver
 *
 * @package phpMyFAQ\Database
 */
interface DatabaseDriver
{
    /**
     * Connects to the database server.
     *
     * @param string $host Hostname
     * @param string $user Username
     * @param string $password Password
     * @param string $database Database name
     * @param int|null $port
     * @return null|bool true, if connected, otherwise false
     */
    public function connect(string $host, string $user, string $password, $database = '', $port = null): ?bool;

    /**
     * This function sends a query to the database.
     *
     * @param string $query
     * @param int $offset
     * @param int $rowcount
     *
     * @return mixed $result
     */
    public function query(string $query, $offset = 0, $rowcount = 0);

    /**
     * Escapes a string for use in a query.
     *
     * @param string
     *
     * @return string
     */
    public function escape($string): string;

    /**
     * Fetch a result row as an object.
     *
     * @param mixed $result
     *
     * @return mixed
     */
    public function fetchObject($result);

    /**
     * Fetch a result row as an array.
     *
     * @param mixed $result
     *
     * @return array
     */
    public function fetchArray($result): ?array;

    /**
     * Fetch a result row.
     * @param $result
     * @return false|mixed
     */
    public function fetchRow($result);

    /**
     * Fetches a complete result as an object.
     *
     * @param mixed $result Resultset
     *
     * @return array
     */
    public function fetchAll($result): ?array;

    /**
     * Number of rows in a result.
     *
     * @param mixed $result
     *
     * @return int
     */
    public function numRows($result): int;

    /**
     * Logs the queries.
     *
     * @return string
     */
    public function log(): string;

    /**
     * This function returns the table status.
     *
     * @param string $prefix Table prefix
     *
     * @return array
     */
    public function getTableStatus($prefix = ''): array;

    /**
     * Returns the next ID of a table.
     *
     * @param string The name of the table
     * @param string      the name of the ID column
     *
     * @return int
     */
    public function nextId($table, $id): int;

    /**
     * Returns the error string.
     *
     * @return string
     */
    public function error(): string;

    /**
     * Returns the library version string.
     *
     * @return string
     */
    public function clientVersion(): string;

    /**
     * Returns the library version string.
     *
     * @return string
     */
    public function serverVersion(): string;

    /**
     * Returns an array with all table names.
     *
     * @param string $prefix Table prefix
     *
     * @return array
     */
    public function getTableNames($prefix = ''): array;

    /**
     * Closes the connection to the database.
     */
    public function close();

    /**
     * Return SQL expression that yields current datetime in the local timezone.
     * The actual SQL value may be of SQL datetime type (or timestamp or similar)
     * or it may be varchar/text (as is in SQLite3) - so make sure the consumer
     * code doesn't depend on the actual type.
     *
     * @return string String that you can pass to SQL as in: SELECT <result of phpMyFAQ\Db_Driver_instance->now()>
     */
    public function now(): string;
}
