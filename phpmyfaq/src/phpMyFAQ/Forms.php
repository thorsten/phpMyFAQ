<?php

/**
 * The main forms class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-21
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Form\FormsHelper;
use phpMyFAQ\Form\FormsRepository;
use phpMyFAQ\Form\FormsRepositoryInterface;
use phpMyFAQ\Language\LanguageCodes;

readonly class Forms
{
    /* @mago-expect[too-many-methods]: Backward-compatible wrappers retained until the next major release. */
    private Translation $translation;
    private FormsRepositoryInterface $repository;

    public function __construct(
        private Configuration $configuration,
        ?FormsRepositoryInterface $repository = null,
    ) {
        $this->translation = new Translation();
        $this->repository = $repository ?? new FormsRepository($this->configuration);
    }

    /**
     * Get inputs of a given form.
     *
     * @param int $formId Form ID
     */
    public function getFormData(int $formId): array
    {
        $formData = $this->repository->fetchFormDataByFormId($formId);
        return new FormsHelper()->filterAndSortFormData($formData, $this->translation);
    }

    /**
     * Get languages that are already translated for a given input
     *
     * @param int $formId Form ID
     * @param int $inputId Input ID
     */
    public function getTranslatedLanguages(int $formId, int $inputId): array
    {
        $translations = $this->getTranslations($formId, $inputId);
        $languages = [];

        foreach ($translations as $translation) {
            $languages[] = $translation->input_lang;
        }

        return $languages;
    }

    /**
     * Update activation and required flags of a given input.
     */
    public function updateInputFlags(int $formId, int $inputId, ?int $activated = null, ?int $required = null): bool
    {
        $ok = true;
        if ($activated !== null) {
            $ok = $ok && $this->repository->updateInputActive($formId, $inputId, $activated);
        }
        if ($required !== null) {
            $ok = $ok && $this->repository->updateInputRequired($formId, $inputId, $required);
        }
        return $ok;
    }

    /**
     * Get translation strings of a given input
     */
    public function getTranslations(int $formId, int $inputId): array
    {
        $translations = $this->repository->fetchTranslationsByFormAndInput($formId, $inputId);

        foreach ($translations as $translation) {
            if ($translation->input_lang !== 'default') {
                continue;
            }

            $translation->input_label = Translation::get($translation->input_label);
        }

        return $translations;
    }

    /**
     * Edit a translation string
     *
     * @param string $label New translation string
     * @param int $formId Form ID
     * @param int $inputId Input ID
     * @param string $lang Edited language
     */
    public function editTranslation(string $label, int $formId, int $inputId, string $lang): bool
    {
        return $this->repository->updateTranslation($label, $formId, $inputId, $lang);
    }

    /**
     * Delete a translation string
     *
     * @param int $formId Form ID
     * @param int $inputId Input ID
     * @param string $lang Deleted language
     */
    public function deleteTranslation(int $formId, int $inputId, string $lang): bool
    {
        return $this->repository->deleteTranslation($formId, $inputId, $lang);
    }

    /**
     * Add a translation string
     *
     * @param int $formId Form ID
     * @param int $inputId Input ID
     * @param string $lang Added language
     * @param string $translation New translation string
     */
    public function addTranslation(int $formId, int $inputId, string $lang, string $translation): bool
    {
        $inputData = $this->repository->fetchDefaultInputData($formId, $inputId);
        if ($inputData === null) {
            return false;
        }

        return $this->repository->insertTranslationRow(
            $formId,
            $inputId,
            $inputData->input_type,
            $translation,
            (int) $inputData->input_active,
            (int) $inputData->input_required,
            LanguageCodes::getKey($lang),
        );
    }

    /**
     * Inserts a given input into the database (Only used in install-routine)
     *
     * @param array $input Input array
     */
    public function insertInputIntoDatabase(array $input): bool
    {
        return $this->repository->insertInput($input);
    }

    public function getInsertQueries(array $input): string
    {
        return $this->repository->buildInsertQuery($input);
    }

    /**
     * Save the (de-)activation of a given input (legacy wrapper).
     */
    public function saveActivateInputStatus(int $formId, int $inputId, int $activated): bool
    {
        return $this->updateInputFlags($formId, $inputId, activated: $activated);
    }

    /**
     * Save the requirement status of a given input (legacy wrapper).
     */
    public function saveRequiredInputStatus(int $formId, int $inputId, int $required): bool
    {
        return $this->updateInputFlags($formId, $inputId, required: $required);
    }
}
