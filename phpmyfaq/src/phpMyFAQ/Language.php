<?php

namespace phpMyFAQ;

/**
 * Manages all language stuff.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo scaramuccia <matteo@phpmyfaq.de>
 * @author Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-05-14
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Class Language
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Matteo scaramuccia <matteo@phpmyfaq.de>
 * @author Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-05-14
 */
class Language
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
     * @var Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
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
    public function languageAvailable(int $id, string $table = 'faqdata'): array
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
                Db::getTablePrefix(),
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
    public function setLanguage(bool $configDetection, string $configLanguage): string
    {
        $detectedLang = [];
        self::getUserAgentLanguage();

        // Get language from: _POST, _GET, _COOKIE, phpMyFAQ configuration and the automatic language detection
        $detectedLang['post'] = Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING);
        if (!is_null($detectedLang['post']) && !self::isASupportedLanguage($detectedLang['post'])) {
            $detectedLang['post'] = null;
        }
        // Get the user language
        $detectedLang['get'] = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
        if (!is_null($detectedLang['get']) && !self::isASupportedLanguage($detectedLang['get'])) {
            $detectedLang['get'] = null;
        }
        // Get the faq record language
        $detectedLang['artget'] = Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRING);
        if (!is_null($detectedLang['artget']) && !self::isASupportedLanguage($detectedLang['artget'])) {
            $detectedLang['artget'] = null;
        }
        // Get the language from the session
        if (isset($_SESSION['lang']) && self::isASupportedLanguage($_SESSION['lang'])) {
            $detectedLang['session'] = trim($_SESSION['lang']);
        }

        // Get the language from the config
        if (isset($configLanguage)) {
            $confLangCode = str_replace(array('language_', '.php'), '', $configLanguage);
            if (self::isASupportedLanguage($confLangCode)) {
                $detectedLang['config'] = $confLangCode;
            }
        }
        // Detect the browser's language
        if ((true === $configDetection) && self::isASupportedLanguage($this->acceptedLanguage)) {
            $detectedLang['detection'] = strtolower($this->acceptedLanguage);
        }
        // Select the language
        if (isset($detectedLang['post'])) {
            self::$language = $detectedLang['post'];
            $detectedLang = null;
            unset($detectedLang);
        } elseif (isset($detectedLang['get'])) {
            self::$language = $detectedLang['get'];
        } elseif (isset($detectedLang['artget'])) {
            self::$language = $detectedLang['artget'];
        } elseif (isset($detectedLang['session'])) {
            self::$language = $detectedLang['session'];
            $detectedLang = null;
            unset($detectedLang);
        } elseif (isset($detectedLang['detection'])) {
            self::$language = $detectedLang['detection'];
            $detectedLang = null;
            unset($detectedLang);
        } elseif (isset($detectedLang['config'])) {
            self::$language = $detectedLang['config'];
            $detectedLang = null;
            unset($detectedLang);
        } else {
            self::$language = 'en'; // just a fallback
        }

        return $_SESSION['lang'] = self::$language;
    }

    /**
     * Returns the current language.
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return self::$language;
    }

    /**
     * This function returns the available languages.
     *
     * @return array
     */
    public static function getAvailableLanguages(): array
    {
        global $languageCodes;

        $search = array('language_', '.php');
        $languages = $languageFiles = [];

        $dir = new \DirectoryIterator(LANGUAGE_DIR);
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
    public static function selectLanguages(
        string $default,
        bool $submitOnChange = false,
        array $excludedLanguages = [],
        string $id = 'language'): string
    {
        global $languageCodes;

        $onChange = ($submitOnChange ? ' onchange="this.form.submit();"' : '');
        $output = '<select class="form-control" name="'.$id.'" id="'.$id.'"'.$onChange.">\n";
        $languages = self::getAvailableLanguages();

        if (count($languages) > 0) {
            foreach ($languages as $lang => $value) {
                if (!in_array($lang, $excludedLanguages)) {
                    $output .= "\t".'<option value="'.$lang.'"';
                    if ($lang == $default) {
                        $output .= ' selected';
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
    public static function languageOptions(
        string $lang = '',
        bool $onlyThisLang = false,
        bool $fileLanguageValue = false): string
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
     * @param string|null $langCode Language code
     *
     * @return bool
     */
    public static function isASupportedLanguage($langCode): bool
    {
        global $languageCodes;

        return isset($languageCodes[strtoupper($langCode)]);
    }

    /**
     * True if the language is supported by the bundled TinyMCE editor.
     *
     * TinyMCE Language is supported if there is a language file present in
     * ROOT/admin/editor/langs/$langcode.js
     *
     * TinyMCE language packs can be downloaded from 
     * http://tinymce.moxiecode.com/download_i18n.php
     * and extracted to ROOT/admin/editor
     *
     * @param string|null $langCode Language code
     *
     * @return bool
     */
    public static function isASupportedTinyMCELanguage($langCode): bool
    {
        return file_exists(
            PMF_ROOT_DIR.'/admin/assets/js/editor/langs/'.$langCode.'.js'
        );
    }

    /**
     * Gets the accepted language from the user agent.
     *
     * $_SERVER['HTTP_ACCEPT_LANGUAGE'] could be like the text below:
     * it,pt-br;q=0.8,en-us;q=0.5,en;q=0.3
     */
    private function getUserAgentLanguage()
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
