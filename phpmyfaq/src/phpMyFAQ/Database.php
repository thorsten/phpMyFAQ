<?php

/**
 * The database abstraction factory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

namespace phpMyFAQ;

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
    /**
     * Instance.
     *
     * @var DatabaseDriver
     */
    private static $instance = null;

    /**
     * Database type.
     *
     * @var string
     */
    private static $dbType = null;

    /**
     * Table prefix.
     *
     * @var string
     */
    private static $tablePrefix = null;

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Database factory.
     *
     * @param string $type Database management system type
     *
     * @return Mysqli|Pgsql|Sqlite3|Sqlsrv
     * @throws Exception
     */
    public static function factory($type)
    {
        self::$dbType = $type;

        if (0 === strpos($type, 'pdo_')) {
            $class = 'phpMyFAQ\Database\Pdo_' . ucfirst(substr($type, 4));
        } else {
            $class = 'phpMyFAQ\Database\\' . ucfirst($type);
        }

        if (class_exists($class)) {
            self::$instance = new $class();

            return self::$instance;
        } else {
            throw new Exception('Invalid Database Type: ' . $type);
        }
    }

    /**
     * Returns the single instance.
     *
     * @return DatabaseDriver
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className();
        }

        return self::$instance;
    }

    /**
     * __clone() Magic method to prevent cloning.
     */
    private function __clone()
    {
    }

    /**
     * Returns the database type.
     *
     * @return string
     */
    public static function getType()
    {
        return self::$dbType;
    }

    /**
     * Check if a table is filled with data.
     *
     * @param string $tableName Table name
     *
     * @return bool true, if table is empty, otherwise false
     */
    public static function checkOnEmptyTable($tableName)
    {
        if (
            self::$instance->numRows(
                self::$instance->query('SELECT * FROM ' . self::getTablePrefix() . $tableName)
            ) < 1
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Error page, if the database connection is not possible.
     * @param string $method
     */
    public static function errorPage($method)
    {
        echo '<!DOCTYPE html>
            <html lang="en" class="no-js">
            <head>
                <meta charset="utf-8">
                <title>Fatal phpMyFAQ Error</title>
                <style type="text/css">
                @import url("assets/themes/default/css/style.min.css");
                </style>
            </head>
            <body>
                <div class="container">
                <p class="alert alert-danger">The connection to the database server could not be established.</p>
                <p class="alert alert-danger">The error message of the database server:<br>' . $method . '</p>
                </div>
            </body>
            </html>';
    }

    /**
     * Sets the table prefix.
     *
     * @param string $tablePrefix
     */
    public static function setTablePrefix($tablePrefix)
    {
        self::$tablePrefix = $tablePrefix;
    }

    /**
     * Returns the table prefix.
     *
     * @return string
     */
    public static function getTablePrefix()
    {
        return self::$tablePrefix;
    }
}
