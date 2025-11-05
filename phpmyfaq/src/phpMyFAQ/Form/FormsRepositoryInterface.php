<?php

/**
 * Interface for Forms repository.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Form;

interface FormsRepositoryInterface
{
    /** @return array<int, object> */
    public function fetchFormDataByFormId(int $formId): array;

    public function updateInputActive(int $formId, int $inputId, int $activated): bool;

    public function updateInputRequired(int $formId, int $inputId, int $required): bool;

    /** @return array<int, object> */
    public function fetchTranslationsByFormAndInput(int $formId, int $inputId): array;

    public function updateTranslation(string $label, int $formId, int $inputId, string $lang): bool;

    public function deleteTranslation(int $formId, int $inputId, string $lang): bool;

    public function fetchDefaultInputData(int $formId, int $inputId): ?object;

    public function insertTranslationRow(
        int $formId,
        int $inputId,
        string $inputType,
        string $label,
        int $inputActive,
        int $inputRequired,
        string $langCode,
    ): bool;

    public function insertInput(array $input): bool;

    public function buildInsertQuery(array $input): string;
}
