<?php
/**
 * $Id: Init.php,v 1.27 2007-02-18 18:01:20 matteo Exp $
 *
 * Some functions
 *
 * @author      Johann-Peter Hartmann <hartmann@mayflower.de>
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Stefan Esser <sesser@php.net>
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since       2005-09-24
 * @copyright   (c) 2005-2007 phpMyFAQ Team
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
define('DEBUG', true);
if (DEBUG) {
    error_reporting(E_ALL);
    if (defined('E_STRICT')) {
       error_reporting(E_ALL & ~E_STRICT);
    }
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

//
// Fix the include path if PMF is running under a "strange" PHP configuration
//
$foundCurrPath = false;
$includePaths = split(PATH_SEPARATOR, ini_get('include_path'));
$i = 0;
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
//
// Since PHP 5.2.0 there are some values for tuning PCRE behaviour
// and avoid high resources consumption
if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
    // Warning: be sure the server has enough memory and stack for PHP
    ini_set('pcre.backtrack_limit', 100000000);
    ini_set('pcre.recursion_limit', 100000000);
}

//
// Read configuration and constants, include main classes and functions
// and create a database connection
//
define('PMF_INCLUDE_DIR', dirname(__FILE__));
require_once(PMF_INCLUDE_DIR.'/data.php');
require_once(PMF_INCLUDE_DIR.'/constants.php');
require_once(PMF_INCLUDE_DIR.'/functions.php');
require_once(PMF_INCLUDE_DIR.'/Configuration.php');
require_once(PMF_INCLUDE_DIR.'/Db.php');
define('SQLPREFIX', $DB['prefix']);
$db = PMF_Db::db_select($DB['type']);
$db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);

//
// Fetch the configuration
//
$faqconfig = new PMF_Configuration($db);
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
if ($faqconfig->get('ldap_support') && file_exists('inc/dataldap.php')) {
    require_once(PMF_INCLUDE_DIR.'dataldap.php');
    require_once(PMF_INCLUDE_DIR.'Ldap.php');
    $ldap = new LDAP($PMF_LDAP['ldap_server'], $PMF_LDAP['ldap_port'], $PMF_LDAP['ldap_base'], $PMF_LDAP['ldap_user'], $PMF_LDAP['ldap_password']);
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
 * @access      public
 * @since       2005-09-24
 * @author      Johann-Peter Hartmann <hartmann@mayflower.de>
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Stefan Esser <sesser@php.net>
 * @author      Christian Stocker <chregu@bitflux.ch>
 */
class PMF_Init
{
    /**
     * The accepted language of the user agend
     *
     * @var  string
     * @see
     */
    var $acceptedLanguage;

    /**
     * The current language
     *
     * @var  string
     * @see
     */
    var $language;

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
    function cleanRequest()
    {
        $_SERVER['PHP_SELF'] = strtr(rawurlencode($_SERVER['PHP_SELF']),array( "%2F"=>"/", "%257E"=>"%7E"));
        $_SERVER['HTTP_USER_AGENT'] = urlencode($_SERVER['HTTP_USER_AGENT']);

        // remove global registered variables to avoid injections
        if (ini_get('register_globals')) {
            PMF_Init::unregisterGlobalVariables();
        }

        // clean external variables
        $externals = array('_REQUEST', '_GET', '_POST', '_COOKIE');
        foreach ($externals as $external) {
            if (isset($GLOBALS[$external]) && is_array($GLOBALS[$external])) {

                // first clean XSS issues
                $newvalues = $GLOBALS[$external];
                $newvalues = PMF_Init::removeXSSGPC($newvalues);

                // then remove magic quotes
                $newvalues = PMF_Init::removeMagicQuotesGPC($newvalues);

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
        PMF_Init::cleanFilenames();
    }

    /**
     * basicFilenameClean()
     *
     * Clean up a filename: if anything goes wrong, an empty string will be returned
     *
     * @param   string  $filename
     * @return  string
     * @access  private
     * @since   2006-12-29
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function basicFilenameClean($filename)
    {
        global $denyUploadExts;

        // Remove the magic quotes if enabled
        $filename = (get_magic_quotes_gpc() ? stripslashes($filename) : $filename);

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
            $filename = str_replace($reservedChars, '_', $filename);
        }

        return $filename;
    }

   /**
    * cleanFilenames()
    *
    * Clean the filename of any uploaded file by the user and force an error
    * when calling is_uploaded_file($_FILES[key]['tmp_name']) if the cleanup goes wrong
    *
    * @access  private
    * @since   2006-12-29
    * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
    */
   function cleanFilenames()
   {
        reset($_FILES);
        while (list($key, $value) = each($_FILES)) {
            if (is_array($_FILES[$key]['name'])) {
                reset($_FILES[$key]['name']);
                // We have a multiple upload with the same name for <input />
                while (list($idx, $value2) = each($_FILES[$key]['name'])) {
                    $_FILES[$key]['name'][$idx] = PMF_Init::basicFilenameClean($_FILES[$key]['name'][$idx]);
                    if ('' == $_FILES[$key]['name'][$idx]) {
                        $_FILES[$key]['type'][$idx] = '';
                        $_FILES[$key]['tmp_name'][$idx] = '';
                        $_FILES[$key]['size'][$idx] = 0;
                        // Since PHP 4.2.0
                        if (isset($_FILES[$key]['error'][$idx])) {
                            // Set an error
                            $_FILES[$key]['error'][$idx] = UPLOAD_ERR_NO_FILE;
                        }
                    }
                }
                reset($_FILES[$key]['name']);
            } else {
                $_FILES[$key]['name'] = PMF_Init::basicFilenameClean($_FILES[$key]['name']);
                if ('' == $_FILES[$key]['name']) {
                    $_FILES[$key]['type'] = '';
                    $_FILES[$key]['tmp_name'] = '';
                    $_FILES[$key]['size'] = 0;
                    // Since PHP 4.2.0
                    if (isset($_FILES[$key]['error'])) {
                        // Set an error
                        $_FILES[$key]['error'] = UPLOAD_ERR_NO_FILE;
                    }
                }
            }
        }
        reset($_FILES);
    }

    /**
     * getUserAgentLanguage()
     *
     * Gets the accepted language from the user agent
     *
     * @return  void
     * @access  private
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getUserAgentLanguage()
    {
        $this->acceptedLanguage = '';
        // $_SERVER['HTTP_ACCEPT_LANGUAGE'] could be like the text below:
        // it,pt-br;q=0.8,en-us;q=0.5,en;q=0.3
        // TODO: (ENH) get an array of accepted languages and cycle through it in PMF_Init::setLanguage
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
     * isASupportedLanguage()
     *
     * True if the language is supported by the current phpMyFAQ installation
     *
     * @param   string  $langcode
     * @return  bool
     * @access  public
     * @author  Matteo scaramuccia <matteo@scaramuccia.com>
     */
    function isASupportedLanguage($langcode)
    {
        global $languageCodes;
        return isset($languageCodes[strtoupper($langcode)]);
    }

    /**
     * setLanguage()
     *
     * Sets the current language for phpMyFAQ user session
     *
     * @param   bool    $config_detection
     * @param   string  $config_language
     * @return  string  $language
     * @access  public
     * @author  Thorsten Rinne <rinne@mayflower.de>
     * @author  Matteo scaramuccia <matteo@scaramuccia.com>
     */
    function setLanguage($config_detection, $config_language)
    {
        $_lang = array();
        PMF_Init::getUserAgentLanguage();

        // Get language from: _POST, _GET, _COOKIE, phpMyFAQ configuration and
        //                    the automatic language detection
        if (isset($_POST['language']) && PMF_Init::isASupportedLanguage($_POST['language']) ) {
            $_lang['post'] = trim($_POST['language']);
        }
        // Get the user language
        if (isset($_GET['lang']) && PMF_Init::isASupportedLanguage($_GET['lang']) ) {
            $_lang['get'] = trim($_GET['lang']);
        }
        // Get the faq record language
        if (isset($_GET['artlang']) && PMF_Init::isASupportedLanguage($_GET['artlang']) ) {
            $_lang['get'] = trim($_GET['artlang']);
        }
        // Get the language from the cookie
        if (isset($_COOKIE['pmf_lang']) && PMF_Init::isASupportedLanguage($_COOKIE['pmf_lang']) ) {
            $_lang['cookie'] = trim($_COOKIE['pmf_lang']);
        }
        // Get the language from the config
        if (isset($config_language)) {
            $confLangCode = str_replace(array("language_", ".php"), "", $config_language);
            if (PMF_Init::isASupportedLanguage($confLangCode) ) {
                $_lang['config'] = $confLangCode;
            }
        }
        // Detect the browser's language
        if ((true === $config_detection) && PMF_Init::isASupportedLanguage($this->acceptedLanguage) ) {
            $_lang['detection'] = $this->acceptedLanguage;
        }
        // Select the language
        if (isset($_lang['post'])) {
            $this->language = $_lang['post'];
            $_lang = null;
            unset($_lang);
            setcookie('pmf_lang', $this->language, time() + 3600);
        } elseif (isset($_lang['get'])) {
            $this->language = $_lang['get'];
        } elseif (isset($_lang['cookie'])) {
            $this->language = $_lang['cookie'];
            $_lang = null;
            unset($_lang);
        } elseif (isset($_lang['config'])) {
            $this->language = $_lang['config'];
            $_lang = null;
            unset($_lang);
            setcookie('pmf_lang', $this->language, time() + 3600);
        } elseif (isset($_lang['detection'])) {
            $this->language = $_lang['detection'];
            $_lang = null;
            unset($_lang);
            setcookie('pmf_lang', $this->language, time() + 3600);
        } else {
            $this->language = 'en'; // just a fallback
        }
        return $this->language;
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
    function unregisterGlobalVariables()
    {
        if (!ini_get('register_globals')) {
            return;
        }

        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            die('GLOBALS overwrite attempt detected' );
        }

        $noUnset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
        $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
        foreach (array_keys($input) as $k) {
            if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
                $GLOBALS[$k] = null;
                unset($GLOBALS[$k]);
            }
        }
    }

    /**
     * This function removes the magic quotes if they enabled
     *
     * @param   array
     * @return  array
     * @access  private
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function removeMagicQuotesGPC($data)
    {
        if (get_magic_quotes_gpc()) {
            $addedData = array();
            foreach ($data as $key => $val) {
                $key = addslashes($key);
                if (is_array($val)) {
                    $addedData[$key] = PMF_Init::removeMagicQuotesGPC($val);
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
     * @copyright   Bitflux (c) 2001-2005 Bitflux GmbH
     */
    function basicXSSClean($string)
    {
        if (strpos($string, '\0') !== false) {
            return null;
        }
        if (get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }

        // Since PHP 5.2.0 PCRE behaviour has been changed and now its behaviour
        // is more correct e.g. according to the modifiers used in the pattern.
        // That said, we are in need to add a fallback if any UTF-8 error is arised
        $canCheckUTF8Error = defined('PREG_BAD_UTF8_ERROR') && function_exists('preg_last_error');

        $string = str_replace(array("&amp;","&lt;","&gt;"),array("&amp;amp;","&amp;lt;","&amp;gt;",),$string);
        // fix &entitiy\n;
        $tmp = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u',"$1;",$string);
        if ($canCheckUTF8Error && (PREG_BAD_UTF8_ERROR == preg_last_error())) {
            $tmp = preg_replace('#(&\#*\w+)[\x00-\x20]+;#',"$1;",$string);
        }
        $string = $tmp;
        $tmp = preg_replace('#(&\#x*)([0-9A-F]+);*#iu',"$1$2;",$string);
        if ($canCheckUTF8Error && (PREG_BAD_UTF8_ERROR == preg_last_error())) {
            $tmp = preg_replace('#(&\#x*)([0-9A-F]+);*#i',"$1$2;",$string);
        }
        $string = $tmp;
        $string = html_entity_decode($string, ENT_COMPAT);

        // remove any attribute starting with "on" or xmlns
        $tmp = preg_replace('#(<[^>]+[\x00-\x20\"\'])(on|xmlns)[^>]*>#iUu',"$1>",$string);
        if ($canCheckUTF8Error && (PREG_BAD_UTF8_ERROR == preg_last_error())) {
            $tmp = preg_replace('#(<[^>]+[\x00-\x20\"\'])(on|xmlns)[^>]*>#iU',"$1>",$string);
        }
        $string = $tmp;

        // remove javascript: and vbscript: protocol
        $tmp = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu','$1=$2nojavascript...',$string);
        if ($canCheckUTF8Error && (PREG_BAD_UTF8_ERROR == preg_last_error())) {
            $tmp = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iU','$1=$2nojavascript...',$string);
        }
        $string = $tmp;
        $tmp = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu','$1=$2novbscript...',$string);
        if ($canCheckUTF8Error && (PREG_BAD_UTF8_ERROR == preg_last_error())) {
            $tmp = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iU','$1=$2novbscript...',$string);
        }
        $string = $tmp;

        // <span style="width: expression(alert('Ping!'));"></span>
        // only works in ie...
        $string = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*expression[\x00-\x20]*\([^>]*>#iU',"$1>",$string);
        $string = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*behaviour[\x00-\x20]*\([^>]*>#iU',"$1>",$string);
        $tmp = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*>#iUu',"$1>",$string);
        if ($canCheckUTF8Error && (PREG_BAD_UTF8_ERROR == preg_last_error())) {
            $tmp = preg_replace('#(<[^>]+)style[\x00-\x20]*=[\x00-\x20]*([\`\'\"]*).*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*>#iU',"$1>",$string);
        }
        $string = $tmp;

        // remove namespaced elements (we do not need them...)
        $string = preg_replace('#</*\w+:\w[^>]*>#i',"",$string);

        // remove really unwanted tags
        do {
            $oldstring = $string;
            $string = preg_replace('#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i',"",$string);
        } while ($oldstring != $string);

        return $string;
    }

    /**
     * removeXSSGPC
     *
     * Removes xss from an array
     *
     * @param   array   $data
     * @return  array
     * @acces   private
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Johann-Peter Hartmann <hartmann@mayflower.de>
     */
    function removeXSSGPC($data) {
        $cleanData = array();
        foreach ($data as $key => $val) {
            $key = PMF_Init::basicXSSClean($key);
            if (is_array($val)) {
                $cleanData[$key] = PMF_Init::removeXSSGPC($val);
            } else {
                $cleanData[$key] = PMF_Init::basicXSSClean($val);
            }
        }
        return $cleanData;
    }
}
