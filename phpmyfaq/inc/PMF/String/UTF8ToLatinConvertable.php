<?php

/**
 * The string wrapper class using single byte string functions.
 *
 * PHP Version 5.5.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-08
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_String_UTF8ToLatinConvertable.
 *
 * The trick is to use xml utf8 functions to encode and decode
 * utf8 strings. This is useful for strings which could be
 * cleanly converted to iso-8859-1 and back with utf8_decode and
 * utf8_decode, so then non multibyte functions could be used.
 *
 * TODO Cover also nearly complete supported charsets, languages and chars
 *      Notice this article: http://en.wikipedia.org/wiki/ISO_8859-1
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */
class PMF_String_UTF8ToLatinConvertable extends PMF_String_Abstract
{
    /**
     * Instance.
     *
     * @var PMF_String_UTF8ToLatinConvertable
     */
    private static $instance;

    /**
     * Constructor.
     */
    final private function __construct()
    {
    }

    /**
     * Create and return an instance.
     *
     * @param string $language
     *
     * @return PMF_String_Basic
     */
    public static function getInstance($language = 'en')
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->encoding = self::DEFAULT_ENCODING;
            self::$instance->language = PMF_Language::isASupportedLanguage($language) ? $language : self::DEFAULT_LANGUAGE;
        }

        return self::$instance;
    }

    /**
     * Prepare a string to be used with a single byte string function.
     * If the string isn't utf8, presume it's iso.
     *
     * @param string $str
     *
     * @return string
     */
    public function iso($str)
    {
        return self::isUTF8($str) ? utf8_decode($str) : $str;
    }

    /**
     * Convert a string back to it's original charset which is utf8.
     *
     * @param string $str
     *
     * @return string
     */
    public function utf8($str)
    {
        return self::isUTF8($str) ? $str : utf8_encode($str);
    }
    /**
     * Get string character count.
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
     * Get a part of string.
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
     * Get position of the first occurence of a string.
     *
     * @param string $haystack Haystack
     * @param string $needle   Needle
     * @param int    $offset   Offset
     *
     * @return int
     */
    public function strpos($haystack, $needle, $offset = 0)
    {
        return strpos($this->iso($haystack), $this->iso($needle), $offset);
    }

    /**
     * Make a string lower case.
     *
     * @param string $str String
     *
     * @return string
     */
    public function strtolower($str)
    {
        $retval = strtolower($this->iso($str));

        return $this->utf8($retval);
    }

    /**
     * Make a string upper case.
     *
     * @param string $str String
     *
     * @return string
     */
    public function strtoupper($str)
    {
        $retval = strtoupper($this->iso($str));

        return $this->utf8($retval);
    }

    /**
     * Get occurence of a string within another.
     *
     * @param string $haystack Haystack
     * @param string $needle   Needle
     * @param bool   $part     Part
     *
     * @return string|false
     */
    public function strstr($haystack, $needle, $part = false)
    {
        $retval = strstr($this->iso($haystack), $this->iso($needle), $part);

        return $this->utf8($retval);
    }

    /**
     * Get last occurence of a string within another.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return string
     */
    public function strrchr($haystack, $needle)
    {
        $retval = strrchr($this->iso($haystack), $this->iso($needle));

        return $this->utf8($retval);
    }

    /**
     * Count substring occurences.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return int
     */
    public function substr_count($haystack, $needle)
    {
        return substr_count($this->iso($haystack), $this->iso($needle));
    }

    /**
     * Find position of last occurrence of a char in a string.
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     *
     * @return int
     */
    public function strrpos($haystack, $needle, $offset = 0)
    {
        return strrpos($this->iso($haystack), $this->iso($needle), $offset);
    }

    /**
     * Match a regexp.
     *
     * @param string $pattern
     * @param string $subject
     * @param array  &$matches
     * @param int    $flags
     * @param int    $offset
     *
     * @return int
     */
    public function preg_match($pattern, $subject, &$matches = null, $flags = 0, $offset = 0)
    {
        return preg_match(self::appendU($pattern), $subject, $matches, $flags, $offset);
    }

    /**
     * Match a regexp globally.
     *
     * @param string $pattern
     * @param string $subject
     * @param array  &$matches
     * @param int    $flags
     * @param int    $offset
     *
     * @return int
     */
    public function preg_match_all($pattern, $subject, &$matches, $flags = 0, $offset = 0)
    {
        return preg_match_all(self::appendU($pattern), $subject, $matches, $flags, $offset);
    }

    /**
     * Split string by a regexp.
     *
     * @param string $pattern
     * @param string $subject
     * @param int    $limit
     * @param int    $flags
     *
     * @return array
     */
    public function preg_split($pattern, $subject, $limit = -1, $flags = 0)
    {
        return preg_split(self::appendU($pattern), $subject, $limit, $flags);
    }

    /**
     * Search and replace by a regexp using a callback.
     *
     * @param string|array $pattern
     * @param function     $callback
     * @param string|array $subject
     * @param int          $limit
     * @param int          &$count
     *
     * @return array|string
     */
    public function preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = 0)
    {
        if (is_array($pattern)) {
            foreach ($pattern as &$p) {
                $p = self::appendU($p);
            }
        } else {
            $pattern = self::appendU($pattern);
        }

        return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
    }

    /**
     * Search and replace by a regexp.
     *
     * @param string|array $pattern
     * @param string|array $replacement
     * @param string|array $subject
     * @param int          $limit
     * @param int          &$count
     *
     * @return array|string|null
     */
    public function preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = 0)
    {
        if (is_array($pattern)) {
            foreach ($pattern as &$p) {
                $p = self::appendU($p);
            }
        } else {
            $pattern = self::appendU($pattern);
        }

        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }

    /**
     * Append an u to the string. The string is supposed 
     * to be a regex prepared to use with a preg_* function.
     *
     * @param string $str
     *
     * @return string
     */
    private static function appendU($str)
    {
        return ((string) $str).'u';
    }
}
