<?php

/**
 * The Administration Translation class for managing translation statistics.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

readonly class TranslationStatistics
{
    private const string REFERENCE_LANGUAGE = 'en';

    /**
     * Returns statistics about all available translations.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getStatistics(): array
    {
        $statistics = [];
        $translationsDir = PMF_TRANSLATION_DIR;
        $referenceKeys = $this->getTranslationKeys(self::REFERENCE_LANGUAGE);
        $totalReferenceKeys = count($referenceKeys);

        $languageFiles = glob($translationsDir . '/language_*.php');

        if ($languageFiles === false) {
            return $statistics;
        }

        foreach ($languageFiles as $languageFile) {
            $language = $this->extractLanguageCode($languageFile);

            if ($language === null) {
                continue;
            }

            $translationKeys = $this->getTranslationKeys($language);
            $totalKeys = count($translationKeys);
            $missingKeys = array_diff($referenceKeys, $translationKeys);
            $missingCount = count($missingKeys);
            $translatedCount = $totalKeys;

            if ($language !== self::REFERENCE_LANGUAGE) {
                $translatedCount = $totalReferenceKeys - $missingCount;
            }

            $statistics[$language] = [
                'language_code' => $language,
                'total_keys' => $totalKeys,
                'translated_keys' => $translatedCount,
                'missing_keys' => $missingCount,
                'completion_percentage' => $totalReferenceKeys > 0
                    ? round(($translatedCount / $totalReferenceKeys) * 100, precision: 2)
                    : 0.0,
            ];
        }

        uasort($statistics, static fn($a, $b): int => $b['completion_percentage'] <=> $a['completion_percentage']);

        return $statistics;
    }

    /**
     * Returns detailed statistics for a specific language.
     *
     * @return array<string, mixed>|null
     */
    public function getLanguageStatistics(string $language): ?array
    {
        $statistics = $this->getStatistics();

        return $statistics[$language] ?? null;
    }

    /**
     * Returns the list of missing translation keys for a specific language.
     *
     * @return array<int, string>
     */
    public function getMissingKeys(string $language): array
    {
        if ($language === self::REFERENCE_LANGUAGE) {
            return [];
        }

        $referenceKeys = $this->getTranslationKeys(self::REFERENCE_LANGUAGE);
        $languageKeys = $this->getTranslationKeys($language);

        return array_values(array_diff($referenceKeys, $languageKeys));
    }

    /**
     * Returns all translation keys for a specific language.
     *
     * @return array<int, string>
     */
    private function getTranslationKeys(string $language): array
    {
        $translationsDir = PMF_TRANSLATION_DIR;
        $file = $translationsDir . '/language_' . $language . '.php';

        if (!file_exists($file)) {
            return [];
        }

        $PMF_LANG = [];
        include $file;

        return array_keys($PMF_LANG);
    }

    /**
     * Extracts the language code from a translation file path.
     */
    private function extractLanguageCode(string $filePath): ?string
    {
        if (preg_match(pattern: '/language_([a-z_]+)\.php$/', subject: $filePath, matches: $matches)) {
            return $matches[1];
        }

        return null;
    }
}
