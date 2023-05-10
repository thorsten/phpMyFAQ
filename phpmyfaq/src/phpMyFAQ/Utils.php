<?php

/**
 * Utilities - Functions and Classes common to the whole phpMyFAQ architecture.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-01
 */

namespace phpMyFAQ;

/**
 * Class Utils
 *
 * @package phpMyFAQ
 */
class Utils
{
    /**
     * Check if a given string could be a language.
     *
     * @param string $lang Language
     */
    public static function isLanguage(string $lang): bool
    {
        return preg_match('/^[a-zA-Z\-]+$/', $lang);
    }

    /**
     * Checks if a date is a phpMyFAQ valid date.
     *
     * @param string $date Date
     */
    public static function isLikeOnPMFDate(string $date): bool
    {
        // Test if the passed string is in the format: %YYYYMMDDhhmmss%
        $dateToTest = $date;
        // Suppress first occurrences of '%'
        if (str_starts_with($dateToTest, '%')) {
            $dateToTest = substr($dateToTest, 1);
        }
        // Suppress last occurrences of '%'
        if (str_ends_with($dateToTest, '%')) {
            $dateToTest = substr($dateToTest, 0, strlen($dateToTest) - 1);
        }
        // PMF date consists of numbers only: YYYYMMDDhhmmss
        return is_numeric($dateToTest);
    }

    /**
     * Shortens a string for a given number of words.
     *
     * @param string $string String
     * @param int    $characters Characters
     * @todo This function doesn't work with Chinese, Japanese, Korean and Thai
     *       because they don't have spaces as word delimiters
     */
    public static function makeShorterText(string $string, int $characters): string
    {
        $string = Strings::preg_replace('/\s+/u', ' ', $string);
        $arrStr = explode(' ', $string);
        $shortStr = '';
        $num = count($arrStr);

        if ($num > $characters) {
            for ($j = 0; $j < $characters; ++$j) {
                $shortStr .= $arrStr[$j] . ' ';
            }
            $shortStr .= '...';
        } else {
            $shortStr = $string;
        }

        return $shortStr;
    }

    /**
     * Resolves the PMF markers like e.g. %sitename%.
     *
     * @param string $text Text contains PMF markers
     */
    public static function resolveMarkers(string $text, Configuration $config): string
    {
        // Available markers: key and resolving value
        $markers = [
            '%sitename%' => $config->getTitle(),
        ];

        // Resolve any known pattern
        return str_replace(
            array_keys($markers),
            array_values($markers),
            $text
        );
    }

    /**
     * This method chops a string.
     *
     * @param string $string String to chop
     * @param int    $words Number of words
     */
    public static function chopString(string $string, int $words): string
    {
        $str = '';
        $pieces = explode(' ', $string);
        $num = count($pieces);
        if ($words > $num) {
            $words = $num;
        }
        for ($i = 0; $i < $words; ++$i) {
            $str .= $pieces[$i] . ' ';
        }

        return $str;
    }

    /**
     * Adds a highlighted word to a string.
     *
     * @param string $string String
     * @param string $highlight Given word for highlighting
     */
    public static function setHighlightedString(string $string, string $highlight): string
    {
        $attributes = [
            'href', 'src', 'title', 'alt', 'class', 'style', 'id', 'name',
            'face', 'size', 'dir', 'rel', 'rev', 'role',
            'onmouseenter', 'onmouseleave', 'onafterprint', 'onbeforeprint',
            'onbeforeunload', 'onhashchange', 'onmessage', 'onoffline', 'ononline',
            'onpopstate', 'onpagehide', 'onpageshow', 'onresize', 'onunload',
            'ondevicemotion', 'ondeviceorientation', 'onabort', 'onblur',
            'oncanplay', 'oncanplaythrough', 'onchange', 'onclick', 'oncontextmenu',
            'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave',
            'ondragover', 'ondragstart', 'ondrop', 'ondurationchange', 'onemptied',
            'onended', 'onerror', 'onfocus', 'oninput', 'oninvalid', 'onkeydown',
            'onkeypress', 'onkeyup', 'onload', 'onloadeddata', 'onloadedmetadata',
            'onloadstart', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover',
            'onmouseup', 'onmozfullscreenchange', 'onmozfullscreenerror', 'onpause',
            'onplay', 'onplaying', 'onprogress', 'onratechange', 'onreset',
            'onscroll', 'onseeked', 'onseeking', 'onselect', 'onshow', 'onstalled',
            'onsubmit', 'onsuspend', 'ontimeupdate', 'onvolumechange', 'onwaiting',
            'oncopy', 'oncut', 'onpaste', 'onbeforescriptexecute', 'onafterscriptexecute'
        ];

        return Strings::preg_replace_callback(
            '/(' . $highlight . '="[^"]*")|' .
            '((' . implode('|', $attributes) . ')="[^"]*' . $highlight . '[^"]*")|' .
            '(' . $highlight . ')/mis',
            ['phpMyFAQ\Utils', 'highlightNoLinks'],
            $string
        );
    }

    /**
     * Callback function for filtering HTML from URLs and images.
     *
     * @param array<int, string> $matches Array of matches from regex pattern
     */
    public static function highlightNoLinks(array $matches): string
    {
        $prefix = $matches[3] ?? '';
        $item = $matches[4] ?? '';
        $postfix = $matches[5] ?? '';

        if (!empty($item) && !self::isForbiddenElement($item)) {
            return sprintf(
                '<mark class="pmf-highlighted-string">%s</mark>',
                $prefix . $item . $postfix
            );
        }

        // Fallback: the original matched string
        return $matches[0];
    }

    /**
     * Tries to detect if a string could be an HTML element
     */
    public static function isForbiddenElement(string $string): bool
    {
        $forbiddenElements = [
            'img', 'picture', 'mark'
        ];

        foreach ($forbiddenElements as $element) {
            if (str_starts_with($element, $string)) {
                return true;
            }
        }

        return false;
    }

    /**
     * debug_backtrace() wrapper function.
     */
    public static function debug(string $string): string
    {
        // sometimes Zend Optimizer causes segfaults with debug_backtrace()
        if (extension_loaded('Zend Optimizer')) {
            $ret = '<code>' . Strings::htmlentities($string) . "</code><br>\n";
        } else {
            $debug = debug_backtrace();
            $ret = '';
            if (isset($debug[2]['class'])) {
                $ret = $debug[2]['file'] . ': ';
                $ret .= $debug[2]['class'] . $debug[1]['type'];
                $ret .= $debug[2]['function'] . '() in line ' . $debug[2]['line'];
                $ret .= ':<br><code>' . Strings::htmlentities($string) . "</code><br>\n";
            }
        }

        return $ret;
    }

    /**
     * Parses a given string and convert all the URLs into links.
     */
    public static function parseUrl(string $string): string
    {
        $protocols = ['http://', 'https://'];

        $string = str_replace($protocols, '', $string);
        $string = str_replace('www.', 'https://www.', $string);

        $pattern = '/(https?:\/\/[^\s]+)/i';
        $replacement = '<a href="$1">$1</a>';

        return preg_replace($pattern, $replacement, $string);
    }

    /**
     * Moves given key of an array to the top
     *
     * @param array<int> $array
     */
    public static function moveToTop(array &$array, string $key): void
    {
        $temp = [$key => $array[$key]];
        unset($array[$key]);
        $array = $temp + $array;
    }

    /**
     * Formats a given number of Bytes to kB, MB, GB, and so on.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
