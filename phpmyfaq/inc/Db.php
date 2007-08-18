<?php
/**
* $Id: Db.php,v 1.4 2007-08-18 21:06:50 thorstenr Exp $
*
* The database abstraction factory
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-24
* @copyright:   (c) 2003-2007 phpMyFAQ Team
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

class PMF_Db
{
    /**
     * Database factory
     *
     * @return  void
     * @access  static
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-02
     */
	public static function db_select($type)
    {
        $file = str_replace('\\', '/', __FILE__);
        $dir = substr($file, 0, strrpos($file, "/"));
        $dir .= '/PMF_DB/';
        $type = ucfirst($type);
        if (file_exists($dir.$type.'.php')) {
            require_once $dir . $type . '.php';
            $class = 'db_'.$type;
            return new $class;
        } else {
            trigger_error('Invalid Database Type', E_USER_ERROR);
        }
    }

    /**
     * Returns the single instance
     *
     * @return  object
     * @access  static
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2007-03-02
     */
    public static function &singleton()
    {
        static $obj;

        if (!isset($obj)) {
            $class = __CLASS__;
            $obj = new $class();
        }

        return $obj;
    }

    /**
     * Error page
     *
     * @return  void
     * @access  static
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2007-02-20
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