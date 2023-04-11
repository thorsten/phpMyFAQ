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
 * @copyright 2019-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-24
 */

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
        string $id = 'language'
    ): string {
        $output = sprintf(
            '<select class="form-select" name="%s" id="%s" %s>',
            $id,
            $id,
            $submitOnChange ? ' onchange="this.form.submit();"' : ''
        );
        $languages = self::getAvailableLanguages();

        if (count($languages) > 0) {
            foreach ($languages as $lang => $value) {
                if (!in_array($lang, $excludedLanguages)) {
                    $output .= sprintf(
                        '<option value="%s" %s>%s</option>',
                        $lang,
                        $lang === $default ? 'selected' : '',
                        $value
                    );
                }
            }
        } else {
            $output .= sprintf('<option value="en">%s</option>', LanguageCodes::get('en'));
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
        $languages = $languageFiles = [];

        $dir = new DirectoryIterator(PMF_LANGUAGE_DIR);
        foreach ($dir as $fileInfo) {
            if (!$fileInfo->isDot()) {
                $languageFiles[] = strtoupper(
                    str_replace($search, '', trim($fileInfo->getFilename()))
                );
            }
        }

        foreach ($languageFiles as $lang) {
            // Check if the file is related to a (real) language before using it
            $isValidLanguage = LanguageCodes::get($lang);
            if ($isValidLanguage !== null) {
                $languages[strtolower($lang)] = LanguageCodes::get($lang);
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
        bool $fileLanguageValue = false
    ): string {
        $output = '';
        foreach (LanguageHelper::getAvailableLanguages() as $key => $value) {
            if ($onlyThisLang) {
                if (strtolower($key) == $lang) {
                    if ($fileLanguageValue) {
                        $output .= "\t<option value=\"language_" . strtolower($lang) . '.php"';
                    } else {
                        $output .= "\t<option value=\"" . strtolower($lang) . '"';
                    }
                    $output .= ' selected="selected"';
                    $output .= '>' . $value . "</option>\n";
                    break;
                }
            } else {
                if ($fileLanguageValue) {
                    $output .= "\t<option value=\"language_" . strtolower($key) . '.php"';
                } else {
                    $output .= "\t<option value=\"" . strtolower($key) . '"';
                }
                if (strtolower($key) == $lang) {
                    $output .= ' selected="selected"';
                }
                $output .= '>' . $value . "</option>\n";
            }
        }

        return $output;
    }
}
