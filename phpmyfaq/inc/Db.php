<?php
/**
* $Id: Db.php,v 1.1 2006-06-11 14:26:55 matteo Exp $
*
* The database abstraction factory
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-24
* @copyright:   (c) 2003-2006 phpMyFAQ Team
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
     * @access  static
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-02
     */
	function db_select($type)
    {
        $file = str_replace('\\', '/', __FILE__);
        $dir = substr($file, 0, strrpos($file, "/"));
        $dir .= '/PMF_DB/';
        $type = ucfirst($type);
        if (file_exists($dir.$type.'.php')) {
            require_once($dir.$type.'.php');
            $class = 'db_'.$type;
            return new $class;
        } else {
            trigger_error('Invalid Database Type', E_USER_ERROR);
        }
    }
}