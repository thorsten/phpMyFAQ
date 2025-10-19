<?php

declare(strict_types=1);

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
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-05-14
 */

namespace phpMyFAQ;

use phpMyFAQ\Language\LanguageCodes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
    public function __construct(
        private readonly Configuration $configuration,
        private readonly SessionInterface $session,
    ) {
    }

    /**
     * Returns an array of country codes for a specific FAQ record ID,
     * specific category ID or all languages used by FAQ records, categories.
     *
     * @param int    $identifier ID
     * @param string $table      Specifies table
     * @return string[]
     */
    public function isLanguageAvailable(int $identifier, string $table = 'faqdata'): array
    {
        $output = [];

        if ($identifier === 0) {
            $distinct = ' DISTINCT ';
            $where = '';
        } else {
            $distinct = '';
            $where = ' WHERE id = ' . $identifier;
        }

        $query = sprintf('SELECT %s lang FROM %s%s %s', $distinct, Database::getTablePrefix(), $table, $where);

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
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
        $detectedLang = $this->detectLanguage($configDetection, $configLanguage);
        self::$language = $this->selectLanguage($detectedLang);
        $this->session->set('lang', self::$language);
        return strtolower(self::$language);
    }

    public function setLanguageByAcceptLanguage(): string
    {
        self::getUserAgentLanguage();

        return self::$language = $this->acceptLanguage;
    }

    /**
     * True if the language is supported by the current phpMyFAQ installation.
     *
     * @param string|null $langCode Language code
     */
    public static function isASupportedLanguage(?string $langCode): bool
    {
        return $langCode !== null && LanguageCodes::getSupported($langCode) !== null;
    }

    /**
     * Returns the current language.
     */
    public function getLanguage(): string
    {
        return strtolower(self::$language);
    }

    /**
     * Detects the language.
     *
     * @param bool   $configDetection Configuration detection
     * @param string $configLanguage  Language from configuration
     * @return string[]
     */
    private function detectLanguage(bool $configDetection, string $configLanguage): array
    {
        $detectedLang = [];
        $this->getUserAgentLanguage();

        $detectedLang['post'] = $this->getPostLanguage();
        $detectedLang['get'] = $this->getGetLanguage();
        $detectedLang['artget'] = $this->getArtGetLanguage();
        $detectedLang['session'] = $this->getSessionLanguage();
        $detectedLang['config'] = $this->getConfigLanguage($configLanguage);
        $detectedLang['detection'] = $this->getDetectionLanguage($configDetection);

        return $detectedLang;
    }

    private function getPostLanguage(): ?string
    {
        $lang = Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_SPECIAL_CHARS);
        return static::isASupportedLanguage($lang) ? $lang : null;
    }

    private function getGetLanguage(): ?string
    {
        $lang = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
        return static::isASupportedLanguage($lang) ? $lang : null;
    }

    private function getArtGetLanguage(): ?string
    {
        $lang = Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_SPECIAL_CHARS);
        return static::isASupportedLanguage($lang) ? $lang : null;
    }

    private function getSessionLanguage(): ?string
    {
        $lang = $this->session->get('lang');
        return static::isASupportedLanguage($lang) ? trim((string) $lang) : null;
    }

    private function getConfigLanguage(string $configLanguage): ?string
    {
        $lang = str_replace(['language_', '.php'], '', $configLanguage);
        return static::isASupportedLanguage($lang) ? $lang : null;
    }

    private function getDetectionLanguage(bool $configDetection): ?string
    {
        return $configDetection && static::isASupportedLanguage($this->acceptLanguage)
            ? strtolower($this->acceptLanguage)
            : null;
    }

    /**
     * Selects the language.
     *
     * @param string[] $detectedLanguage Detected language
     */
    private function selectLanguage(array $detectedLanguage): string
    {
        $priorityOrder = ['post', 'get', 'artget', 'session', 'detection', 'config'];

        foreach ($priorityOrder as $source) {
            if (!empty($detectedLanguage[$source])) {
                return $detectedLanguage[$source];
            }
        }

        return 'en';
    }

    /**
     * Gets the accepted language from the user agent.
     *
     * HTTP_ACCEPT_LANGUAGE could be like the text below:
     * it,pt_BR;q=0.8,en_US;q=0.5,en;q=0.3
     */
    private function getUserAgentLanguage(): void
    {
        $languages = Request::createFromGlobals()->getLanguages();

        foreach ($languages as $language) {
            if (self::isASupportedLanguage(strtoupper($language))) {
                $this->acceptLanguage = strtolower($language);
                break;
            }
        }

        // If the browser, e.g., sends "en_us", we want to get "en" only.
        if ('' === $this->acceptLanguage) {
            foreach ($languages as $language) {
                $language = substr($language, 0, 2);
                if (self::isASupportedLanguage(strtoupper($language))) {
                    $this->acceptLanguage = strtolower($language);
                    break;
                }
            }
        }
    }
}
