<?php

/**
 * The Translation class provides methods and functions for the
 * translation file handling.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-20
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use stdClass;

class Translation
{
    /**  @var string The directory with the language files */
    protected string $languagesDir = 'translations';

    /** @var string The default fallback language */
    protected string $defaultLanguage = 'en';

    /** @var string The current language */
    protected string $currentLanguage = '';

    /** @var string[][] The loaded languages */
    protected array $loadedLanguages = [];

    /** @var bool Translation already initialized? */
    protected bool $isReady = false;

    private static ?Translation $translation = null;

    public static function create(): Translation
    {
        if (self::$translation === null) {
            self::$translation = new self();
        }

        return self::$translation;
    }

    /**
     * Returns the translation of a specific key from the current language
     *
     * @return string|string[][]|null
     */
    public static function get(string $languageKey): string|array|null
    {
        try {
            self::$translation->checkInit();
            self::$translation->checkLanguageLoaded();

            if (!empty(self::$translation->loadedLanguages[self::$translation->currentLanguage][$languageKey])) {
                return self::$translation->loadedLanguages[self::$translation->currentLanguage][$languageKey];
            }

            return self::$translation->loadedLanguages[self::$translation->defaultLanguage][$languageKey];
        } catch (Exception) {
            // handle exception
            Configuration::getConfigurationInstance()->getLogger()->error(
                'Error while fetching translation key: ' . $languageKey
            );
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function setLanguagesDir(string $languagesDir): Translation
    {
        self::$translation->languagesDir = $languagesDir;
        self::$translation->checkLanguageDirectory();

        return self::$translation;
    }

    /**
     * @throws Exception
     */
    public function setDefaultLanguage(string $defaultLanguage): Translation
    {
        self::$translation->defaultLanguage = $defaultLanguage;
        self::$translation->checkDefaultLanguage();
        self::$translation->checkDefaultLanguageLoaded();

        return self::$translation;
    }

    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    /**
     * @throws Exception
     */
    public function setCurrentLanguage(string $currentLanguage): Translation
    {
        self::$translation->checkInit();
        self::$translation->currentLanguage = $currentLanguage;
        self::$translation->checkLanguageLoaded();

        return self::$translation;
    }

    public function getCurrentLanguage(): string
    {
        return self::$translation->currentLanguage;
    }

    /**
     * Returns the single instance.
     */
    public static function getInstance(): Translation
    {
        if (null == self::$translation) {
            $className = self::class;
            self::$translation = new $className();
        }

        return self::$translation;
    }

    /**
     * Use "mbstring" extension if available and when possible
     */
    public function setMultiByteLanguage(): void
    {
        $validMultiByteStrings = ['ja', 'en', 'uni'];
        $multiByteLanguage = (self::get('metaLanguage') != 'ja') ? 'uni' : self::get('metaLanguage');
        if (function_exists('mb_language') && in_array($multiByteLanguage, $validMultiByteStrings)) {
            mb_language($multiByteLanguage);
            mb_internal_encoding('utf-8');
        }
    }

    /**
     * Returns the configuration items from the current language for the given section.
     *
     * @return array<string, array<string, string>>
     */
    public static function getConfigurationItems(string $section = ''): array
    {
        $configuration = [];

        foreach (self::fetchTranslationFile() as $key => $value) {
            if (str_starts_with($key, $section)) {
                $configuration[$key] = [
                    'element' => $value[0] ?? '',
                    'label' => $value[1] ?? '',
                    'description' => $value[2] ?? '',
                ];

                switch ($key) {
                    case 'records.maxAttachmentSize':
                        /** @phpstan-ignore-next-line */
                        $configuration[$key]['label'] = sprintf(
                            $configuration[$key]['label'],
                            ini_get('upload_max_filesize')
                        );
                        break;
                    case 'main.dateFormat':
                        $configuration[$key]['label'] = sprintf(
                            '<a target="_blank" href="https://www.php.net/manual/en/function.date.php">%s</a>',
                            $configuration[$key]['label']
                        );
                        break;
                }
            }
        }

        Utils::moveToTop($configuration, 'main.maintenanceMode');

        return $configuration;
    }

    /**
     * Checks if the default language is already loaded.
     */
    protected function checkDefaultLanguageLoaded(): void
    {
        if (empty(self::$translation->loadedLanguages[self::$translation->defaultLanguage])) {
            self::$translation->checkCurrentLanguage();
            self::$translation->loadedLanguages[self::$translation->defaultLanguage] = require(
                self::$translation->filename(self::$translation->defaultLanguage)
            );
        }
    }

    /**
     * Checks if current language is already loaded. Loading new language only when needed.
     */
    protected function checkLanguageLoaded(): void
    {
        if (empty(self::$translation->loadedLanguages[self::$translation->currentLanguage])) {
            self::$translation->checkCurrentLanguage();
            self::$translation->loadedLanguages[self::$translation->currentLanguage] = require(
                self::$translation->filename(self::$translation->currentLanguage)
            );
        }
    }

    /**
     * Checks if language directory exists. If not, throw an exception.
     * @throws Exception
     */
    protected function checkLanguageDirectory(): void
    {
        if (!is_dir(self::$translation->languagesDir)) {
            throw new Exception('The directory ' . self::$translation->languagesDir . ' was not found!');
        }
    }

    /**
     * Checks if default language exists. If not, throw an exception.
     * @throws Exception
     */
    protected function checkDefaultLanguage(): void
    {
        if (!file_exists(static::filename(self::$translation->defaultLanguage))) {
            throw new Exception('Default language "' . self::$translation->defaultLanguage . '"not found!');
        }
    }

    /**
     * Checks if the Translation class has been initialized.
     * @throws Exception
     */
    protected function checkInit(): void
    {
        if (!self::$translation->isReady) {
            static::init();
        }
    }

    /**
     * @throws Exception
     */
    protected function init(): void
    {
        self::$translation->checkLanguageDirectory();
        self::$translation->checkDefaultLanguage();

        self::$translation->currentLanguage = self::$translation->getCurrentLanguage();
        self::$translation->isReady = true;
    }

    /**
     * Checks if locale for current language exists. If not, start using the default language.
     */
    protected function checkCurrentLanguage(): void
    {
        if (!file_exists(self::$translation->filename(self::$translation->currentLanguage))) {
            self::$translation->currentLanguage = self::$translation->defaultLanguage;
        }
    }

    /**
     * Returns the filename for the given language.
     */
    protected function filename(string $language): string
    {
        return self::$translation->languagesDir . DIRECTORY_SEPARATOR . 'language_' . $language . '.php';
    }

    /**
     * Fetches the translation file for the current language.
     * @return array<string, array<string, string>>
     */
    private static function fetchTranslationFile(): array
    {
        $LANG_CONF = [];
        include self::$translation->filename(self::$translation->currentLanguage);

        return $LANG_CONF;
    }
}
