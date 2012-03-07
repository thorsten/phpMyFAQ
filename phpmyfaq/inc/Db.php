<?php
/**
 * The database abstraction factory
 *
 * PHP Version 5.2
 *
 *  http://www.mozilla.org/MPL/
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Db
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Db
 *
 * @category  phpMyFAQ
 * @package   PMF_Db
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */
class PMF_Db
{
    /**
     * Instance
     *
     * @var PMF_Db_Driver
     */
    private static $instance = null;
    
    /**
     * Database type
     *
     * @var string
     */
    private static $dbType = null;
    
    /**
     * Constructor
     *
     */
    private function __construct()
    {
    }
    
    /**
     * Database factory
     *
     * @param string $type Database management system type
     *
     * @return PMF_Db_Driver
     */
    public static function factory($type)
    {
        self::$dbType = $type;
        
        $file = str_replace('\\', '/', __FILE__);
        $dir  = substr($file, 0, strrpos($file, "/")) . '/PMF_DB/';
        $type = ucfirst($type);
        
        if (file_exists($dir . $type . '.php')) {
            require_once $dir . $type . '.php';
            $class          = 'PMF_DB_' . $type;
            self::$instance = new $class;
            return self::$instance;
        } else {
            throw new PMF_Exception('Invalid Database Type: ' . $type);
        }
    }

    /**
     * Returns the single instance
     *
     * @return PMF_Db_Driver
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            $className      = __CLASS__;
            self::$instance = new $className();
        }
        return self::$instance;
    }
   
    /**
     * __clone() Magic method to prevent cloning
     *
     * @return void
     */
    private function __clone()
    {
    }
    
    /**
     * Returns the database type
     *
     * @return string
     */
    public static function getType()
    {
        return self::$dbType;
    }
    
    /**
     * Check if a table is filled with data
     *
     * @param string $tableName Table name
     *
     * @return boolean true, if table is empty, otherwise false
     */
    public static function checkOnEmptyTable($tableName)
    {
        if (self::$instance->numRows(self::$instance->query('SELECT * FROM ' . SQLPREFIX . $tableName)) < 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Error page
     *
     * @return string
     */
    public static function errorPage($method)
    {
        print '<!DOCTYPE html>
            <html lang="en" class="no-js">
            <head>
                <meta charset="utf-8">
                <title>Fatal phpMyFAQ Error</title>
                <style type="text/css">
                @import url(template/default/style.css);
                </style>
            </head>
            <body>
                <p class="error">The connection to the database server could not be established.</p>
                <p class="error">The error message of the database server:<br />$method</p>
            </body>
            </html>';
    }
}