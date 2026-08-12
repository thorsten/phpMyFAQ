<?php

/**
 * Filters the available-languages list down to the languages a user may act on.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Language;

/**
 * Filters the available-languages map down to the languages a user may act
 * on, based on user- and group-level language restrictions. Null means
 * unrestricted.
 */
final class LanguageRestrictionFilter
{
    /**
     * @param array<string, string> $availableLanguages Language code => label map,
     *        as returned by LanguageHelper::getAvailableLanguages()
     * @param array<string>|null $allowedLanguageCodes Null = unrestricted
     * @return array<string, string>
     */
    public static function filter(array $availableLanguages, ?array $allowedLanguageCodes): array
    {
        if ($allowedLanguageCodes === null) {
            return $availableLanguages;
        }

        return array_filter(
            $availableLanguages,
            static fn(string $code): bool => in_array($code, $allowedLanguageCodes, strict: true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Returns the language codes to exclude from a language <select>, i.e.
     * the complement of the allowed set. Null allowed set means unrestricted,
     * so nothing is excluded.
     *
     * @param array<string, string> $availableLanguages Language code => label map
     * @param array<string>|null $allowedLanguageCodes Null = unrestricted
     * @return array<string>
     */
    public static function excludedLanguages(array $availableLanguages, ?array $allowedLanguageCodes): array
    {
        if ($allowedLanguageCodes === null) {
            return [];
        }

        return array_values(array_diff(array_keys($availableLanguages), $allowedLanguageCodes));
    }
}
