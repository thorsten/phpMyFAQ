<?php

/**
 * Category language service class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-10-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Category\Language;

use phpMyFAQ\Configuration;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Language\LanguageCodes;

final class CategoryLanguageService
{
    /**
     * Returns languages available to translate for a given category id.
     * Key = language code (lowercase), Value = display name.
     *
     * @return array<string, string>
     */
    public function getLanguagesToTranslate(Configuration $configuration, int $categoryId): array
    {
        $existing = $configuration->getLanguage()->isLanguageAvailable($categoryId, table: 'faqcategories');
        $existingLower = array_map(strtolower(...), $existing);

        $result = [];
        foreach (LanguageHelper::getAvailableLanguages() as $lang => $name) {
            $langLower = strtolower((string) $lang);
            if (!in_array($langLower, $existingLower, strict: true)) {
                $result[$langLower] = $name;
            }
        }

        return $result;
    }

    /**
     * Returns existing translation languages for a given category id.
     * Key = language code (lowercase), Value = display name.
     *
     * @return array<string, string>
     */
    public function getExistingTranslations(Configuration $configuration, int $categoryId): array
    {
        $existing = $configuration->getLanguage()->isLanguageAvailable($categoryId, table: 'faqcategories');
        $available = LanguageHelper::getAvailableLanguages();

        $result = [];
        foreach ($existing as $code) {
            $codeLower = strtolower($code);
            $result[$codeLower] = $available[$codeLower] ?? LanguageCodes::get($codeLower) ?? $codeLower;
        }

        ksort($result);
        return $result;
    }

    /**
     * Returns all languages currently used by categories (distinct langs in faqcategories).
     * Key = language code (lowercase), Value = display name.
     *
     * @return array<string, string>
     */
    public function getLanguagesInUse(Configuration $configuration): array
    {
        $all = $configuration->getLanguage()->isLanguageAvailable(identifier: 0, table: 'faqcategories');
        $result = [];
        foreach ($all as $code) {
            $codeLower = strtolower($code);
            $result[$codeLower] = LanguageCodes::get($codeLower) ?? $codeLower;
        }

        asort($result);
        return $result;
    }
}
