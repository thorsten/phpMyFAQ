<?php
// phpcs:ignoreFile
/**
 * The string wrapper class using single byte string functions.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
    final public const DEFAULT_ENCODING = 'utf-8';
    /**
     * Instance.
     */
    private static ?StringBasic $instance = null;

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
     */
    public function substr(string $str, int $start, $length = null): string
    {
        $length = null == $length ? strlen($str) : $length;

        return substr($str, $start, $length);
    }

    /**
     * Get position of the first occurrence of a string.
     *
     * @param string $haystack Haystack
     * @param string $needle   Needle
     * @param int    $offset   Offset
     */
    public function strpos(string $haystack, string $needle, int $offset = 0): int
    {
        return strpos($haystack, $needle, $offset);
    }

    /**
     * Make a string lower case.
     *
     * @param string $str String
     */
    public function strtolower(string $str): string
    {
        return strtolower($str);
    }

    /**
     * Make a string upper case.
     *
     * @param string $str String
     */
    public function strtoupper(string $str): string
    {
        return strtoupper($str);
    }

    /**
     * Get occurrence of a string within another.
     *
     * @param string $haystack Haystack
     * @param string $needle Needle
     * @param bool   $part Part
     */
    public function strstr(string $haystack, string $needle, bool $part = false): string|false
    {
        return strstr($haystack, $needle, $part);
    }

    /**
     * Count substring occurrences.
     *
     * @return int
     */
    public function substr_count(string $haystack, string $needle) // phpcs:ignore
    {
        return substr_count($haystack, $needle);
    }

    /**
     * Find position of last occurrence of a char in a string.
     *
     * @return int
     */
    public function strrpos(string $haystack, string $needle, int $offset = 0) // phpcs:ignore
    {
        return strrpos($haystack, $needle, $offset);
    }

    /**
     * Match a regexp.
     *
     * @param null $matches
     * @return int
     */
    public function preg_match(string $pattern, string $subject, &$matches = null, int $flags = 0, int $offset = 0) // phpcs:ignore
    {
        return preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Match a regexp globally.
     *
     * @param string[][] $matches
     * @return int
     */
    public function preg_match_all(string $pattern, string $subject, &$matches, int $flags = 0, int $offset = 0) // phpcs:ignore
    {
        return preg_match_all($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Split string by a regexp.
     */
    public function preg_split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array|bool
    {
        return preg_split($pattern, $subject, $limit, $flags);
    }

    /**
     * Search and replace by a regexp using a callback.
     *
     * @param string|string[] $pattern
     * @param string|string[] $subject
     * @return string|string[]
     */
    public function preg_replace_callback(
        string|array $pattern,
        callable $callback,
        string|array $subject,
        int $limit = -1,
        int &$count = 0
    ): string|array {
        return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
    }

    /**
     * Search and replace by a regexp.
     *
     * @param string|string[] $pattern
     * @param string|string[] $replacement
     * @param string|string[] $subject
     * @return string|string[]|null
     */
    public function preg_replace(
        string|array $pattern,
        string|array $replacement,
        string|array $subject,
        int $limit = -1,
        int &$count = 0
    ): string|array|null {
        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }
}
