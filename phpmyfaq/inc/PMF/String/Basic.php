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
 * @since     2009-04-06
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_String_Basic.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */
class PMF_String_Basic extends PMF_String_Abstract
{
    /**
     * Instance.
     *
     * @var PMF_String_Basic
     */
    private static $instance;

    /**
     * Default encoding.
     *
     * @var string
     */
    const DEFAULT_ENCODING = 'iso-8859-1';

    /**
     * Constructor.
     *
     * @return PMF_String_Basic
     */
    final private function __construct()
    {
    }

    /**
     * Create and return an instance.
     *
     * @param string $encoding
     * @param string $language
     *
     * @return PMF_String_Basic
     */
    public static function getInstance($encoding = null, $language = 'en')
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->encoding = self::DEFAULT_ENCODING;
            self::$instance->language = PMF_Language::isASupportedLanguage($language) ? $language : self::DEFAULT_LANGUAGE;
        }

        return self::$instance;
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
        return strlen($str);
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
        $length = null == $length ? strlen($str) : $length;

        return substr($str, $start, $length);
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
        return strpos($haystack, $needle, $offset);
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
        return strtolower($str);
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
        return strtoupper($str);
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
        return strstr($haystack, $needle, (boolean) $part);
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
        return strrchr($haystack, $needle);
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
        return substr_count($haystack, $needle);
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
        return strrpos($haystack, $needle, $offset);
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
        return preg_match($pattern, $subject, $matches, $flags, $offset);
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
        return preg_match_all($pattern, $subject, $matches, $flags, $offset);
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
        return preg_split($pattern, $subject, $limit, $flags);
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
        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }
}
