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
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-03-20
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;

class Translation
{
    /**  @var string The directory with the language files */
    protected string $translationsDir = 'translations';

    /** @var string The default fallback language */
    protected string $defaultLanguage = 'en';

    /** @var string The current language */
    protected string $currentLanguage = '';

    /** @var string[][] The loaded languages */
    protected array $loadedLanguages = [];

    /** @var array<string, array<string, array<string, string>>> Plugin translations: [pluginName][language][key] */
    protected array $pluginTranslations = [];

    /** @var bool Translation already initialized? */
    protected bool $isReady = false;

    private static ?Translation $translation = null;

    public static function create(): Translation
    {
        if (!self::$translation instanceof Translation) {
            self::$translation = new self();
        }

        return self::$translation;
    }

    /**
     * @internal Only for tests to reset the static instance.
     */
    public static function resetInstance(): void
    {
        self::$translation = null;
    }

    /**
     * Returns the translation of a specific key from the current language
     *
     * @return string|string[][]|null
     */
    public static function get(string $key): string|array|null
    {
        try {
            self::$translation->checkInit();
            self::$translation->checkLanguageLoaded();

            // Check if key uses plugin namespace format: plugin.PluginName.messageKey
            if (str_starts_with($key, 'plugin.')) {
                $parts = explode('.', $key, 3);

                if (count($parts) === 3) {
                    [$namespace, $pluginName, $messageKey] = $parts;

                    // Try the current language first
                    if (
                        isset(
                            self::$translation->pluginTranslations[$pluginName][self::$translation->currentLanguage][$messageKey],
                        )
                    ) {
                        return self::$translation->pluginTranslations[$pluginName][self::$translation->currentLanguage][$messageKey];
                    }

                    // Fallback to the default language
                    if (
                        isset(
                            self::$translation->pluginTranslations[$pluginName][self::$translation->defaultLanguage][$messageKey],
                        )
                    ) {
                        return self::$translation->pluginTranslations[$pluginName][self::$translation->defaultLanguage][$messageKey];
                    }

                    return null;
                }
            }

            if (
                isset(self::$translation->loadedLanguages[self::$translation->currentLanguage][$key])
                && self::$translation->loadedLanguages[self::$translation->currentLanguage][$key] !== ''
            ) {
                return self::$translation->loadedLanguages[self::$translation->currentLanguage][$key];
            }

            return self::$translation->loadedLanguages[self::$translation->defaultLanguage][$key] ?? null;
        } catch (Exception) {
            Configuration::getConfigurationInstance()
                ->getLogger()
                ->error('Error while fetching translation key: ' . $key);
        }

        return null;
    }

    /**
     * Checks if a specific translation key exists in the current or default language.
     */
    public static function has(string $key): bool
    {
        try {
            self::$translation->checkInit();
            self::$translation->checkLanguageLoaded();

            // Check plugin namespace
            if (str_starts_with($key, 'plugin.')) {
                $parts = explode('.', $key, 3);

                if (count($parts) === 3) {
                    [$namespace, $pluginName, $messageKey] = $parts;

                    if (
                        isset(
                            self::$translation->pluginTranslations[$pluginName][self::$translation->currentLanguage][$messageKey],
                        )
                    ) {
                        return true;
                    }

                    if (
                        isset(
                            self::$translation->pluginTranslations[$pluginName][self::$translation->defaultLanguage][$messageKey],
                        )
                    ) {
                        return true;
                    }

                    return false;
                }
            }

            // Original core logic
            if (isset(self::$translation->loadedLanguages[self::$translation->currentLanguage][$key])) {
                return true;
            }

            if (isset(self::$translation->loadedLanguages[self::$translation->defaultLanguage][$key])) {
                return true;
            }
        } catch (Exception) { /* @mago-expect lint:no-empty-catch-clause */
        }

        return false;
    }

    /**
     * Returns all translations from the current language.
     * @throws Exception
     * @return array<string, string>
     */
    public static function getAll(): array
    {
        self::$translation->checkInit();
        self::$translation->checkLanguageLoaded();

        return self::$translation->loadedLanguages[self::$translation->currentLanguage];
    }

    /**
     * @throws Exception
     */
    public function setTranslationsDir(string $translationsDir): Translation
    {
        self::$translation->translationsDir = $translationsDir;
        self::$translation->checkTranslationsDirectory();

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
        if (null === self::$translation) {
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
        $multiByteLanguage = self::get(key: 'metaLanguage') !== 'ja' ? 'uni' : self::get(key: 'metaLanguage');
        if (
            function_exists(function: 'mb_language')
            && in_array($multiByteLanguage, $validMultiByteStrings, strict: true)
        ) {
            mb_language($multiByteLanguage);
            mb_internal_encoding(encoding: 'utf-8');
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
            if (!str_starts_with($key, $section)) {
                continue;
            }

            $configuration[$key] = [
                'element' => $value[0] ?? '',
                'label' => $value[1] ?? '',
                'description' => $value[2] ?? '',
            ];

            switch ($key) {
                case 'records.maxAttachmentSize':
                    $configuration[$key]['label'] = sprintf(
                        $configuration[$key]['label'],
                        ini_get(option: 'upload_max_filesize'),
                    );
                    break;
                case 'main.dateFormat':
                    $configuration[$key]['label'] =
                        '<a target="_blank" href="https://www.php.net/manual/en/function.date.php">'
                        . $configuration[$key]['label']
                        . '</a>';
                    break;
            }
        }

        Utils::moveToTop($configuration, key: 'main.maintenanceMode');

        return $configuration;
    }

    /**
     * Registers translations for a plugin from its translations directory
     *
     * @param string $pluginName The plugin name (for namespace)
     * @param string $translationsDir Absolute path to plugin's translations directory
     * @throws Exception
     */
    public function registerPluginTranslations(string $pluginName, string $translationsDir): void
    {
        if (!is_dir($translationsDir)) {
            return; // Silently skip if no translations directory
        }

        // Load all language files from plugin translations directory
        $languageFiles = glob($translationsDir . '/language_*.php');

        if ($languageFiles === false) {
            return; // Silently skip if glob fails
        }

        foreach ($languageFiles as $file) {
            // Extract language code from filename: language_en.php -> en
            if (preg_match('/language_([a-z]{2,3}(_[a-z]{2})?)\.php$/i', basename($file), $matches)) {
                $langCode = strtolower($matches[1]);

                // Include the file and extract the $PMF_LANG array
                $PMF_LANG = [];
                include $file;

                // Store in namespaced structure
                if (!isset($this->pluginTranslations[$pluginName])) {
                    $this->pluginTranslations[$pluginName] = [];
                }

                $this->pluginTranslations[$pluginName][$langCode] = $PMF_LANG;
            }
        }
    }

    // ---------------------------------------------------------------------
    // Internal helpers (initialization, loading, filesystem checks)
    // ---------------------------------------------------------------------

    /**
     * Checks if the default language is already loaded.
     */
    protected function checkDefaultLanguageLoaded(): void
    {
        $this->ensureLanguageLoaded(self::$translation->defaultLanguage);
    }

    /**
     * Checks if the current language is already loaded. Loading new language only when needed.
     */
    protected function checkLanguageLoaded(): void
    {
        $this->ensureLanguageLoaded(self::$translation->currentLanguage);
    }

    /**
     * Ensures that the given language is loaded in the cache.
     */
    private function ensureLanguageLoaded(string $language): void
    {
        $loadedLanguages = &self::$translation->loadedLanguages;

        if (isset($loadedLanguages[$language]) && $loadedLanguages[$language] !== []) {
            return;
        }

        self::$translation->checkCurrentLanguage();
        $loadedLanguages[$language] = require self::$translation->filename($language);
    }

    /**
     * Checks if the translations directory exists. If not, throw an exception.
     * @throws Exception
     */
    protected function checkTranslationsDirectory(): void
    {
        if (!is_dir(self::$translation->translationsDir)) {
            throw new Exception('The directory ' . self::$translation->translationsDir . ' was not found!');
        }
    }

    /**
     * Checks if the default language exists. If not, throw an exception.
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
            self::performInit();
        }
    }

    /**
     * Performs the actual initialization logic.
     *
     * @throws Exception
     */
    private static function performInit(): void
    {
        self::$translation->checkTranslationsDirectory();
        self::$translation->checkDefaultLanguage();

        self::$translation->currentLanguage = self::$translation->getCurrentLanguage();
        self::$translation->isReady = true;
    }

    /**
     * Checks if locale for the current language exists. If not, start using the default language.
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
        return self::$translation->translationsDir . DIRECTORY_SEPARATOR . 'language_' . strtolower($language) . '.php';
    }

    /**
     * Fetches the translation file for the current language.
     * @return array<string, array<string, string>>
     */
    private static function fetchTranslationFile(): array
    {
        $LANG_CONF = [];
        include self::$translation->filename(language: 'en');
        include self::$translation->filename(self::$translation->currentLanguage);

        return $LANG_CONF;
    }
}
