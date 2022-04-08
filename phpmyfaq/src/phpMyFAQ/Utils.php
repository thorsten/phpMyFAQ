<?php

/**
 * Utilities - Functions and Classes common to the whole phpMyFAQ architecture.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2005-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-01
 */

namespace phpMyFAQ;

define('HTTP_PARAMS_GET_CATID', 'catid');
define('HTTP_PARAMS_GET_CURRENTDAY', 'today');
define('HTTP_PARAMS_GET_DISPOSITION', 'dispos');
define('HTTP_PARAMS_GET_GIVENDATE', 'givendate');
define('HTTP_PARAMS_GET_LANG', 'lang');
define('HTTP_PARAMS_GET_DOWNWARDS', 'downwards');
define('HTTP_PARAMS_GET_TYPE', 'type');

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
     * @return bool
     */
    public static function isLanguage(string $lang): bool
    {
        return preg_match('/^[a-zA-Z\-]+$/', $lang);
    }

    /**
     * Checks if a date is a phpMyFAQ valid date.
     *
     * @param string $date Date
     * @return bool
     */
    public static function isLikeOnPMFDate(string $date): bool
    {
        // Test if the passed string is in the format: %YYYYMMDDhhmmss%
        $dateToTest = $date;
        // Suppress first occurrences of '%'
        if (substr($dateToTest, 0, 1) == '%') {
            $dateToTest = substr($dateToTest, 1);
        }
        // Suppress last occurrences of '%'
        if (substr($dateToTest, -1, 1) == '%') {
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
     * @return string
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
     * @param string        $text Text contains PMF markers
     * @param Configuration $config
     * @return string
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
     * @return string
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
     * @return string
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
     *
     * @return string
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
     * Tries to detect if a string could be a HTML element
     *
     * @param string $string
     *
     * @return bool
     */
    public static function isForbiddenElement(string $string): bool
    {
        $forbiddenElements = [
            'img', 'picture', 'mark'
        ];

        foreach ($forbiddenElements as $element) {
            if (strpos($element, $string)) {
                return true;
            }
        }

        return false;
    }

    /**
     * debug_backtrace() wrapper function.
     *
     * @param string $string
     * @return string
     */
    public static function debug(string $string): string
    {
        // sometimes Zend Optimizer causes segfaults with debug_backtrace()
        if (extension_loaded('Zend Optimizer')) {
            $ret = '<code>' . $string . "</code><br>\n";
        } else {
            $debug = debug_backtrace();
            $ret = '';
            if (isset($debug[2]['class'])) {
                $ret = $debug[2]['file'] . ':<br>';
                $ret .= $debug[2]['class'] . $debug[1]['type'];
                $ret .= $debug[2]['function'] . '() in line ' . $debug[2]['line'];
                $ret .= ': <code>' . $string . "</code><br>\n";
            }
        }

        return $ret;
    }

    /**
     * Parses a given string and convert all the URLs into links.
     *
     * @param string $string
     * @return string
     */
    public static function parseUrl(string $string): string
    {
        $protocols = array('http://', 'https://', 'ftp://');

        $string = str_replace($protocols, '', $string);
        $string = str_replace('www.', 'http://www.', $string);
        $string = preg_replace('|http://([a-zA-Z0-9-\./]+)|', '<a href="http://$1">$1</a>', $string);
        return preg_replace(
            '/(([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6})/',
            '<a href="mailto:$1">$1</a>',
            $string
        );
    }

    /**
     * Moves given key of an array to the top
     *
     * @param array<int> $array
     * @param string $key
     */
    public static function moveToTop(array &$array, string $key): void
    {
        $temp = [$key => $array[$key]];
        unset($array[$key]);
        $array = $temp + $array;
    }
}
