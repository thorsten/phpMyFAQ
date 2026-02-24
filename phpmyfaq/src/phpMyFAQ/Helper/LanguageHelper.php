<?php

/**
 * Language helper class for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-24
 */

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use DirectoryIterator;
use phpMyFAQ\Language\LanguageCodes;

/**
 * Class LanguageHelper
 *
 * @package phpMyFAQ\Helper
 */
class LanguageHelper
{
    /**
     * This function displays the <select> box for the available languages
     * optionally filtered by excluding some provided languages.
     *
     * @param  string[]  $excludedLanguages
     */
    public static function renderSelectLanguage(
        string $default,
        bool $submitOnChange = false,
        array $excludedLanguages = [],
        string $id = 'language',
    ): string {
        $output = sprintf(
            '<select class="form-select" name="%s" aria-label="%s" id="%s" %s>',
            $id,
            ucfirst($id),
            $id,
            $submitOnChange ? ' onchange="this.form.submit();"' : '',
        );
        $languages = self::getAvailableLanguages();

        if ($languages === []) {
            $output .= sprintf('<option value="en">%s</option>', LanguageCodes::get('en'));
            return $output . '</select>';
        }

        foreach ($languages as $lang => $value) {
            if (in_array($lang, $excludedLanguages, strict: true)) {
                continue;
            }

            $output .= sprintf(
                '<option value="%s" %s>%s</option>',
                $lang,
                $lang === $default ? 'selected' : '',
                $value,
            );
        }

        return $output . '</select>';
    }

    /**
     * This function returns the available languages.
     *
     * @return string[]
     */
    public static function getAvailableLanguages(): array
    {
        $search = ['language_', '.php'];
        $languages = [];
        $languageFiles = [];

        $dir = new DirectoryIterator(PMF_TRANSLATION_DIR);
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $languageFiles[] = strtoupper(str_replace(
                search: $search,
                replace: '',
                subject: trim($fileInfo->getFilename()),
            ));
        }

        foreach ($languageFiles as $languageFile) {
            // Check if the file is related to a (real) language before using it
            $isValidLanguage = LanguageCodes::get($languageFile);
            if ($isValidLanguage !== null) {
                $languages[strtolower($languageFile)] = LanguageCodes::get($languageFile);
            }
        }

        // Sort the languages list
        asort($languages);
        reset($languages);

        return $languages;
    }

    /**
     * Function for displaying all languages in <option>.
     *
     * @param string $lang              the language to be selected
     * @param bool   $onlyThisLang      print only the passed language?
     * @param bool   $fileLanguageValue print the <language file> instead of the <language code> as value?
     */
    public static function renderLanguageOptions(
        string $lang = '',
        bool $onlyThisLang = false,
        bool $fileLanguageValue = false,
    ): string {
        $output = '';
        foreach (LanguageHelper::getAvailableLanguages() as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if ($onlyThisLang && $normalizedKey !== $lang) {
                continue;
            }

            $languageKey = $normalizedKey;
            if ($onlyThisLang) {
                $languageKey = strtolower($lang);
            }

            $optionValue = $languageKey;
            if ($fileLanguageValue) {
                $optionValue = 'language_' . $languageKey . '.php';
            }

            $output .= "\t<option value=\"" . $optionValue . '"';

            if ($normalizedKey === $lang) {
                $output .= ' selected="selected"';
            }

            $output .= '>' . $value . "</option>\n";

            if ($onlyThisLang) {
                break;
            }
        }

        return $output;
    }
}
