<?php

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
        $existing = $configuration->getLanguage()->isLanguageAvailable($categoryId, 'faqcategories');
        $existingLower = array_map(static fn($l) => strtolower((string) $l), $existing);

        $result = [];
        foreach (LanguageHelper::getAvailableLanguages() as $lang => $name) {
            $langLower = strtolower((string) $lang);
            if (!in_array($langLower, $existingLower, true)) {
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
        $existing = $configuration->getLanguage()->isLanguageAvailable($categoryId, 'faqcategories');
        $available = LanguageHelper::getAvailableLanguages();

        $result = [];
        foreach ($existing as $code) {
            $codeLower = strtolower((string) $code);
            // Prefer LanguageHelper names, fallback to LanguageCodes
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
        $all = $configuration->getLanguage()->isLanguageAvailable(0, 'faqcategories');
        $result = [];
        foreach ($all as $code) {
            $codeLower = strtolower((string) $code);
            $result[$codeLower] = LanguageCodes::get($codeLower) ?? $codeLower;
        }
        asort($result);
        return $result;
    }
}
