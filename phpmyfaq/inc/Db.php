<?php
/**
 * The database abstraction factory
 *
 * @package   phpMyFAQ
 * @package   PMF_Db
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2003-02-24
 * @copyright 2003-2009 phpMyFAQ Team
 * @version   SVN: $Id$
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
 */

/**
 * PMF_Db
 *
 * @package   phpMyFAQ
 * @package   PMF_Db
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2003-02-24
 * @copyright 2003-2009 phpMyFAQ Team
 * @version   SVN: $Id$
 */
class PMF_Db
{
    /**
     * Instance
     * 
     * @var PMF_Db
     */
    private static $instance = null;
    
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
     * @access static
     * @return object
     */
	public static function dbSelect($type)
    {
        $file = str_replace('\\', '/', __FILE__);
        $dir  = substr($file, 0, strrpos($file, "/")) . '/PMF_DB/';
        $type = ucfirst($type);
        if (file_exists($dir . $type . '.php')) {
            require_once $dir . $type . '.php';
            $class          = 'PMF_DB_' . $type;
            self::$instance = new $class;
            return self::$instance;
        } else {
            throw new Exception('Invalid Database Type: ' . $type);
        }
    }

    /**
     * Returns the single instance
     *
     * @access static
     * @return object
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
     * __clone() Magic method to prevent cloning
     * 
     * @return void
     */
    public function __clone()
    {
        throw new Exception('Illegally attempted to clone ' . __CLASS__);
    }

    /**
     * Error page
     *
     * @access static
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
        print "    @import url(template/style.css);\n";
        print "    @import url(template/colors.css);\n";
        print "    --> /*]]>*/ </style>\n";
        print "</head>\n";
        print "<body>\n";
        print "<p align=\"center\">The connection to the database server could not be established.</p>\n";
        print "<p align=\"center\">The error message of the database server:<br />".$method."</p>\n";
        print "</body>\n";
        print "</html>";
    }
}