<?php
/**
 * The string wrapper class using mbstring extension. 
 *
 * PHP Version 5.2.0
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
 * 
 * @category  phpMyFAQ
 * @package   PMF_String
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-04-06
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_String_Mbstring
 *
 * @category  phpMyFAQ
 * @package   PMF_String
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-04-06
 */
class PMF_String_Mbstring extends PMF_String_Abstract
{
    /**
     * Instance
     * 
     * @var PMF_String_Mbstring
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
     * @param string $language
     * 
     * @return PMF_String_Mbstring
     */
    public static function getInstance($language = 'en')
    {
        if (!self::$instance) {
            self::$instance = new self;
            self::$instance->encoding = self::DEFAULT_ENCODING;
            self::$instance->language = PMF_Language::isASupportedLanguage($language) ? $language : self::DEFAULT_LANGUAGE;
            mb_regex_encoding(self::$instance->encoding);
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
    public function strpos($haystack, $needle, $offset = 0)
    {
        return mb_strpos($haystack, $needle, $offset, $this->encoding);
    }
    
    /**
     * Make a string lower case
     * 
     * @param string $str String
     * 
     * @return string
     */
    public function strtolower($str)
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
    public function strtoupper($str)
    {
        return mb_strtoupper($str, $this->encoding);
    }
    
    /**
     * Get first occurence of a string within another
     * 
     * @param string  $haystack Haystack
     * @param string  $needle   Needle
     * @param boolean $part     Part
     * 
     * @return string|false
     */
    public function strstr($haystack, $needle, $part = false)
    {
        return mb_strstr($haystack, $needle, $part, $this->encoding);
    }
    
    
    /**
	 * Get last occurence of a string within another
	 * @param string $haystack
	 * @param string $needle
	 * 
	 * @return string
     */
    public function strrchr($haystack, $needle)
    {
        return mb_strrchr($haystack, $needle, false, $this->encoding);
    }
    
    
    /**
     * 
     * Count substring occurences
     * @param string $haystack
     * @param string $needle
     * 
     * @return int
     */
    public function substr_count($haystack, $needle)
    {
        return mb_substr_count($haystack, $needle, $this->encoding);
    }

    
    /**
     * 
     * Match a regexp
     * @param string $pattern
     * @param string $subject
     * @param array &$matches
     * @param int $flags
     * @param int $offset
     * 
     * @return int
     */
    public function preg_match($pattern, $subject, &$matches = null, $flags = 0, $offset = 0)
    {
        return preg_match(self::appendU($pattern), $subject, $matches, $flags, $offset);
    }
    
    
    /**
     * 
     * Match a regexp globally
     * @param string $pattern
     * @param string $subject
     * @param array &$matches
     * @param int $flags
     * @param int $offset
     * 
     * @return int
     */
    public function preg_match_all($pattern, $subject, &$matches, $flags = 0, $offset = 0)
    {
        return preg_match_all(self::appendU($pattern), $subject, $matches, $flags, $offset);
    }
    
    
    /**
     * Split string by a regexp
     * @param string $pattern
     * @param string $subject
     * @param int $limit
     * @param int $flags
     * 
     * @return array
     */
    public function preg_split($pattern, $subject, $limit = -1, $flags = 0)
    {
        return preg_split(self::appendU($pattern), $subject, $limit, $flags);
    }
    
    
    /**
     * Search and replace by a regexp using a callback
     * @param string|array $pattern
     * @param function $callback
     * @param string|array $subject
     * @param int $limit
     * @param int &$count
     * 
     * @return array|string
     */
    public function preg_replace_callback($pattern, $callback, $subject, $limit= -1, &$count = 0)
    {
        if(is_array($pattern)) {
            foreach($pattern as &$p) {
                $p = self::appendU($p);
            }
        } else {
            $pattern = self::appendU($pattern);
        }
        
        return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
    }
    
    
    /**
     * Search and replace by a regexp
     * @param string|array $pattern
     * @param string|array $replacement
     * @param string|array $subject
     * @param int $limit
     * @param int &$count
     * 
     * @return array|string|null
     */
    public function preg_replace($pattern, $replacement, $subject, $limit= -1, &$count = 0)
    {
        if(is_array($pattern)) {
            foreach($pattern as &$p) {
                $p = self::appendU($p);
            }
        } else {
            $pattern = self::appendU($pattern);
        }
        
        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }
    
    
    /**
     * Append an u to the string. The string is supposed 
     * to be a regex prepared to use with a preg_* function
     * 
     * @param string $str
     * 
     * @return string
     */
    private static function appendU($str)
    {
        $str = (string) $str;
        
        return self::isUTF8($str) ?  $str . 'u' : $str;
    }
}    
