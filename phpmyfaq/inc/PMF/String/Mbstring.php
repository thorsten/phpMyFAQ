<?php

/**
 * The string wrapper class using mbstring extension. 
 *
 * PHP Version 5.5
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
 * PMF_String_Mbstring.
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
class PMF_String_Mbstring extends PMF_String_Abstract
{
    /**
     * Instance.
     *
     * @var PMF_String_Mbstring
     */
    private static $instance;

    /**
     * Constructor.
     *
     * @return PMF_String_Mbstring
     */
    final private function __construct()
    {
    }

    /**
     * Create and return an instance.
     *
     * @param string $language
     *
     * @return PMF_String_Mbstring
     */
    public static function getInstance($language = 'en')
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->encoding = self::DEFAULT_ENCODING;
            self::$instance->language = PMF_Language::isASupportedLanguage($language) ? $language : self::DEFAULT_LANGUAGE;
            mb_regex_encoding(self::$instance->encoding);
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
        return mb_strlen($str, $this->encoding);
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
        $length = null == $length ? mb_strlen($str) : $length;

        return mb_substr($str, $start, $length, $this->encoding);
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
        return mb_strpos($haystack, $needle, $offset, $this->encoding);
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
        return mb_strtolower($str, $this->encoding);
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
        return mb_strtoupper($str, $this->encoding);
    }

    /**
     * Get first occurence of a string within another.
     *
     * @param string $haystack Haystack
     * @param string $needle   Needle
     * @param bool   $part     Part
     *
     * @return string|false
     */
    public function strstr($haystack, $needle, $part = false)
    {
        return mb_strstr($haystack, $needle, $part, $this->encoding);
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
        return mb_strrchr($haystack, $needle, false, $this->encoding);
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
        return mb_substr_count($haystack, $needle, $this->encoding);
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
        $str = (string)$str;

        return parent::isUTF8($str) ? $str.'u' : $str;
    }
}
