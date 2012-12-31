<?php
/**
 * The database abstraction factory
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 *  http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Db
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
	public static function dbSelect($type)
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
        if (self::$instance->num_rows(self::$instance->query('SELECT * FROM ' . SQLPREFIX . $tableName)) < 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Error page
     *
     * @return void
     */
    public static function errorPage($method)
    {
        print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
        print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
        print "<head>\n";
        print "    <title>phpMyFAQ Error</title>\n";
        print "    <meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=utf-8\" />\n";
        print "    <style type=\"text/css\" media=\"screen\"> /*<![CDATA[*/ <!--\n";
        print "    @import url(template/default/style.css);\n";
        print "    --> /*]]>*/ </style>\n";
        print "</head>\n";
        print "<body>\n";
        print "<p align=\"center\">The connection to the database server could not be established.</p>\n";
        print "<p align=\"center\">The error message of the database server:<br />".$method."</p>\n";
        print "</body>\n";
        print "</html>";
    }
}