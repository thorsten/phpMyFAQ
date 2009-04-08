<?php
/**
 * The string wrapper class using single byte string functions.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_String
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-04-08
 * @version    SVN: $Id: Basic.php 4153 2009-04-08 05:54:08Z thorsten $
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
 * PMF_String_UTF8ToLatinConvertable
 *
 * The trick is to use xml utf8 functions to encode and decode
 * utf8 strings. This is useful for strings which could be
 * cleanly converted to iso-8859-1 and back with utf8_decode and
 * utf8_decode, so then non multibyte functions could be used.
 *
 * TODO Cover also nearly complete supported charsets, languages and chars
 *      Notice this article: http://en.wikipedia.org/wiki/ISO_8859-1
 * 
 * @package    phpMyFAQ
 * @subpackage PMF_String
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-04-08
 * @version    SVN: $Id: Basic.php 4153 2009-04-08 05:54:08Z thorsten $
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_String_UTF8ToLatinConvertable extends PMF_String_Abstract
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
     * @return PMF_String_Basic
     */
    private final function __construct()
    {
    }
 
    
    /**
     * Create and return an instance
     * 
     * @param string $encoding
     * @param string $language
     * 
     * @return PMF_String_Basic
     */
    public static function getInstance($encoding = null, $language = 'en')
    {
        if (!self::$instance) {
            self::$instance = new self;
            self::$instance->encoding = null == $encoding ? self::DEFAULT_ENCODING : $encoding;
            self::$instance->language = PMF_Init::isASupportedLanguage($language) ? $language : self::DEFAULT_LANGUAGE;
        }
       
        return self::$instance;
    }
    
    
    /**
	 * Prepare a string to be used with a single byte string function.
	 * If the string isn't utf8, presume it's iso
	 * @param string $str
	 * 
	 * @return string
     */
    public function iso($str)
    {
        return isUTF8($str) ? utf8_decode($str) : $str;
    }
    
    
    /**
	 * Convert a string back to it's original charset which is utf8
	 * @param string $str
	 * 
	 * @return string
     */
    public function utf8($str)
    {
        return utf8_encode($str);
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
        return strlen($this->iso($str));
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
        $length = null == $length ? $this->strlen($str) : $length;
        
        $retval = substr($this->iso($str), $start, $length);
        
        return $this->utf8($retval);
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
        return strpos($this->iso($haystack), $this->iso($needle), (int) $offset);
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
        $retval = strtolower($this->iso($str));
        
        return $this->utf8($retval);
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
        $retval = strtoupper($this->iso($str));
        
        return $this->utf8($retval);
    }
    
    /**
     * Get occurence of a string within another
     * 
     * @param string $haystack Haystack
     * @param string $needle   Needle
     * @param boolean $part    Part
     * 
     * @return string|false
     */
    public static function strstr($haystack, $needle, $part = false)
    {
        $retval = strstr($this->iso($haystack), $this->iso($needle), (boolean) $part);
        
        return $this->utf8($retval);
    }   
}
