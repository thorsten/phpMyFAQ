<?php
/**
 * Manages all language stuff
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Language
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo scaramuccia <matteo@phpmyfaq.de>
 * @since      2009-05-14
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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

/**
 * PMF_Language
 * 
 * @package    phpMyFAQ
 * @subpackage PMF_Language
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo scaramuccia <matteo@phpmyfaq.de>
 * @since      2009-05-14
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Language
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
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        
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
     * True if the language is supported by the current phpMyFAQ installation
     *
     * @param  string $langcode Language code
     * @return bool
     */
    public static function isASupportedLanguage($langcode)
    {
        global $languageCodes;
        return isset($languageCodes[strtoupper($langcode)]);
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
}