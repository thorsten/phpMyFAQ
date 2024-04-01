<?php

/**
 * The database abstraction factory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Mysqli;
use phpMyFAQ\Database\Pgsql;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Database\Sqlsrv;

/**
 * Class Database
 *
 * @package phpMyFAQ
 */
class Database
{
    private static ?DatabaseDriver $databaseDriver = null;

    /** @var string Database type. */
    private static string $dbType;

    /** @var string|null Table prefix */
    private static ?string $tablePrefix = null;

    /**
     * Database factory.
     *
     * @param string $type Database management system type
     * @throws Exception
     */
    public static function factory(string $type): ?DatabaseDriver
    {
        self::$dbType = $type;

        if (str_starts_with($type, 'pdo_')) {
            $class = 'phpMyFAQ\Database\Pdo_' . ucfirst(substr($type, 4));
        } else {
            $class = 'phpMyFAQ\Database\\' . ucfirst($type);
        }

        if (class_exists($class)) {
            self::$databaseDriver = new $class();
            return self::$databaseDriver;
        }

        throw new Exception('Invalid Database Type: ' . $type);
    }

    /**
     * Returns the single instance.
     */
    public static function getInstance(): ?DatabaseDriver
    {
        if (null == self::$databaseDriver) {
            $className = self::class;
            self::$databaseDriver = new $className();
        }

        return self::$databaseDriver;
    }

    /**
     * Returns the database type as string
     */
    public static function getType(): string
    {
        return self::$dbType;
    }

    /**
     * Check if a table is filled with data.
     *
     * @param string $tableName Table name
     * @return bool true, if the table is empty, otherwise false
     */
    public static function checkOnEmptyTable(string $tableName): bool
    {
        return self::$databaseDriver->numRows(
            self::$databaseDriver->query('SELECT * FROM ' . self::getTablePrefix() . $tableName)
        ) < 1;
    }

    /**
     * Error page, if the database connection is not possible.
     */
    public static function errorPage(string $method): void
    {
        echo '<!DOCTYPE html>
            <html lang="en" class="no-js">
            <head>
                <meta charset="utf-8">
                <title>Fatal phpMyFAQ Error</title>
                <link href="assets/dist/styles.css" rel="stylesheet">
                <script src="assets/dist/frontend.js"></script>
            </head>
            <body>
                <div class="container mt-5">
                <p class="alert alert-danger">The connection to the database server could not be established.</p>
                <p class="alert alert-danger">The error message of the database server: ' . $method . '</p>
                </div>
            </body>
            </html>';
    }

    /**
     * Sets the table prefix.
     */
    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
    }

    /**
     * Returns the table prefix.
     */
    public static function getTablePrefix(): ?string
    {
        return self::$tablePrefix;
    }
}
