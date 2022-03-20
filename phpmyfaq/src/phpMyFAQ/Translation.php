<?php

/**
 * The Translation class provides methods and functions for the
 * translation handling.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-20
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;

class Translation
{
    /**  @var string The directory with the language files */
    protected string $languagesDir = 'lang';

    /** @var string The default fallback language */
    protected string $defaultLanguage = 'en';

    /** @var string The current language */
    protected string $currentLanguage = '';

    /** @var array The loaded languages */
    protected array $loadedLanguages = [];

    /** @var bool Translation already initialized? */
    protected bool $isReady = false;

    /** @var Translation|null */
    private static ?Translation $instance = null;

    /**
     * @return Translation
     */
    public static function create(): Translation
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Returns the translation of a specific key from the current language
     *
     * @param string $languageKey
     * @return string|null
     * @throws Exception
     */
    public static function get(string $languageKey): ?string
    {
        self::$instance->checkInit();
        self::$instance->checkLanguageLoaded();

        if (!empty(self::$instance->loadedLanguages[self::$instance->currentLanguage][$languageKey])) {
            return self::$instance->loadedLanguages[self::$instance->currentLanguage][$languageKey];
        }

        return self::$instance->loadedLanguages[self::$instance->defaultLanguage][$languageKey];
    }

    /**
     * @param string $languagesDir
     * @return Translation
     * @throws Exception
     */
    public function setLanguagesDir(string $languagesDir): Translation
    {
        self::$instance->languagesDir = $languagesDir;
        self::$instance->checkLanguageDirectory();

        return self::$instance;
    }

    /**
     * @param string $defaultLanguage
     * @return Translation
     * @throws Exception
     */
    public function setDefaultLanguage(string $defaultLanguage): Translation
    {
        self::$instance->defaultLanguage = $defaultLanguage;
        self::$instance->checkDefaultLanguage();
        self::$instance->checkDefaultLanguageLoaded();

        return self::$instance;
    }

    /**
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    /**
     * @param string $currentLanguage
     * @return Translation
     * @throws Exception
     */
    public function setCurrentLanguage(string $currentLanguage): Translation
    {
        self::$instance->checkInit();
        self::$instance->currentLanguage = $currentLanguage;
        self::$instance->checkLanguageLoaded();

        return self::$instance;
    }

    /**
     * @return string
     */
    public function getCurrentLanguage(): string
    {
        return self::$instance->currentLanguage;
    }

    /**
     * Returns the single instance.
     *
     * @return Translation
     */
    public static function getInstance(): Translation
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className();
        }

        return self::$instance;
    }

    /**
     * Checks if the default language is already loaded.
     */
    protected function checkDefaultLanguageLoaded(): void
    {
        if (empty(self::$instance->loadedLanguages[self::$instance->defaultLanguage])) {
            self::$instance->checkCurrentLanguage();
            self::$instance->loadedLanguages[self::$instance->defaultLanguage] = require(
                self::$instance->filename(self::$instance->defaultLanguage)
            );
        }
    }

    /**
     * Checks if current language is already loaded. Loading new language only when needed.
     */
    protected function checkLanguageLoaded(): void
    {
        if (empty(self::$instance->loadedLanguages[self::$instance->currentLanguage])) {
            self::$instance->checkCurrentLanguage();
            self::$instance->loadedLanguages[self::$instance->currentLanguage] = require(
                self::$instance->filename(self::$instance->currentLanguage)
            );
        }
    }

    /**
     * Checks if language directory exists. If not, throws an exception.
     * @throws Exception
     */
    protected function checkLanguageDirectory(): void
    {
        if (!is_dir(self::$instance->languagesDir)) {
            throw new Exception('The directory ' . self::$instance->languagesDir . ' was not found!');
        }
    }

    /**
     * Checks if default language exists. If not, throws an exception.
     * @throws Exception
     */
    protected function checkDefaultLanguage(): void
    {
        if (!file_exists(static::filename(self::$instance->defaultLanguage))) {
            throw new Exception('Default language "' . self::$instance->defaultLanguage . '"not found!');
        }
    }

    /**
     * Checks if the Translation class has been initialized.
     * @throws Exception
     */
    protected function checkInit(): void
    {
        if (!self::$instance->isReady) {
            static::init();
        }
    }

    /**
     * @throws Exception
     */
    protected function init(): void
    {
        self::$instance->checkLanguageDirectory();
        self::$instance->checkDefaultLanguage();
        self::$instance->currentLanguage = self::$instance->getCurrentLanguage();
        self::$instance->isReady = true;
    }

    /**
     * Checks if locale for current language exists. If not, start using the default language.
     */
    protected function checkCurrentLanguage(): void
    {
        if (!file_exists(self::$instance->filename(self::$instance->currentLanguage))) {
            self::$instance->currentLanguage = self::$instance->defaultLanguage;
        }
    }

    /**
     * Returns the filename for the given language.
     * @param string $language
     * @return string
     */
    protected function filename(string $language): string
    {
        return self::$instance->languagesDir . DIRECTORY_SEPARATOR . 'language_' . $language . '.php';
    }

    /**
     * __clone() Magic method to prevent cloning.
     */
    private function __clone()
    {
    }
}
