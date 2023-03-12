<?php

/**
 * Manages all language stuff.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo scaramuccia <matteo@phpmyfaq.de>
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-05-14
 */

namespace phpMyFAQ;

use phpMyFAQ\Language\LanguageCodes;

/**
 * Class Language
 *
 * @package phpMyFAQ
 */
class Language
{
    /**
     * The current language.
     *
     * @var string
     */
    public static $language = '';

    /**
     * The accepted language of the user agent.
     */
    private string $acceptLanguage = '';

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * True if the language is supported by the bundled TinyMCE editor.
     *
     * TinyMCE Language is supported if there is a language file present in
     * ROOT/admin/editor/langs/$langcode.js
     *
     * TinyMCE language packs can be downloaded from
     * https://tinymce.moxiecode.com/download_i18n.php
     * and extracted to ROOT/admin/editor
     *
     * @param string $langCode Language code
     */
    public static function isASupportedTinyMCELanguage(string $langCode): bool
    {
        return file_exists(
            PMF_ROOT_DIR . '/admin/assets/js/editor/langs/' . $langCode . '.js'
        );
    }

    /**
     * Returns an array of country codes for a specific FAQ record ID,
     * specific category ID or all languages used by FAQ records , categories.
     *
     * @param int    $id    ID
     * @param string $table Specifies table
     */
    public function languageAvailable(int $id, string $table = 'faqdata'): array
    {
        $distinct = null;
        $where = null;
        $query = null;
        $result = null;
        $output = [];
        if ($id == 0) {
            // get languages for all ids
            $distinct = ' DISTINCT ';
            $where = '';
        } else {
            // get languages for specified id
            $distinct = '';
            $where = ' WHERE id = ' . $id;
        }
        $query = sprintf(
            '
                SELECT %s
                    lang
                FROM
                    %s%s
                %s',
            $distinct,
            Database::getTablePrefix(),
            $table,
            $where
        );
        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $output[] = $row->lang;
            }
        }

        return $output;
    }

    /**
     * Sets the current language for phpMyFAQ user session.
     *
     * @param bool   $configDetection Configuration detection
     * @param string $configLanguage  Language from configuration
     */
    public function setLanguage(bool $configDetection, string $configLanguage): string
    {
        $confLangCode = null;
        $detectedLang = [];
        self::getUserAgentLanguage();

        // Get language from: _POST, _GET, _COOKIE, phpMyFAQ configuration and the automatic language detection
        $detectedLang['post'] = Filter::filterInput(INPUT_POST, 'language', FILTER_UNSAFE_RAW);
        if (!is_null($detectedLang['post']) && !self::isASupportedLanguage($detectedLang['post'])) {
            $detectedLang['post'] = null;
        }
        // Get the user language
        $detectedLang['get'] = Filter::filterInput(INPUT_GET, 'lang', FILTER_UNSAFE_RAW);
        if (!is_null($detectedLang['get']) && !self::isASupportedLanguage($detectedLang['get'])) {
            $detectedLang['get'] = null;
        }
        // Get the faq record language
        $detectedLang['artget'] = Filter::filterInput(INPUT_GET, 'artlang', FILTER_UNSAFE_RAW);
        if (!is_null($detectedLang['artget']) && !self::isASupportedLanguage($detectedLang['artget'])) {
            $detectedLang['artget'] = null;
        }
        // Get the language from the session
        if (isset($_SESSION['lang']) && self::isASupportedLanguage($_SESSION['lang'])) {
            $detectedLang['session'] = trim((string) $_SESSION['lang']);
        }
        // Get the language from the config
        $confLangCode = str_replace(['language_', '.php'], '', $configLanguage);
        if (self::isASupportedLanguage($confLangCode)) {
            $detectedLang['config'] = $confLangCode;
        }
        // Detect the browser's language
        if ((true === $configDetection) && self::isASupportedLanguage($this->acceptLanguage)) {
            $detectedLang['detection'] = strtolower($this->acceptLanguage);
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
            self::$language = 'en'; // just a last fallback
        }

        return $_SESSION['lang'] = self::$language;
    }

    public function setLanguageByAcceptLanguage(): string
    {
        self::getUserAgentLanguage();

        return self::$language = $this->acceptLanguage;
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
                (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                $matches
            );

            if (is_countable($matches[1]) ? count($matches[1]) : 0) {
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
                    $this->acceptLanguage = $lang;
                    break;
                }
            }

            // If the browser e.g. sends "en-us", we want to get "en" only.
            if ('' === $this->acceptLanguage) {
                foreach ($languages as $lang => $val) {
                    $lang = substr($lang, 0, 2);
                    if (self::isASupportedLanguage(strtoupper($lang))) {
                        $this->acceptLanguage = $lang;
                        break;
                    }
                }
            }
        }
    }

    /**
     * True if the language is supported by the current phpMyFAQ installation.
     *
     * @param string|null $langCode Language code
     */
    public static function isASupportedLanguage(?string $langCode): bool
    {
        return !($langCode === null) && LanguageCodes::get($langCode) !== null;
    }

    /**
     * Returns the current language.
     */
    public function getLanguage(): string
    {
        return self::$language;
    }
}
