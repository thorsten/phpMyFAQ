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
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Language
 *
 * @package phpMyFAQ
 */
class Language
{
    /**
     * The current language.
     */
    public static string $language = '';

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
     * Returns an array of country codes for a specific FAQ record ID,
     * specific category ID or all languages used by FAQ records , categories.
     *
     * @param int    $id    ID
     * @param string $table Specifies table
     * @return string[]
     */
    public function isLanguageAvailable(int $id, string $table = 'faqdata'): array
    {
        $output = [];

        if ($id === 0) {
            $distinct = ' DISTINCT ';
            $where = '';
        } else {
            $distinct = '';
            $where = ' WHERE id = ' . $id;
        }

        $query = sprintf(
            'SELECT %s lang FROM %s%s %s',
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
        $detectedLang = [];
        self::getUserAgentLanguage();

        // Get language from: _POST, _GET, _COOKIE, phpMyFAQ configuration and the automatic language detection
        $detectedLang['post'] = Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!is_null($detectedLang['post']) && !self::isASupportedLanguage($detectedLang['post'])) {
            $detectedLang['post'] = null;
        }
        // Get the user language
        $detectedLang['get'] = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!is_null($detectedLang['get']) && !self::isASupportedLanguage($detectedLang['get'])) {
            $detectedLang['get'] = null;
        }
        // Get the faq record language
        $detectedLang['artget'] = Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_SPECIAL_CHARS);
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
    private function getUserAgentLanguage(): void
    {
        $languages = Request::createFromGlobals()->getLanguages();

        foreach ($languages as $language) {
            if (self::isASupportedLanguage(strtoupper($language))) {
                $this->acceptLanguage = $language;
                break;
            }
        }

        // If the browser e.g. sends "en-us", we want to get "en" only.
        if ('' === $this->acceptLanguage) {
            foreach ($languages as $language) {
                $language = substr($language, 0, 2);
                if (self::isASupportedLanguage(strtoupper($language))) {
                    $this->acceptLanguage = $language;
                    break;
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
        return !($langCode === null) && LanguageCodes::getSupported($langCode) !== null;
    }

    /**
     * Returns the current language.
     */
    public function getLanguage(): string
    {
        return self::$language;
    }
}
