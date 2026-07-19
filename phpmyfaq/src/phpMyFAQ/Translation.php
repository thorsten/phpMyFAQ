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

    /** @var array<string, array<string, string|array<int, string>>> The loaded languages */
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
     * Returns the translation of a specific key from the current language.
     * Returns a string for regular keys, an array for plural form keys, or null if not found.
     *
     * @return string|string[]|null
     */
    public static function get(string $key): string|array|null
    {
        try {
            $translation = self::getInstance();
            $translation->checkInit();
            $translation->checkLanguageLoaded();

            // Check if the key uses the plugin namespace format: plugin.PluginName.messageKey
            if (str_starts_with($key, 'plugin.')) {
                $parts = explode(separator: '.', string: $key, limit: 3);

                if (count($parts) === 3) {
                    [$namespace, $pluginName, $messageKey] = $parts;

                    // Try the current language first
                    $currentTranslation =
                        $translation->pluginTranslations[$pluginName][$translation->currentLanguage][$messageKey]
                        ?? null;
                    if ($currentTranslation !== null) {
                        return $currentTranslation;
                    }

                    return (
                        $translation->pluginTranslations[$pluginName][$translation->defaultLanguage][$messageKey]
                        ?? null
                    );
                }
            }

            $currentLanguageTranslations = $translation->loadedLanguages[$translation->currentLanguage];
            if (array_key_exists($key, $currentLanguageTranslations) && $currentLanguageTranslations[$key] !== '') {
                return $currentLanguageTranslations[$key];
            }

            return $translation->loadedLanguages[$translation->defaultLanguage][$key] ?? null;
        } catch (Exception) {
            Configuration::getConfigurationInstance()
                ->getLogger()
                ->error('Error while fetching translation key: ' . $key);
        }

        return null;
    }

    /**
     * Returns the translation of a specific key as a string.
     * Use this for keys that are known to always return a string (not plural form keys).
     * Returns an empty string if the key is not found or returns an array.
     */
    public static function getString(string $key): string
    {
        $value = self::get($key);

        return is_string($value) ? $value : '';
    }

    /**
     * Checks if a specific translation key exists in the current or default language.
     */
    public static function has(string $key): bool
    {
        try {
            $translation = self::getInstance();
            $translation->checkInit();
            $translation->checkLanguageLoaded();

            // Check plugin namespace
            if (str_starts_with($key, 'plugin.')) {
                $parts = explode(separator: '.', string: $key, limit: 3);

                if (count($parts) === 3) {
                    [$namespace, $pluginName, $messageKey] = $parts;
                    $currentLanguagePluginTranslations = $translation->pluginTranslations[$pluginName][$translation->currentLanguage]
                    ?? [];
                    if (array_key_exists($messageKey, $currentLanguagePluginTranslations)) {
                        return true;
                    }

                    $defaultLanguagePluginTranslations = $translation->pluginTranslations[$pluginName][$translation->defaultLanguage]
                    ?? [];
                    return array_key_exists($messageKey, $defaultLanguagePluginTranslations);
                }
            }

            // Original core logic
            $currentLanguageTranslations = $translation->loadedLanguages[$translation->currentLanguage];
            if (array_key_exists($key, $currentLanguageTranslations)) {
                return true;
            }

            $defaultLanguageTranslations = $translation->loadedLanguages[$translation->defaultLanguage];
            if (array_key_exists($key, $defaultLanguageTranslations)) {
                return true;
            }
        } catch (Exception) {
            return false;
        }

        return false;
    }

    /**
     * Returns all translations from the current language.
     * @throws Exception
     * @return array<string, string|array<int, string>>
     */
    public static function getAll(): array
    {
        $translation = self::getInstance();
        $translation->checkInit();
        $translation->checkLanguageLoaded();

        return $translation->loadedLanguages[$translation->currentLanguage];
    }

    /**
     * @throws Exception
     */
    public function setTranslationsDir(string $translationsDir): Translation
    {
        $this->translationsDir = $translationsDir;
        $this->checkTranslationsDirectory();

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setDefaultLanguage(string $defaultLanguage): Translation
    {
        $this->defaultLanguage = $defaultLanguage;
        $this->checkDefaultLanguage();
        $this->checkDefaultLanguageLoaded();

        return $this;
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
        $this->checkInit();
        $this->currentLanguage = $currentLanguage;
        $this->checkLanguageLoaded();

        return $this;
    }

    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }

    /**
     * Returns the single instance.
     */
    public static function getInstance(): Translation
    {
        if (!self::$translation instanceof Translation) {
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

        // Load all language files from the plugin translations directory
        $languageFiles = glob($translationsDir . '/language_*.php');

        if ($languageFiles === false) {
            return; // Silently skip if the glob fails
        }

        foreach ($languageFiles as $languageFile) {
            // Extract language code from the filename: language_en.php -> en
            $matches = [];
            if (!preg_match('/language_([a-z]{2,3}(_[a-z]{2})?)\.php$/i', basename($languageFile), $matches)) {
                continue;
            }

            $langCode = strtolower($matches[1]);

            // Include the file and extract the $PMF_LANG array
            $PMF_LANG = [];
            include $languageFile;

            // Store in a namespaced structure
            if (!array_key_exists($pluginName, $this->pluginTranslations)) {
                $this->pluginTranslations[$pluginName] = [];
            }

            $this->pluginTranslations[$pluginName][$langCode] = $PMF_LANG;
        }
    }

    /**
     * Checks if the default language is already loaded.
     */
    protected function checkDefaultLanguageLoaded(): void
    {
        $this->ensureLanguageLoaded($this->defaultLanguage);
    }

    /**
     * Checks if the current language is already loaded. Loading new language only when needed.
     */
    protected function checkLanguageLoaded(): void
    {
        $this->ensureLanguageLoaded($this->currentLanguage);
    }

    /**
     * Ensures that the given language is loaded in the cache.
     */
    private function ensureLanguageLoaded(string $language): void
    {
        if (array_key_exists($language, $this->loadedLanguages) && $this->loadedLanguages[$language] !== []) {
            return;
        }

        $this->checkCurrentLanguage();
        $loaded = require $this->filename($language);
        $translations = [];
        if (is_array($loaded)) {
            foreach ($loaded as $translationKey => $translationValue) {
                if (is_string($translationValue)) {
                    $translations[(string) $translationKey] = $translationValue;
                    continue;
                }

                if (!is_array($translationValue)) {
                    continue;
                }

                $pluralForms = [];
                foreach ($translationValue as $pluralIndex => $pluralForm) {
                    $pluralForms[(int) $pluralIndex] = (string) $pluralForm;
                }

                $translations[(string) $translationKey] = $pluralForms;
            }
        }

        $this->loadedLanguages[$language] = $translations;
    }

    /**
     * Checks if the translations directory exists. If not, throw an exception.
     * @throws Exception
     */
    protected function checkTranslationsDirectory(): void
    {
        if (!is_dir($this->translationsDir)) {
            throw new Exception('The directory ' . $this->translationsDir . ' was not found!');
        }
    }

    /**
     * Checks if the default language exists. If not, throw an exception.
     * @throws Exception
     */
    protected function checkDefaultLanguage(): void
    {
        if (!file_exists($this->filename($this->defaultLanguage))) {
            throw new Exception('Default language "' . $this->defaultLanguage . '"not found!');
        }
    }

    /**
     * Checks if the Translation class has been initialized.
     * @throws Exception
     */
    protected function checkInit(): void
    {
        if (!$this->isReady) {
            $this->performInit();
        }
    }

    /**
     * Performs the actual initialization logic.
     *
     * @throws Exception
     */
    private function performInit(): void
    {
        $this->checkTranslationsDirectory();
        $this->checkDefaultLanguage();

        $this->isReady = true;
    }

    /**
     * Checks if locale for the current language exists. If not, start using the default language.
     */
    protected function checkCurrentLanguage(): void
    {
        if (!file_exists($this->filename($this->currentLanguage))) {
            $this->currentLanguage = $this->defaultLanguage;
        }
    }

    /**
     * Returns the filename for the given language.
     */
    protected function filename(string $language): string
    {
        return $this->translationsDir . DIRECTORY_SEPARATOR . 'language_' . strtolower($language) . '.php';
    }

    /**
     * Fetches the translation file for the current language.
     * @return array<string, array<string, string>>
     */
    private static function fetchTranslationFile(): array
    {
        $translation = self::getInstance();

        $LANG_CONF = [];
        include $translation->filename(language: 'en');
        include $translation->filename($translation->currentLanguage);

        return $LANG_CONF;
    }
}
