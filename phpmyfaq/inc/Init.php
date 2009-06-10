<?php
/**
 * Some basic functions and PMF_Init class.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Init
 * @author     Johann-Peter Hartmann <hartmann@mayflower.de>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Stefan Esser <sesser@php.net>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author     Christian Stocker <chregu@bitflux.ch>
 * @since      2005-09-24
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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
 */

//
// Debug mode:
// - false      debug mode disabled
// - true       debug mode enabled
//
define('DEBUG', false);
if (DEBUG) {
    error_reporting(E_ALL & E_STRICT);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

//
// Fix the PHP include path if PMF is running under a "strange" PHP configuration
//
$foundCurrPath = false;
$includePaths  = explode(PATH_SEPARATOR, ini_get('include_path'));
$i             = 0;
while((!$foundCurrPath) && ($i < count($includePaths))) {
    if ('.' == $includePaths[$i]) {
        $foundCurrPath = true;
    }
    $i++;
}
if (!$foundCurrPath) {
    ini_set('include_path', '.'.PATH_SEPARATOR.ini_get('include_path'));
}

//
// Tweak some PHP configuration values
// Warning: be sure the server has enough memory and stack for PHP
//
ini_set('pcre.backtrack_limit', 100000000);
ini_set('pcre.recursion_limit', 100000000);

//
// Include our class/interface autoloader
//
require_once 'autoLoader.php';

//
// Read configuration and constants, included main functions
//
define('PMF_INCLUDE_DIR', dirname(__FILE__));
require_once PMF_INCLUDE_DIR.'/data.php';
require_once PMF_INCLUDE_DIR.'/constants.php';
require_once PMF_INCLUDE_DIR.'/functions.php';
// TODO: Linkverifier.php contains both PMF_Linkverifier class and
//       helper functions => move the fns into the class.
require_once PMF_INCLUDE_DIR.'/Linkverifier.php';

//
// Set the error handler to our pmf_error_handler() function
//
set_error_handler('pmf_error_handler');

//
// Create a database connection
//
define('SQLPREFIX', $DB['prefix']);
$db = PMF_Db::dbSelect($DB['type']);
$db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);

//
// Fetch the configuration
//
$faqconfig = PMF_Configuration::getInstance();
$faqconfig->getAll();
$PMF_CONF = $faqconfig->config;

//
// We always need a valid session!
//
// Avoid any PHP version to move sessions on URLs
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('url_rewriter.tags', '');

//
// Connect to LDAP server, when LDAP support is enabled
//
if ($faqconfig->get('main.ldapSupport') && file_exists(PMF_INCLUDE_DIR . '/dataldap.php')) {
    require_once PMF_INCLUDE_DIR . '/dataldap.php';
} else {
    $ldap = null;
}

/**
 * PMF_Init
 *
 * This class provides methods to clean the request environment from global
 * variables, unescaped slashes and XSS in the request string. It also detects
 * and sets the current language.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Init
 * @author     Johann-Peter Hartmann <hartmann@mayflower.de>
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Stefan Esser <sesser@php.net>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author     Christian Stocker <chregu@bitflux.ch>
 * @since      2005-09-24
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$
 */
class PMF_Init
{
    /**
     * The accepted language of the user agend
     *
     * @var string
     */
    public $acceptedLanguage = '';

    /**
     * The current language
     *
     * @var string
     */
    public static $language = '';

    /**
     * cleanRequest
     *
     * Cleans the request environment from:
     * - global variables,
     * - unescaped slashes,
     * - xss in the request string,
     * - uncorrect filenames when file are uploaded.
     *
     * @return  void
     * @access  public
     * @author  Johann-Peter Hartmann <hartmann@mayflower.de>
     */
    public static function cleanRequest()
    {
        if (version_compare(PHP_VERSION, '6.0.0-dev', '<')) {
            $_SERVER['PHP_SELF'] = strtr(rawurlencode($_SERVER['PHP_SELF']),array( "%2F"=>"/", "%257E"=>"%7E"));
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = urlencode($_SERVER['HTTP_USER_AGENT']);
        }

        // remove global registered variables to avoid injections
        if (ini_get('register_globals')) {
            self::_unregisterGlobalVariables();
        }

        // clean external variables
        $externals = array('_REQUEST', '_GET', '_POST', '_COOKIE');
        foreach ($externals as $external) {
            if (isset($GLOBALS[$external]) && is_array($GLOBALS[$external])) {

                // first clean XSS issues
                $newvalues = $GLOBALS[$external];
                $newvalues = self::_removeXSSGPC($newvalues);

                // then remove magic quotes
                $newvalues = self::_removeMagicQuotesGPC($newvalues);

                // clean old array and insert cleaned data
                foreach (array_keys($GLOBALS[$external]) as $key) {
                    $GLOBALS[$external][$key] = null;
                    unset($GLOBALS[$external][$key]);
                }
                foreach (array_keys($newvalues) as $key) {
                    $GLOBALS[$external][$key] = $newvalues[$key];
                }
            }
        }

        // clean external filenames (uploaded files)
        self::_cleanFilenames();
    }

    /**
     * Clean up a filename: if anything goes wrong, an empty string will be returned
     *
     * @param   string  $filename
     * @return  string
     * @access  private
     * @since   2006-12-29
     * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
     */
    private static function _basicFilenameClean($filename)
    {
        global $denyUploadExts;

        // Remove the magic quotes if enabled
        $filename = (ini_get('magic_quotes_gpc') ? stripslashes($filename) : $filename);

        $path_parts = pathinfo($filename);
        // We need a filename without any path info
        if ($path_parts['basename'] !== $filename) {
            return '';
        }
        //  We need a filename with at least 1 chars plus the optional extension
        if (isset($path_parts['extension']) && ($path_parts['basename'] == '.'.$path_parts['extension'])) {
            return '';
        }
        if (!isset($path_parts['extension']) && (strlen($path_parts['basename']) == 0)) {
            return '';
        }

        // Deny some extensions (see inc/constants.php), if any
        if (!isset($path_parts['extension'])) {
            $path_parts['extension'] = '';
        }
        if (count($denyUploadExts) > 0) {
            if (in_array(strtolower($path_parts['extension']), $denyUploadExts)) {
                return '';
            }
        }

        // Clean the file to remove some chars depending on the server OS
        // 0. main/rfc1867.c: rfc1867_post_handler removes any char before the last occurence of \/
        // 1. Besides \/ on Windows: :*?"<>|
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $reservedChars = array(':', '*', '?', '"', '<', '>', "'", '|');
            $filename      = str_replace($reservedChars, '_', $filename);
        }

        return $filename;
    }

   /**
    * Clean the filename of any uploaded file by the user and force an error
    * when calling is_uploaded_file($_FILES[key]['tmp_name']) if the cleanup goes wrong
    *
    * @access  private
    * @since   2006-12-29
    * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
    */
   private static function _cleanFilenames()
   {
        reset($_FILES);
        while (list($key, $value) = each($_FILES)) {
            if (is_array($_FILES[$key]['name'])) {
                reset($_FILES[$key]['name']);
                // We have a multiple upload with the same name for <input />
                while (list($idx, $value2) = each($_FILES[$key]['name'])) {
                    $_FILES[$key]['name'][$idx] = self::_basicFilenameClean($_FILES[$key]['name'][$idx]);
                    if ('' == $_FILES[$key]['name'][$idx]) {
                        $_FILES[$key]['type'][$idx]     = '';
                        $_FILES[$key]['tmp_name'][$idx] = '';
                        $_FILES[$key]['size'][$idx]     = 0;
                        $_FILES[$key]['error'][$idx]    = UPLOAD_ERR_NO_FILE;
                    }
                }
                reset($_FILES[$key]['name']);
            } else {
                $_FILES[$key]['name'] = self::_basicFilenameClean($_FILES[$key]['name']);
                if ('' == $_FILES[$key]['name']) {
                    $_FILES[$key]['type']     = '';
                    $_FILES[$key]['tmp_name'] = '';
                    $_FILES[$key]['size']     = 0;
                    $_FILES[$key]['error']   = UPLOAD_ERR_NO_FILE;
                }
            }
        }
        reset($_FILES);
    }

    /**
     * Gets the accepted language from the user agent
     *
     * @return  void
     * @access  private
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
     */
    private function _getUserAgentLanguage()
    {
        $matches = array();
        // $_SERVER['HTTP_ACCEPT_LANGUAGE'] could be like the text below:
        // it,pt-br;q=0.8,en-us;q=0.5,en;q=0.3
        // TODO: (ENH) get an array of accepted languages and cycle through it in self::setLanguage
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // ISO Language Codes, 2-letters: ISO 639-1, <Country tag>[-<Country subtag>]
            // Simplified language syntax detection: xx[-yy]
            preg_match("/([a-z\-]+)/i", trim($_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches);
            if (isset($matches[1])) {
                $this->acceptedLanguage = $matches[1];
            }
        }
    }

    /**
     * True if the language is supported by the current phpMyFAQ installation
     *
     * @param   string  $langcode
     * @return  bool
     * @access  public
     * @author  Matteo scaramuccia <matteo@phpmyfaq.de>
     */
    public static function isASupportedLanguage($langcode)
    {
        global $languageCodes;
        return isset($languageCodes[strtoupper($langcode)]);
    }

    /**
     * Sets the current language for phpMyFAQ user session
     *
     * @param   bool    $config_detection Configuration detection
     * @param   string  $config_language  Language from configuration
     * @return  string
     */
    public function setLanguage($config_detection, $config_language)
    {
        global $sid;

        $_lang = array();
        self::_getUserAgentLanguage();

        // Get language from: _POST, _GET, _COOKIE, phpMyFAQ configuration and the automatic language detection
        $_lang['post'] = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING);
        if (!is_null($_lang['post']) && !self::isASupportedLanguage($_lang['post']) ) {
            $_lang['post'] = null;
        }
        // Get the user language
        $_lang['get'] = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
        if (!is_null($_lang['get']) && !self::isASupportedLanguage($_lang['get']) ) {
            $_lang['get'] = null;
        }
        // Get the faq record language
        $_lang['artget'] = PMF_Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
        if (!is_null($_lang['artget']) && !self::isASupportedLanguage($_lang['artget']) ) {
            $_lang['get'] = null;
        }
        // Get the language from the session
        if (isset($_SESSION['pmf_lang']) && self::isASupportedLanguage($_SESSION['pmf_lang']) ) {
            $_lang['session'] = trim($_SESSION['pmf_lang']);
        }
        
        // Get the language from the config
        if (isset($config_language)) {
            $confLangCode = str_replace(array("language_", ".php"), "", $config_language);
            if (self::isASupportedLanguage($confLangCode) ) {
                $_lang['config'] = $confLangCode;
            }
        }
        // Detect the browser's language
        if ((true === $config_detection) && self::isASupportedLanguage($this->acceptedLanguage) ) {
            $_lang['detection'] = $this->acceptedLanguage;
        }
        // Select the language
        if (isset($_lang['post'])) {
            self::$language = $_lang['post'];
            $_lang = null;
            unset($_lang);
        } elseif (isset($_lang['get'])) {
            self::$language = $_lang['get'];
        } elseif (isset($_lang['session'])) {
            self::$language = $_lang['session'];
            $_lang = null;
            unset($_lang);
        } elseif (isset($_lang['detection'])) {
            self::$language = $_lang['detection'];
            $_lang = null;
            unset($_lang);
        } elseif (isset($_lang['config'])) {
            self::$language = $_lang['config'];
            $_lang = null;
            unset($_lang);
        } else {
            self::$language = 'en'; // just a fallback
        }
        
        return $_SESSION['pmf_lang'] = self::$language;
    }

    /**
     * This function deregisters the global variables only when 'register_globals = On'.
     * Note: you must assure that 'session_start()' is called AFTER this function and not BEFORE,
     *       otherwise each $_SESSION key will be set to NULL because $GLOBALS
     *       has an entry, as copy-by-ref, for each $_SESSION key when 'register_globals = On'.
     *
     * @return  void
     * @access  private
     * @author  Stefan Esser <sesser@php.net>
     */
    private static function _unregisterGlobalVariables()
    {
        if (!ini_get('register_globals')) {
            return;
        }

        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            die('GLOBALS overwrite attempt detected.');
        }

        $noUnset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
        $input   = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
        foreach (array_keys($input) as $k) {
            if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
                $GLOBALS[$k] = null;
                unset($GLOBALS[$k]);
            }
        }
    }

    /**
     * This function removes the magic quotes if they are enabled.
     *
     * @param   array
     * @return  array
     * @access  private
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    private static function _removeMagicQuotesGPC($data)
    {
        static $recursionCounter = 0;
        // Avoid webserver crashes. For any detail, see: http://www.php-security.org/MOPB/MOPB-02-2007.html
        // Note: 1000 is an heuristic value, large enough to be "transparent" to PMF.
        if ($recursionCounter > 1000) {
            die('Deep recursion attack detected.');
        }

        if (ini_get('magic_quotes_gpc')) {
            $addedData = array();
            foreach ($data as $key => $val) {
                $key = addslashes($key);
                if (is_array($val)) {
                    $recursionCounter++;
                    $addedData[$key] = self::_removeMagicQuotesGPC($val);
                } else {
                    $addedData[$key] = $val;
                }
            }
            return $addedData;
        }
        return $data;
    }

    /**
     * Cleans a html string from some xss issues
     *
     * @param       string  $string
     * @return      string
     * @access      private
     * @author      Christian Stocker <chregu@bitflux.ch>
     * @copyright   Copyright (c) 2001-2008 Liip AG
     */
    private static function _basicXSSClean($string)
    {
        global  $PMF_LANG;

        if (strpos($string, '\0') !== false) {
            return null;
        }

        if (ini_get('magic_quotes_gpc')) {
            $string = stripslashes($string);
        }

        $string = str_replace(array("&amp;", "&lt;", "&gt;"), array("&amp;amp;", "&amp;lt;", "&amp;gt;"), $string);
        
        // fix &entitiy\n;
        $string = preg_replace('#(&\#*\w+)[\x00-\x20]+;#', "$1;", $string);
        $string = preg_replace('#(&\#x*)([0-9A-F]+);*#i', "$1$2;", $string);
        $string = html_entity_decode($string, ENT_COMPAT, $PMF_LANG['metaCharset']);
        
        // remove any attribute starting with "on" or xmlns
        $string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iU', "$1>", $string);
        
        // remove javascript: and vbscript: protocol
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*)[\\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iU', '$1=$2nojavascript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iU', '$1=$2novbscript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#U', '$1=$2nomozbinding...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'\"]*)[\x00-\x20]*data[\x00-\x20]*:#U', '$1=$2nodata...', $string);
        
        //<span style="width: expression(alert('Ping!'));"></span> 
        // only works in ie...
        $string = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*expression[\x00-\x20]*\([^>]*>#iU', "$1>", $string);
        $string = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*behaviour[\x00-\x20]*\([^>]*>#iU', "$1>", $string);
        $string = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*>#iU', "$1>", $string);
        
        //remove namespaced elements (we do not need them...)
        $string = preg_replace('#</*\w+:\w[^>]*>#i', "", $string);
        
        //remove really unwanted tags
        do {
            $oldstring = $string;
            $string = preg_replace('#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i', "", $string);
        } while ($oldstring != $string);
        
        return $string;
    }

    /**
     * Removes xss from an array
     *
     * @param   array   $data
     * @return  array
     * @acces   private
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Johann-Peter Hartmann <hartmann@mayflower.de>
     */
    private static function _removeXSSGPC($data)
    {
        static $recursionCounter = 0;
        // Avoid webserver crashes. For any detail, see: http://www.php-security.org/MOPB/MOPB-02-2007.html
        // Note: 1000 is an heuristic value, large enough to be "transparent" to PMF.
        if ($recursionCounter > 1000) {
            die('Deep recursion attack detected.');
        }

        $cleanData = array();
        foreach ($data as $key => $val) {
            $key = self::_basicXSSClean($key);
            if (is_array($val)) {
                $recursionCounter++;
                $cleanData[$key] = self::_removeXSSGPC($val);
            } else {
                $cleanData[$key] = self::_basicXSSClean($val);
            }
        }
        return $cleanData;
    }
}
