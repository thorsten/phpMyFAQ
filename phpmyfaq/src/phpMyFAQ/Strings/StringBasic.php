<?php
// phpcs:ignoreFile
/**
 * The string wrapper class using single byte string functions.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */

namespace phpMyFAQ\Strings;

use phpMyFAQ\Language;

/**
 * Class Basic
 *
 * @package phpMyFAQ\Strings
 */
class StringBasic extends StringsAbstract
{
    /**
     * Default encoding.
     *
     * @var string
     */
    public const DEFAULT_ENCODING = 'utf-8';
    /**
     * Instance.
     *
     * @var StringBasic
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
     * @param string|null $encoding
     * @param string      $language
     * @return self
     */
    public static function getInstance(string $encoding = null, string $language = 'en'): StringBasic
    {
        if (!(self::$instance instanceof StringBasic)) {
            self::$instance = new self();
            self::$instance->encoding = self::DEFAULT_ENCODING;
            self::$instance->language = Language::isASupportedLanguage($language) ? $language : self::DEFAULT_LANGUAGE;
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
    public static function strlen(string $str): int
    {
        return strlen($str);
    }

    /**
     * Get a part of string.
     *
     * @param string $str String
     * @param int    $start Start
     * @param null   $length Length
     * @return string
     */
    public function substr(string $str, int $start, $length = null)
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
     * Get occurrence of a string within another.
     *
     * @param string $haystack Haystack
     * @param string $needle Needle
     * @param bool   $part Part
     * @return string|false
     */
    public function strstr(string $haystack, string $needle, $part = false)
    {
        return strstr($haystack, $needle, (bool)$part);
    }

    /**
     * Count substring occurrences.
     *
     * @param string $haystack
     * @param string $needle
     * @return int
     */
    public function substr_count(string $haystack, string $needle) // phpcs:ignore
    {
        return substr_count($haystack, $needle);
    }

    /**
     * Find position of last occurrence of a char in a string.
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     * @return int
     */
    public function strrpos(string $haystack, string $needle, $offset = 0) // phpcs:ignore
    {
        return strrpos($haystack, $needle, $offset);
    }

    /**
     * Match a regexp.
     *
     * @param string $pattern
     * @param string $subject
     * @param null   $matches
     * @param int    $flags
     * @param int    $offset
     * @return int
     */
    public function preg_match(string $pattern, string $subject, &$matches = null, $flags = 0, $offset = 0) // phpcs:ignore
    {
        return preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Match a regexp globally.
     *
     * @param string     $pattern
     * @param string     $subject
     * @param string[][] $matches
     * @param int        $flags
     * @param int        $offset
     * @return int
     */
    public function preg_match_all(string $pattern, string $subject, &$matches, $flags = 0, $offset = 0) // phpcs:ignore
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
     * @return string[]|array|false
     */
    public function preg_split(string $pattern, string $subject, $limit = -1, $flags = 0) // phpcs:ignore
    {
        return preg_split($pattern, $subject, $limit, $flags);
    }

    /**
     * Search and replace by a regexp using a callback.
     *
     * @param string|string[] $pattern
     * @param callable $callback
     * @param string|string[] $subject
     * @param int $limit
     * @param int $count
     * @return string|string[]
     */
    public function preg_replace_callback($pattern, callable $callback, $subject, $limit = -1, &$count = 0) // phpcs:ignore
    {
        return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
    }

    /**
     * Search and replace by a regexp.
     *
     * @param string|string[] $pattern
     * @param string|string[] $replacement
     * @param string|string[] $subject
     * @param int $limit
     * @param int $count
     *
     * @return string|string[]|null
     */
    public function preg_replace($pattern, $replacement, $subject, int $limit = -1, int &$count = 0) // phpcs:ignore
    {
        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }
}
