<?php

/**
 * The forms helper class to reduce complexity in Forms.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Form;

use phpMyFAQ\Translation;

final class FormsHelper
{
    public function filterAndSortFormData(array $formData, Translation $translation): array
    {
        foreach ($formData as $input) {
            if ($input->input_lang !== 'default') {
                continue;
            }

            $input->input_label = Translation::get($input->input_label);
        }

        $filteredEntries = [];
        $fallbackCandidates = [];
        $seenIds = [];

        foreach ($formData as $entry) {
            if ($entry->input_lang === $translation->getCurrentLanguage()) {
                $filteredEntries[] = $entry;
                $seenIds[] = $entry->input_id;
                continue;
            }

            $fallbackCandidates[] = $entry;
        }

        foreach ($fallbackCandidates as $fallbackCandidate) {
            if (
                !(
                    $fallbackCandidate->input_lang === 'default'
                    && !in_array($fallbackCandidate->input_id, $seenIds, strict: true)
                )
            ) {
                continue;
            }

            $filteredEntries[] = $fallbackCandidate;
        }

        usort($filteredEntries, static fn(object $a, object $b): int => $a->input_id <=> $b->input_id);
        return $filteredEntries;
    }
}
