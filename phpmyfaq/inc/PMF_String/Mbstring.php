<?php
/**
 * The string wrapper class using mbstring extension. 
 *
 * @package    phpMyFAQ
 * @subpackage PMF_String
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-04-06
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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
 * PMF_String_Mbstring
 *
 * @package    phpMyFAQ
 * @subpackage PMF_String
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-04-06
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_String_Mbstring extends PMF_String_Abstract
{
    /**
     * Instance
     * 
     * @var object
     */
    private static $instance;
    
    /**
     * 
     * Constructor
     * 
     * @return PMF_String_Mbstring
     */
    private final function __construct()
    {
    }
    
    /**
     * Create and return an instance
     * 
     * @return PMF_String_Mbstring
     */
    public static function getInstance($encoding = null)
    {
        if (!self::$instance) {
            self::$instance = new self;
            self::$instance->encoding = null == $encoding ? self::DEFAULT_ENCODING : $encoding;
        }
        
        return self::$instance;
    }
    
    /**
     * Get string character count
     * 
     * @param string $str String
     * 
     * @return int
     */
    public function strlen($str)
    {
        return mb_strlen($str, $this->encoding);
    }
    
    /**
     * Get a part of string
     * 
     * @param string $str    String
     * @param int    $start  Start
     * @param int    $length Length
     * 
     * @return string
     */
    public function substr($str, $start, $length = null)
    {
        $length = null == $length ? mb_strlen($str) : $length;
        
        return mb_substr($str, $start, $length, $this->encoding);
    }

    /**
     * Get position of the first occurence of a string
     * 
     * @param string $haystack Haystack
     * @param string $needle   Needle
     * @param string $offset   Offset
     * 
     * @return int
     */
    public static function strpos($haystack, $needle, $offset = null)
    {
        return mb_strpos($haystack, $needle, (int) $offset, $this->encoding);
    }
    
    /**
     * Make a string lower case
     * 
     * @param string $str String
     * 
     * @return string
     */
    public static function strtolower($str)
    {
        return mb_strtolower($str, $this->encoding);
    }
        
    /**
     * Make a string upper case
     * 
     * @param string $str String
     * 
     * @return string
     */
    public static function strtoupper($str)
    {
        return mb_strtoupper($str, $this->encoding);
    }
    
    /**
     * Get occurence of a string within another
     * 
     * @param string  $haystack Haystack
     * @param string  $needle   Needle
     * @param boolean $part     Part
     * 
     * @return string|false
     */
    public static function strstr($haystack, $needle, $part = false)
    {
        return mb_strstr($haystack, $needle, (boolean) $part, $this->encoding);
    }
}    
