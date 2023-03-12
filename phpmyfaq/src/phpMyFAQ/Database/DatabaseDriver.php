<?php

/**
 * Interface for database drivers.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
    public function connect(
        string $host,
        string $user,
        string $password,
        string $database = '',
        int $port = null
    ): ?bool;

    /**
     * This function sends a query to the database.
     *
     * @return mixed $result
     */
    public function query(string $query, int $offset = 0, int $rowcount = 0): mixed;

    /**
     * Escapes a string for use in a query.
     */
    public function escape(string $string): string;

    /**
     * Fetch a result row as an object.
     *
     *
     */
    public function fetchObject(mixed $result): mixed;

    /**
     * Fetch a result row as an array.
     *
     *
     * @return array
     */
    public function fetchArray(mixed $result): ?array;

    /**
     * Fetch a result row.
     */
    public function fetchRow(mixed $result): mixed;

    /**
     * Fetches a complete result as an object.
     *
     * @param mixed $result Result
     *
     * @return array
     */
    public function fetchAll(mixed $result): ?array;

    /**
     * Number of rows in a result.
     */
    public function numRows(mixed $result): int;

    /**
     * Logs the queries.
     */
    public function log(): string;

    /**
     * This function returns the table status.
     *
     * @param string $prefix Table prefix
     */
    public function getTableStatus(string $prefix = ''): array;

    /**
     * Returns the next ID of a table.
     *
     * @param string $table The name of the table
     * @param string $id The name of the ID column
     */
    public function nextId(string $table, string $id): int;

    /**
     * Returns the error string.
     */
    public function error(): string;

    /**
     * Returns the library version string.
     */
    public function clientVersion(): string;

    /**
     * Returns the library version string.
     */
    public function serverVersion(): string;

    /**
     * Returns an array with all table names.
     *
     * @param string $prefix Table prefix
     */
    public function getTableNames(string $prefix = ''): array;

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
     * @return string String that you can pass to SQL as in: SELECT <result of phpMyFAQ\DatabaseDriver->now()>
     */
    public function now(): string;
}
