<?php
// phpcs:ignoreFile

/**
 * The main string wrapper class.
 *
 * The class uses the mbstring extension if available. It's strongly recommended
 * to use and extend this class instead of using direct string functions. Doing so,
 * you guarantee your code is upwards compatible with UTF-8 improvements. All
 * the string methods behavior is identical to that of the same named
 * single byte string functions.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     */
    private static null|Mbstring|StringBasic $instance = null;

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
        if (self::$instance === null) {
            if (extension_loaded('mbstring') && function_exists('mb_regex_encoding')) {
                self::$instance = Mbstring::getInstance($language);
            } else {
                self::$instance = StringBasic::getInstance($language);
            }
        }
    }

    /**
     * Get string character count.
     *
     * @param string $str String
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
     */
    public static function substr(string $string, int $start, ?int $length = 0): string
    {
        return self::$instance->substr($string, $start, $length);
    }

    /**
     * Make a string lower case.
     *
     * @param string $str String
     */
    public static function strtolower(string $str): string
    {
        return self::$instance->strtolower($str);
    }

    /**
     * Make a string upper case.
     *
     * @param string $str String
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
     */
    public static function strstr(string $haystack, string $needle, bool $part = false): string|false
    {
        return self::$instance->strstr($haystack, $needle, $part);
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
        int $flags = 0,
        int $offset = 0
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
        int $flags = 0,
        int $offset = 0
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
     * @return array|bool
     */
    public static function preg_split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array|bool // phpcs:ignore
    {
        return self::$instance->preg_split($pattern, $subject, $limit, $flags);
    }

    /**
     * Search and replace by a regexp using a callback.
     *
     * @param string          $pattern
     * @param callable        $callback
     * @param string|string[] $subject
     * @param int             $limit
     * @param int             $count
     * @return string|string[]
     */
    public static function preg_replace_callback(
        string $pattern,
        callable $callback,
        string|array $subject,
        int $limit = -1,
        int &$count = 0
    ): string|array {
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
    public static function preg_replace(
        string|array $pattern,
        string|array $replacement,
        string|array $subject,
        int $limit = -1,
        int &$count = 0
    ): string|array|null {
        return self::$instance->preg_replace($pattern, $replacement, $subject, $limit, $count);
    }

    /**
     * Convert special chars to HTML entities.
     *
     * @param string|null $string The input string.
     * @param int         $quoteStyle Quote style
     * @param string      $charset Character set, UTF-8 by default
     * @param bool        $doubleEncode If set to false, no encoding of existing entities
     */
    public static function htmlspecialchars(
        ?string $string = '',
        int $quoteStyle = ENT_HTML5,
        string $charset = 'utf-8',
        bool $doubleEncode = false
    ): string {
        return htmlspecialchars(
            (string) $string,
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
