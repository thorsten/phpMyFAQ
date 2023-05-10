<?php
// phpcs:ignoreFile
/**
 * The string wrapper class using a mbstring extension.
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
use phpMyFAQ\Strings\StringsAbstract;

/**
 * Class Mbstring
 *
 * @package phpMyFAQ\Strings
 */
class Mbstring extends StringsAbstract
{
    /**
     * Instance.
     */
    private static ?Mbstring $instance = null;

    /**
     * Constructor.
     */
    final private function __construct()
    {
    }

    /**
     * Create and return an instance.
     */
    public static function getInstance(string $language = 'en'): Mbstring
    {
        if (!(self::$instance instanceof Mbstring)) {
            self::$instance = new self();
            self::$instance->encoding = self::DEFAULT_ENCODING;
            self::$instance->language = Language::isASupportedLanguage($language) ? $language : self::DEFAULT_LANGUAGE;
            mb_regex_encoding(self::$instance->encoding);
        }

        return self::$instance;
    }

    /**
     * Get string character count.
     *
     * @param string $str String
     */
    public function strlen(string $str): int
    {
        return mb_strlen($str, $this->encoding);
    }

    /**
     * Get a part of string.
     *
     * @param string   $str String
     * @param int      $start Start
     * @param int|null $length Length
     */
    public function substr(string $str, int $start, int $length = null): string
    {
        $length = null == $length ? mb_strlen($str) : $length;

        return mb_substr($str, $start, $length, $this->encoding);
    }

    /**
     * Get position of the first occurrence of a string.
     *
     * @param string $haystack Haystack
     * @param string $needle Needle
     * @param int    $offset Offset
     */
    public function strpos(string $haystack, string $needle, int $offset = 0): int
    {
        return mb_strpos($haystack, $needle, $offset, $this->encoding);
    }

    /**
     * Make a string lower case.
     *
     * @param string $str String
     */
    public function strtolower(string $str): string
    {
        return mb_strtolower($str, $this->encoding);
    }

    /**
     * Make a string upper case.
     *
     * @param string $str String
     */
    public function strtoupper(string $str): string
    {
        return mb_strtoupper($str, $this->encoding);
    }

    /**
     * Get the first occurrence of a string within another.
     *
     * @param string $haystack Haystack
     * @param string $needle Needle
     * @param bool $part Part
     */
    public function strstr(string $haystack, string $needle, bool $part = false): string|false
    {
        return mb_strstr($haystack, $needle, $part, $this->encoding);
    }

    /**
     * Count substring occurrences.
     */
    public function substr_count(string $haystack, string $needle): int
    {
        return mb_substr_count($haystack, $needle, $this->encoding);
    }

    /**
     * Match a regexp.
     *
     * @param        $matches
     */
    public function preg_match(string $pattern, string $subject, &$matches = null, int $flags = 0, int $offset = 0): int
    {
        return preg_match(self::appendU($pattern), $subject, $matches, $flags, $offset);
    }

    /**
     * Match a regexp globally.
     *
     * @param string[][] $matches
     * @param int $flags
     * @param int $offset
     *
     */
    public function preg_match_all(string $pattern, string $subject, array &$matches, $flags = 0, $offset = 0): int
    {
        return preg_match_all(self::appendU($pattern), $subject, $matches, $flags, $offset);
    }

    /**
     * Split string by a regexp.
     *
     * @param int $limit
     * @param int $flags
     *
     */
    public function preg_split(string $pattern, string $subject, $limit = -1, $flags = 0): array|bool
    {
        return preg_split(self::appendU($pattern), $subject, $limit, $flags);
    }

    /**
     * Search and replace by a regexp using a callback.
     *
     * @param string|string[] $pattern
     * @param string|string[] $subject
     *
     * @return string|string[]
     */
    public function preg_replace_callback(string|array $pattern, callable $callback, string|array $subject, int $limit = -1, int &$count = 0): string|array
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
     * @param string|string[] $pattern
     * @param string|string[] $replacement
     * @param string|string[] $subject
     *
     * @return string|string[]|null
     */
    public function preg_replace(
        string|array $pattern,
        string|array $replacement,
        string|array $subject,
        int $limit = -1,
        int &$count = 0
    ): string|array|null {
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
     * Append a "u" to the string.
     * The string is supposed to be a regex prepared to use with a preg_* function.
     */
    private static function appendU(string $str): string
    {
        return parent::isUTF8($str) ? $str . 'u' : $str;
    }
}
