<?php
// phpcs:ignoreFile
/**
 * The string wrapper class using mbstring extension.
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
     *
     * @var Mbstring
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
     * @param  string $language
     * @return Mbstring
     */
    public static function getInstance($language = 'en'): Mbstring
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
     * @return int
     */
    public function strlen(string $str): int
    {
        return mb_strlen($str, $this->encoding);
    }

    /**
     * Get a part of string.
     *
     * @param string $str String
     * @param int $start Start
     * @param null $length Length
     *
     * @return string
     */
    public function substr(string $str, int $start, $length = null): string
    {
        $length = null == $length ? mb_strlen($str) : $length;

        return mb_substr($str, $start, $length, $this->encoding);
    }

    /**
     * Get position of the first occurrence of a string.
     *
     * @param string $haystack Haystack
     * @param string $needle Needle
     * @param int $offset Offset
     *
     * @return int
     */
    public function strpos(string $haystack, string $needle, $offset = 0): int
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
    public function strtolower(string $str): string
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
    public function strtoupper(string $str): string
    {
        return mb_strtoupper($str, $this->encoding);
    }

    /**
     * Get first occurrence of a string within another.
     *
     * @param string $haystack Haystack
     * @param string $needle Needle
     * @param bool $part Part
     *
     * @return string|false
     */
    public function strstr(string $haystack, string $needle, $part = false)
    {
        return mb_strstr($haystack, $needle, $part, $this->encoding);
    }

    /**
     * Count substring occurrences.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return int
     */
    public function substr_count(string $haystack, string $needle): int
    {
        return mb_substr_count($haystack, $needle, $this->encoding);
    }

    /**
     * Match a regexp.
     *
     * @param string $pattern
     * @param string $subject
     * @param null $matches
     * @param int $flags
     * @param int $offset
     *
     * @return int
     */
    public function preg_match(string $pattern, string $subject, &$matches = null, $flags = 0, $offset = 0): int
    {
        return preg_match(self::appendU($pattern), $subject, $matches, $flags, $offset);
    }

    /**
     * Match a regexp globally.
     *
     * @param string $pattern
     * @param string $subject
     * @param string[][] $matches
     * @param int $flags
     * @param int $offset
     *
     * @return int
     */
    public function preg_match_all(string $pattern, string $subject, array &$matches, $flags = 0, $offset = 0): int
    {
        return preg_match_all(self::appendU($pattern), $subject, $matches, $flags, $offset);
    }

    /**
     * Split string by a regexp.
     *
     * @param string $pattern
     * @param string $subject
     * @param int $limit
     * @param int $flags
     *
     * @return string[]|array|false
     */
    public function preg_split(string $pattern, string $subject, $limit = -1, $flags = 0)
    {
        return preg_split(self::appendU($pattern), $subject, $limit, $flags);
    }

    /**
     * Search and replace by a regexp using a callback.
     *
     * @param string|string[] $pattern
     * @param callable $callback
     * @param string|string[] $subject
     * @param int $limit
     * @param int $count
     *
     * @return string|string[]
     */
    public function preg_replace_callback($pattern, callable $callback, $subject, int $limit = -1, int &$count = 0)
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
     * @param int $limit
     * @param int $count
     *
     * @return string|string[]|null
     */
    public function preg_replace($pattern, $replacement, $subject, int $limit = -1, int &$count = 0)
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
    private static function appendU(string $str): string
    {
        $str = (string)$str;

        return parent::isUTF8($str) ? $str . 'u' : $str;
    }
}
