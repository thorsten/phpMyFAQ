<?php
// phpcs:ignoreFile

/**
 * The main string wrapper class.
 *
 * The class uses mbstring extension if available. It's strongly recommended
 * to use and extend this class instead of using direct string functions. Doing so
 * you guarantees your code is upwards compatible with UTF-8 improvements. All
 * the string methods behaviour is identical to that of the same named
 * single byte string functions.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */

namespace phpMyFAQ;

use phpMyFAQ\Strings\Mbstring;
use phpMyFAQ\Strings\StringBasic;

/**
 * Class Strings
 *
 * @package phpMyFAQ
 */
class Strings
{
    /**
     * Instance.
     *
     * @var Mbstring|StringBasic|null
     */
    private static $instance = null;

    /**
     * Constructor.
     */
    final private function __construct()
    {
    }

    /**
     * Init.
     *
     * @param string $language Language
     */
    public static function init(string $language = 'en'): void
    {
        if (!self::$instance) {
            if (extension_loaded('mbstring') && function_exists('mb_regex_encoding')) {
                self::$instance = Mbstring::getInstance($language);
            } else {
                self::$instance = StringBasic::getInstance($language);
            }
        }
    }

    /**
     * Get current encoding.
     *
     * @return string
     */
    public static function getEncoding(): string
    {
        return self::$instance->getEncoding();
    }

    /**
     * Get string character count.
     *
     * @param string $str String
     * @return int
     */
    public static function strlen(string $str): int
    {
        return self::$instance->strlen($str);
    }

    /**
     * Get a part of string.
     *
     * @param string $string String
     * @param int $start Start
     * @param int|null $length Length
     * @return string
     */
    public static function substr(string $string, int $start, $length = 0): string
    {
        return self::$instance->substr($string, $start, $length);
    }

    /**
     * Get position of the first occurrence of a string.
     *
     * @param string $haystack Haystack
     * @param string $needle Needle
     * @param int    $offset Offset
     * @return int
     */
    public static function strpos(string $haystack, string $needle, $offset = 0): int
    {
        return self::$instance->strpos($haystack, $needle, $offset);
    }

    /**
     * Make a string lower case.
     *
     * @param string $str String
     * @return string
     */
    public static function strtolower(string $str): string
    {
        return self::$instance->strtolower($str);
    }

    /**
     * Make a string upper case.
     *
     * @param string $str String
     * @return string
     */
    public static function strtoupper(string $str): string
    {
        return self::$instance->strtoupper($str);
    }

    /**
     * Get occurrence of a string within another.
     *
     * @param string $haystack Haystack
     * @param string $needle Needle
     * @param bool   $part Part
     * @return string|false
     */
    public static function strstr(string $haystack, string $needle, $part = false)
    {
        return self::$instance->strstr($haystack, $needle, $part);
    }

    /**
     * Set current encoding.
     *
     * @param string $encoding
     */
    public static function setEncoding(string $encoding): void
    {
        self::$instance->setEncoding($encoding);
    }

    /**
     * Count substring occurrences.
     *
     * @param string $haystack
     * @param string $needle
     * @return int
     */
    public static function substr_count(string $haystack, string $needle): int // phpcs:ignore
    {
        return self::$instance->substr_count($haystack, $needle);
    }

    /**
     * Find position of last occurrence of a char in a string.
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     * @return int
     */
    public static function strrpos(string $haystack, string $needle, $offset = 0): int
    {
        return self::$instance->strrpos($haystack, $needle, $offset);
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
    public static function preg_match(
        string $pattern,
        string $subject,
        &$matches = null,
        $flags = 0,
        $offset = 0
    ): int // phpcs:ignore
    {
        return self::$instance->preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Match a regexp globally.
     *
     * @param string $pattern
     * @param string $subject
     * @param null   $matches
     * @param int    $flags
     * @param int    $offset
     * @return int
     */
    public static function preg_match_all(
        string $pattern,
        string $subject,
        &$matches = null,
        $flags = 0,
        $offset = 0
    ): int // phpcs:ignore
    {
        return self::$instance->preg_match_all($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Split string by a regexp.
     *
     * @param string $pattern
     * @param string $subject
     * @param int    $limit
     * @param int    $flags
     * @return string[]|array|false
     */
    public static function preg_split(string $pattern, string $subject, $limit = -1, $flags = 0) // phpcs:ignore
    {
        return self::$instance->preg_split($pattern, $subject, $limit, $flags);
    }

    /**
     * Search and replace by a regexp using a callback.
     *
     * @param string $pattern
     * @param callable $callback
     * @param string|string[] $subject
     * @param int $limit
     * @param int $count
     * @return string|string[]
     */
    public static function preg_replace_callback(
        string $pattern,
        callable $callback,
        $subject,
        $limit = -1,
        &$count = 0
    ) {
        return self::$instance->preg_replace_callback($pattern, $callback, $subject, $limit, $count);
    }

    /**
     * Search and replace by a regexp.
     *
     * @param string|string[] $pattern
     * @param string|string[] $replacement
     * @param string|string[] $subject
     * @param int $limit
     * @param int $count
     * @return string|string[]|null
     */
    public static function preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = 0)
    {
        return self::$instance->preg_replace($pattern, $replacement, $subject, $limit, $count);
    }

    /**
     * Convert special chars to html entities.
     *
     * @param string|null $string The input string.
     * @param int         $quoteStyle Quote style
     * @param string      $charset Character set, UTF-8 by default
     * @param bool        $doubleEncode If set to false, no encoding of existing entities
     * @return string
     */
    public static function htmlspecialchars(
        ?string $string = '',
        int $quoteStyle = ENT_HTML5,
        string $charset = 'utf-8',
        bool $doubleEncode = false
    ): string {
        return htmlspecialchars(
            $string,
            $quoteStyle,
            $charset,
            $doubleEncode
        );
    }

    /**
     * Convert all applicable characters to HTML entities.
     *
     * @param string $string The input string.
     * @param int    $quoteStyle Quote style
     * @param string $charset Character set, UTF-8 by default
     * @param bool   $doubleEncode If set to false, no encoding of existing entities
     * @return string
     */
    public static function htmlentities(
        string $string,
        int $quoteStyle = ENT_HTML5,
        string $charset = 'utf-8',
        bool $doubleEncode = false
    ): string
    {
        return htmlentities(
            $string,
            $quoteStyle,
            $charset,
            $doubleEncode
        );
    }
}
