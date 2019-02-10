<?php

/**
 * Utilities - Functions and Classes common to the whole phpMyFAQ architecture.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-01
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**#@+
  * HTTP GET Parameters PMF accepted keys definitions
  */
define('HTTP_PARAMS_GET_CATID', 'catid');
define('HTTP_PARAMS_GET_CURRENTDAY', 'today');
define('HTTP_PARAMS_GET_DISPOSITION', 'dispos');
define('HTTP_PARAMS_GET_GIVENDATE', 'givendate');
define('HTTP_PARAMS_GET_LANG', 'lang');
define('HTTP_PARAMS_GET_DOWNWARDS', 'downwards');
define('HTTP_PARAMS_GET_TYPE', 'type');

/**
 * PMF_Utils class.
 *
 * This class has only static methods
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-11-01
 */
class PMF_Utils
{
    /**
     * Get the content at the given URL using an HTTP GET call.
     *
     * @param string $url URL of the content
     *
     * @return string
     */
    public static function getHTTPContent($url)
    {
        // Sanity check
        if (empty($url)) {
            return false;
        }

        // Create the HTTP options for the HTTP stream context, see below
        // Set phpMyFAQ agent related data
        $agent = 'phpMyFAQ/'.PMF_System::getVersion().' on PHP/'.PHP_VERSION;
        $opts = array(
            'header' => 'User-Agent: '.$agent."\r\n",
            'method' => 'GET',
        );
        // HTTP 1.1 Virtual Host
        $urlParts = @parse_url($url);
        if (isset($urlParts['host'])) {
            $opts['header'] = $opts['header'].'Host: '.$urlParts['host']."\r\n";
        }
        // Socket timeout
        $opts['timeout'] = 5;

        // Create the HTTP stream context
        $ctx = stream_context_create(
            array(
                'http' => $opts,
            )
        );

        return file_get_contents($url, null, $ctx);
    }

    /**
     * Returns date from out of time.
     *
     * @return string
     */
    public static function getNeverExpireDate()
    {
        // Unix: 13 Dec 1901 20:45:54 -> 19 Jan 2038 03:14:07, signed 32 bit
        // Windows: 1 Jan 1970 -> 19 Jan 2038.
        // So we will use: 1 Jan 2038 -> 2038-01-01, 00:00:01
        return self::getPMFDate(mktime(0, 0, 1, 1, 1, 2038));
    }

    /**
     * Returns a phpMyFAQ date.
     *
     * @param int $unixTime Unix timestamp
     *
     * @return string
     */
    public static function getPMFDate($unixTime = null)
    {
        if (!isset($unixTime)) {
            // localtime
            $unixTime = $_SERVER['REQUEST_TIME'];
        }

        return date('YmdHis', $unixTime);
    }

    /**
     * Check if a given string could be a language.
     *
     * @param string $lang Language
     *
     * @return bool
     */
    public static function isLanguage($lang)
    {
        return preg_match('/^[a-zA-Z\-]+$/', $lang);
    }

    /**
     * Checks if a date is a phpMyFAQ valid date.
     *
     * @param int $date Date
     *
     * @return int
     */
    public static function isLikeOnPMFDate($date)
    {
        // Test if the passed string is in the format: %YYYYMMDDhhmmss%
        $testdate = $date;
        // Suppress first occurence of '%'
        if (substr($testdate, 0, 1) == '%') {
            $testdate = substr($testdate, 1);
        }
        // Suppress last occurence of '%'
        if (substr($testdate, -1, 1) == '%') {
            $testdate = substr($testdate, 0, strlen($testdate) - 1);
        }
        // PMF date consists of numbers only: YYYYMMDDhhmmss
        return is_int($testdate);
    }

    /**
     * Shortens a string for a given number of words.
     *
     * @param string $str  String
     * @param int    $char Characters
     *
     * @return string
     *
     * @todo This function doesn't work with Chinese, Japanese and Korean
     *       because they don't have spaces as word delimiters
     */
    public static function makeShorterText($str, $char)
    {

        $str = PMF_String::preg_replace('/\s+/u', ' ', $str);
        $arrStr = explode(' ', $str);
        $shortStr = '';
        $num = count($arrStr);

        if ($num > $char) {
            for ($j = 0; $j <= $char; ++$j) {
                $shortStr .= $arrStr[$j].' ';
            }
            $shortStr .= '...';
        } else {
            $shortStr = $str;
        }

        return $shortStr;
    }

    /**
     * Resolves the PMF markers like e.g. %sitename%.
     *
     * @param string            $text   Text contains PMF markers
     * @param PMF_Configuration $config
     *
     * @return string
     */
    public static function resolveMarkers($text, PMF_Configuration $config)
    {
        // Available markers: key and resolving value
        $markers = array(
            '%sitename%' => $config->get('main.titleFAQ'),
        );

        // Resolve any known pattern
        return str_replace(
            array_keys($markers),
            array_values($markers),
            $text
        );
    }

    /**
     * Shuffles an associative array without losing key associations.
     *
     * @param array $data Array of data
     *
     * @return array $shuffled_data Array of shuffled data
     */
    public static function shuffleData($data)
    {
        $shuffled_data = [];

        if (is_array($data)) {
            if (count($data) > 1) {
                $randomized_keys = array_rand($data, count($data));

                foreach ($randomized_keys as $current_key) {
                    $shuffled_data[$current_key] = $data[$current_key];
                }
            } else {
                $shuffled_data = $data;
            }
        }

        return $shuffled_data;
    }

    /**
     * This method chops a string.
     *
     * @param string $string String to chop
     * @param int    $words  Number of words
     *
     * @return string
     */
    public static function chopString($string, $words)
    {
        $str = '';
        $pieces = explode(' ', $string);
        $num = count($pieces);
        if ($words > $num) {
            $words = $num;
        }
        for ($i = 0; $i < $words; ++$i) {
            $str .= $pieces[$i].' ';
        }

        return $str;
    }

    /**
     * Adds a highlighted word to a string.
     *
     * @param string $string    String
     * @param string $highlight Given word for highlighting
     *
     * @return string
     */
    public static function setHighlightedString($string, $highlight)
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

        return PMF_String::preg_replace_callback(
            '/('.$highlight.'="[^"]*")|'.
            '(('.implode('|', $attributes).')="[^"]*'.$highlight.'[^"]*")|'.
            '('.$highlight.')/mis',
            ['PMF_Utils', 'highlightNoLinks'],
            $string
        );
    }

    /**
     * Callback function for filtering HTML from URLs and images.
     *
     * @param array $matches Array of matches from regex pattern
     *
     * @return string
     */
    public static function highlightNoLinks(Array $matches)
    {
        $prefix = isset($matches[3]) ? $matches[3] : '';
        $item = isset($matches[4]) ? $matches[4] : '';
        $postfix = isset($matches[5]) ? $matches[5] : '';

        if (!empty($item) && !self::isForbiddenElement($item)) {
            return sprintf(
                '<mark class="pmf-highlighted-string">%s</mark>',
                $prefix.$item.$postfix
            );
        }

        // Fallback: the original matched string
        return $matches[0];
    }

    /**
     * Tries to detect if a string could be a HTML element
     *
     * @param $string
     *
     * @return bool
     */
    public static function isForbiddenElement($string)
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
     * @param $string
     *
     * @return string
     */
    public static function debug($string)
    {
        // sometimes Zend Optimizer causes segfaults with debug_backtrace()
        if (extension_loaded('Zend Optimizer')) {
            $ret = '<pre>'.$string."</pre><br>\n";
        } else {
            $debug = debug_backtrace();
            $ret = '';
            if (isset($debug[2]['class'])) {
                $ret = $debug[2]['file'].':<br>';
                $ret .= $debug[2]['class'].$debug[1]['type'];
                $ret .= $debug[2]['function'].'() in line '.$debug[2]['line'];
                $ret .= ': <pre>'.$string."</pre><br>\n";
            }
        }

        return $ret;
    }

    /**
     * Parses a given string and convert all the URLs into links.
     *
     * @param string $string
     *
     * @return string
     */
    public static function parseUrl($string)
    {
        $protocols = array('http://', 'https://', 'ftp://');

        $string = str_replace($protocols, '', $string);
        $string = str_replace('www.', 'http://www.', $string);
        $string = preg_replace('|http://([a-zA-Z0-9-\./]+)|', '<a href="http://$1">$1</a>', $string);
        $string = preg_replace(
            '/(([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6})/',
            '<a href="mailto:$1">$1</a>',
            $string
        );

        return $string;
    }

    /**
     * Moves given key of an array to the top
     *
     * @param array  $array
     * @param string $key
     */
    public static function moveToTop(&$array, $key)
    {
        $temp = [$key => $array[$key]];
        unset($array[$key]);
        $array = $temp + $array;
    }

    /**
     * Creates a seed with microseconds.
     * @return float|int
     */
    private static function makeSeed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return $sec + $usec * 1000000;
    }

    /**
     * Returns a random number.
     * @param $min
     * @param $max
     * @return int
     */
    public static function createRandomNumber($min, $max)
    {
        mt_srand(PMF_Utils::makeSeed());
        return mt_rand($min, $max);
    }
}
