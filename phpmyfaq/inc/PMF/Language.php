<?php

/**
 * Manages all language stuff.
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
 * @author    Matteo scaramuccia <matteo@phpmyfaq.de>
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-05-14
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Language.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo scaramuccia <matteo@phpmyfaq.de>
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-05-14
 */
class PMF_Language
{
    /**
     * The accepted language of the user agend.
     *
     * @var string
     */
    public $acceptedLanguage = '';

    /**
     * The current language.
     *
     * @var string
     */
    public static $language = '';

    /**
     * @var PMF_Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Language
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Returns an array of country codes for a specific FAQ record ID,
     * specific category ID or all languages used by FAQ records , categories.
     *
     * @param int    $id    ID
     * @param string $table Specifies table
     *
     * @return array
     */
    public function languageAvailable($id, $table = 'faqdata')
    {
        $output = [];

        if (isset($id)) {
            if ($id == 0) {
                // get languages for all ids
                $distinct = ' DISTINCT ';
                $where = '';
            } else {
                // get languages for specified id
                $distinct = '';
                $where = ' WHERE id = '.$id;
            }

            $query = sprintf('
                SELECT %s
                    lang
                FROM
                    %s%s
                %s',
                $distinct,
                PMF_Db::getTablePrefix(),
                $table,
                $where
            );

            $result = $this->config->getDb()->query($query);

            if ($this->config->getDb()->numRows($result) > 0) {
                while ($row = $this->config->getDb()->fetchObject($result)) {
                    $output[] = $row->lang;
                }
            }
        }

        return $output;
    }

    /**
     * Sets the current language for phpMyFAQ user session.
     *
     * @param bool   $configDetection Configuration detection
     * @param string $configLanguage  Language from configuration
     *
     * @return string
     */
    public function setLanguage($configDetection, $configLanguage)
    {
        $_lang = [];
        self::_getUserAgentLanguage();

        // Get language from: _POST, _GET, _COOKIE, phpMyFAQ configuration and the automatic language detection
        $_lang['post'] = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING);
        if (!is_null($_lang['post']) && !self::isASupportedLanguage($_lang['post'])) {
            $_lang['post'] = null;
        }
        // Get the user language
        $_lang['get'] = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
        if (!is_null($_lang['get']) && !self::isASupportedLanguage($_lang['get'])) {
            $_lang['get'] = null;
        }
        // Get the faq record language
        $_lang['artget'] = PMF_Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
        if (!is_null($_lang['artget']) && !self::isASupportedLanguage($_lang['artget'])) {
            $_lang['artget'] = null;
        }
        // Get the language from the session
        if (isset($_SESSION['pmf_lang']) && self::isASupportedLanguage($_SESSION['pmf_lang'])) {
            $_lang['session'] = trim($_SESSION['pmf_lang']);
        }

        // Get the language from the config
        if (isset($configLanguage)) {
            $confLangCode = str_replace(array('language_', '.php'), '', $configLanguage);
            if (self::isASupportedLanguage($confLangCode)) {
                $_lang['config'] = $confLangCode;
            }
        }
        // Detect the browser's language
        if ((true === $configDetection) && self::isASupportedLanguage($this->acceptedLanguage)) {
            $_lang['detection'] = strtolower($this->acceptedLanguage);
        }
        // Select the language
        if (isset($_lang['post'])) {
            self::$language = $_lang['post'];
            $_lang = null;
            unset($_lang);
        } elseif (isset($_lang['get'])) {
            self::$language = $_lang['get'];
        } elseif (isset($_lang['artget'])) {
            self::$language = $_lang['artget'];
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
     * Returns the current language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return self::$language;
    }

    /**
     * This function returns the available languages.
     *
     * @return array
     */
    public static function getAvailableLanguages()
    {
        global $languageCodes;

        $search = array('language_' , '.php');
        $languages = $languageFiles = [];

        $dir = new DirectoryIterator(PMF_LANGUAGE_DIR);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $languageFiles[] = strtoupper(
                    str_replace($search, '', trim($fileinfo->getFilename()))
                );
            }
        }

        foreach ($languageFiles as $lang) {
            // Check if the file is related to a (real) language before using it
            if (array_key_exists($lang, $languageCodes)) {
                $languages[strtolower($lang)] = $languageCodes[$lang];
            }
        }

        // Sort the languages list
        asort($languages);
        reset($languages);

        return $languages;
    }

    /**
     * This function displays the <select> box for the available languages
     * optionally filtered by excluding some provided languages.
     *
     * @param string $default
     * @param bool   $submitOnChange
     * @param array  $excludedLanguages
     * @param string $id
     *
     * @return string
     */
    public static function selectLanguages($default, $submitOnChange = false, Array $excludedLanguages = [], $id = 'language')
    {
        global $languageCodes;

        $onChange = ($submitOnChange ? ' onchange="this.form.submit();"' : '');
        $output = '<select class="form-control" name="'.$id.'" id="'.$id.'" size="1"'.$onChange.">\n";
        $languages = self::getAvailableLanguages();

        if (count($languages) > 0) {
            foreach ($languages as $lang => $value) {
                if (!in_array($lang, $excludedLanguages)) {
                    $output .= "\t".'<option value="'.$lang.'"';
                    if ($lang == $default) {
                        $output .= ' selected="selected"';
                    }
                    $output .= '>'.$value."</option>\n";
                }
            }
        } else {
            $output .= "\t<option value=\"en\">".$languageCodes['EN'].'</option>';
        }
        $output .= "</select>\n";

        return $output;
    }

    /**
     * Function for displaying all languages in <option>.
     *
     * @param string $lang              the languange to be selected
     * @param bool   $onlyThisLang      print only the passed language?
     * @param bool   $fileLanguageValue print the <language file> instead of the <language code> as value?
     *
     * @return string
     */
    public static function languageOptions($lang = '', $onlyThisLang = false, $fileLanguageValue = false)
    {
        $output = '';
        foreach (self::getAvailableLanguages() as $key => $value) {
            if ($onlyThisLang) {
                if (strtolower($key) == $lang) {
                    if ($fileLanguageValue) {
                        $output .= "\t<option value=\"language_".strtolower($lang).'.php"';
                    } else {
                        $output .= "\t<option value=\"".strtolower($lang).'"';
                    }
                    $output .= ' selected="selected"';
                    $output .= '>'.$value."</option>\n";
                    break;
                }
            } else {
                if ($fileLanguageValue) {
                    $output .= "\t<option value=\"language_".strtolower($key).'.php"';
                } else {
                    $output .= "\t<option value=\"".strtolower($key).'"';
                }
                if (strtolower($key) == $lang) {
                    $output .= ' selected="selected"';
                }
                $output .= '>'.$value."</option>\n";
            }
        }

        return $output;
    }

    /**
     * True if the language is supported by the current phpMyFAQ installation.
     *
     * @param string $langcode Language code
     *
     * @return bool
     */
    public static function isASupportedLanguage($langcode)
    {
        global $languageCodes;

        return isset($languageCodes[strtoupper($langcode)]);
    }

    /**
     * True if the language is supported by the bundled TinyMCE editor.
     *
     * TinyMCE Language is supported if there is a language file present in
     * PMF_ROOT/admin/editor/langs/$langcode.js
     *
     * TinyMCE language packs can be downloaded from 
     * http://tinymce.moxiecode.com/download_i18n.php
     * and extracted to PMF_ROOT/admin/editor
     *
     * @param string $langcode Language code
     *
     * @return bool
     */
    public static function isASupportedTinyMCELanguage($langcode)
    {
        return file_exists(
            PMF_ROOT_DIR.'/admin/assets/js/editor/langs/'.$langcode.'.js'
        );
    }

    /**
     * Gets the accepted language from the user agent.
     *
     * $_SERVER['HTTP_ACCEPT_LANGUAGE'] could be like the text below:
     * it,pt-br;q=0.8,en-us;q=0.5,en;q=0.3
     */
    private function _getUserAgentLanguage()
    {
        $matches = $languages = [];

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // ISO Language Codes, 2-letters: ISO 639-1, <Country tag>[-<Country subtag>]
            // Simplified language syntax detection: xx[-yy]
            preg_match_all(
                '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
                $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                $matches
            );

            if (count($matches[1])) {
                $languages = array_combine($matches[1], $matches[4]);
                foreach ($languages as $lang => $val) {
                    if ($val === '') {
                        $languages[$lang] = 1;
                    }
                }
                arsort($languages, SORT_NUMERIC);
            }
            foreach ($languages as $lang => $val) {
                if (self::isASupportedLanguage(strtoupper($lang))) {
                    $this->acceptedLanguage = $lang;
                    break;
                }
            }
        }
    }
}
