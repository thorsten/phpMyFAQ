<?php
/**
 * The string wrapper class. 
 *
 * @package    phpMyFAQ
 * @subpackage PMF_String
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-04-06
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id: Category.php,v 1.56 2008-01-26 01:02:56 thorstenr Exp $
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

/**
 * PMF_String
 *
 * @package    phpMyFAQ
 * @subpackage PMF_String
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-04-06
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id: String.php,v 1.56 2008-01-26 01:02:56 thorstenr Exp $
 */
class PMF_String
{
    private static $instance;
    
    private final function __construct()
    {
        /**
         * just blocking
         */
    }
    
    /**
     * 
     * Check if the instance exists and create it if needed.
     * @return void
     */
    public static function instance()
    {
        if(!self::$instance) {
            $cwd = dirname(__FILE__);
            if(extension_loaded('mbstring')) {
                require_once "$cwd/PMF_String/Mbstring.php";
                self::$instance = PMF_Mbstring::getInstance();
            } else {
                require_once "$cwd/PMF_String/Basic.php";
                self::$instance = PMF_StringBasic::getInstance();
            }
        }
    }
    
    
    /**
     * Get string character count
     * 
     * @param string $str
     * 
     * @return int
     */
    public function strlen($str)
    {
        self::instance();
        
        return self::$instance->strlen($str);
    }
    

    /**
     * Get a part of string
     * 
     * @param string $str
     * @param int $start
     * @param int $length
     * 
     * @return string
     */
    public function substr($str, $start, $length)
    {
        self::instance();

        return self::$instance->substr($str, $start, $length);
    }
}
