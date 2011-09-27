<?php
/**
 * Utilities - Functions and Classes common to the whole phpMyFAQ architecture.
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Utils
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2005-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
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
 * PMF_Utils class
 *
 * This class has only static methods
 * 
 * @category  phpMyFAQ
 * @package   PMF_Utils
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
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
        $agent = 'phpMyFAQ/' . PMF_Configuration::getInstance()->get('main.currentVersion') . ' on PHP/'.PHP_VERSION;
        $opts  = array(
            'header' => 'User-Agent: '.$agent."\r\n",
            'method' => 'GET');
        // HTTP 1.1 Virtual Host
        $urlParts = @parse_url($url);
        if (isset($urlParts['host'])) {
            $opts['header'] = $opts['header'].'Host: '.$urlParts['host']."\r\n";
        }
        // Socket timeout
        if (version_compare(PHP_VERSION, '5.2.1', '<')) {
            @ini_set('default_socket_timeout', 5);
        } else {
            $opts['timeout'] = 5;
        }

        // Create the HTTP stream context
        $ctx = stream_context_create(
            array(
                'http' => $opts
            )
        );

        return file_get_contents($url, $flags, $ctx);
    }

    /**
     * Returns date from out of time
     *
     * @return string
     */
    public static function getNeverExpireDate()
    {
        // Unix: 13 Dec 1901 20:45:54 -> 19 Jan 2038 03:14:07, signed 32 bit
        // Windows: 1 Jan 1970 -> 19 Jan 2038.
        // So we will use: 1 Jan 2038 -> 2038-01-01, 00:00:01
        return PMF_Utils::getPMFDate(mktime(0, 0 , 1, 1, 1, 2038));
    }

    /**
     * Returns a phpMyFAQ date
     *
     * @param  integer $unixTime Unix timestamp
     * @return string
     */
    public static function getPMFDate($unixTime = NULL)
    {
        if (!isset($unixTime)) {
            // localtime
            $unixTime = $_SERVER['REQUEST_TIME'];
        }
        return date('YmdHis', $unixTime);
    }

    /**
     * Check if a given string could be a language
     *
     * @param  string $lang Language
     * @return boolean
     */
    public static function isLanguage($lang)
    {
        return preg_match('/^[a-zA-Z\-]+$/', $lang);
    }

    /**
     * Checks if a date is a phpMyFAQ valid date
     *
     * @param  integer $date Date
     * @return integer
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
            $testdate = substr($testdate, 0, strlen($testdate)-1);
        }
        // PMF date consists of numbers only: YYYYMMDDhhmmss
        return is_int($testdate);
    }

    /**
     * Shortens a string for a given number of words
     * 
     * @param  string  $str  String  
     * @param  integer $char Characters
     * 
     * @return string
     * 
     * @todo This function doesn't work with Chinese, Japanese and Korean
     *       because they don't have spaces as word delimiters
     */
    public static function makeShorterText($str, $char)
    {
        $str      = PMF_String::preg_replace('/\s+/u', ' ', $str);
        $arrStr   = explode(' ', $str);
        $shortStr = '';
        $num      = count($arrStr);

        if ($num > $char) {
            for ($j = 0; $j <= $char; $j++) {
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
     * @public
     * @static
     * @param   string $text Text contains PMF markers
     * @return  string
     */
    public static function resolveMarkers($text)
    {
        // Available markers: key and resolving value
        $markers = array('%sitename%' => PMF_Configuration::getInstance()->get('main.titleFAQ'));

        // Resolve any known pattern
        return str_replace(
            array_keys($markers),
            array_values($markers),
            $text
        );
    }

    /**
     * Shuffles an associative array without losing key associations
     *
     * @param  array $data          Array of data
     * @return array $shuffled_data Array of shuffled data
     */
    public static function shuffleData($data)
    {
        $shuffled_data = array();

        if (is_array($data)) {
            if (count($data) > 1) {
                $randomized_keys = array_rand($data, count($data));

                foreach($randomized_keys as $current_key) {
                    $shuffled_data[$current_key] = $data[$current_key];
                }
            } else {
                $shuffled_data = $data;
            }
        }

        return $shuffled_data;
    }
    
    /**
     * Returns an array of country codes for a specific FAQ record ID, 
     * specific category ID or all languages used by FAQ records , categories
     *
     * @param  integer $id    ID
     * @param  string  $table Specifies table
     * @return array
     */
    public static function languageAvailable($id, $table = 'faqdata')
    {
    	$db     = PMF_Db::getInstance();
        $output = array();

        if (isset($id)) {
            if ($id == 0) {
                // get languages for all ids
                $distinct = ' DISTINCT ';
                $where = '';
            } else {
                // get languages for specified id
                $distinct = '';
                $where = " WHERE id = ".$id;
            }

            $query = sprintf("
                SELECT %s
                    lang
                FROM
                    %s%s
                %s",
                $distinct,
                SQLPREFIX,
                $table,
                $where);
        
            $result = $db->query($query);

            if ($db->numRows($result) > 0) {
                while ($row = $db->fetchObject($result)) {
                    $output[] = $row->lang;
                }
            }
        }

        return $output;
    }
    
    /**
     * This method chops a string
     * 
     * @param string  $string String to chop
     * @param integer $words  Number of words
     *
     * @return string
     */
    public static function chopString ($string, $words)
    {
        $str    = '';
        $pieces = explode(' ', $string);
        $num    = count($pieces);
        if ($words > $num) {
            $words = $num;
        }
        for ($i = 0; $i < $words; $i ++) {
            $str .= $pieces[$i] . ' ';
        }
        return $str;
    }
    
    /**
     * Adds a highlighted word to a string
     * 
     * @param string $string    String
     * @param string $highlight Given word for highlighting
     * 
     * @return string
     */
    public static function setHighlightedString($string, $highlight)
    {
        $attributes  = array(
            'href', 'src', 'title', 'alt', 'class', 'style', 'id', 'name', 'face',
            'size', 'dir', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup',
            'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown',
            'onkeyup');
        
        return PMF_String::preg_replace_callback(
            '/(' . $highlight . '="[^"]*")|' .
            '((' . implode('|', $attributes) . ')="[^"]*' . $highlight . '[^"]*")|' .
            '(' . $highlight . ')/mis',
            array('PMF_Utils', 'highlightNoLinks'),
            $string);
    }
    
    /**
     * Callback function for filtering HTML from URLs and images
     *
     * @param array $matches Array of matches from regex pattern
     * 
     * @return string
     */
    public static function highlightNoLinks(Array $matches)
    {
        $itemAsAttrName  = $matches[1];
        $itemInAttrValue = isset($matches[2]) ? $matches[2] : ''; // $matches[3] is the attribute name
        $prefix          = isset($matches[3]) ? $matches[3] : '';
        $item            = isset($matches[4]) ? $matches[4] : '';
        $postfix         = isset($matches[5]) ? $matches[5] : '';
        
        if (!empty($item)) {
            return '<span class="highlight">'.$prefix.$item.$postfix.'</span>';
        }
        
        // Fallback: the original matched string
        return $matches[0];
    }

    /**
     * Returns the MIME type of a file according to the used PHP version
     *
     * @param string $filename Filename 
     *
     * @return string
     */
    public static function getMimeType($filename)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            return mime_content_type($filename);
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            return $finfo->file($filename);
        }
    }
}
