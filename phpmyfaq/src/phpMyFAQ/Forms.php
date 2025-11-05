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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-21
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Form\FormsRepository;
use phpMyFAQ\Form\FormsRepositoryInterface;
use phpMyFAQ\Language\LanguageCodes;

readonly class Forms
{
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

        foreach ($formData as $input) {
            if ($input->input_lang === 'default') {
                $input->input_label = Translation::get($input->input_label);
            }
        }

        $filteredEntries = [];

        $formDataNoMatchingLanguage = [];
        $idsAlreadyFiltered = [];

        foreach ($formData as $entry) {
            if ($entry->input_lang === $this->translation->getCurrentLanguage()) {
                $filteredEntries[] = $entry;
                $idsAlreadyFiltered[] = $entry->input_id;
            } else {
                $formDataNoMatchingLanguage[] = $entry;
            }
        }

        foreach ($formDataNoMatchingLanguage as $item) {
            if ($item->input_lang === 'default' && !in_array($item->input_id, $idsAlreadyFiltered, strict: true)) {
                $filteredEntries[] = $item;
            }
        }

        usort($filteredEntries, [$this, 'sortByInputId']);

        return $filteredEntries;
    }

    /**
     * Sort function for usort | Sorting form data by input-id
     */
    private function sortByInputId(object $first, object $second): int
    {
        return $first->input_id - $second->input_id;
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
     * Save the (de-)activation of a given input
     *
     * @param int $formId Form ID
     * @param int $inputId Input ID
     * @param int $activated Activation status
     */
    public function saveActivateInputStatus(int $formId, int $inputId, int $activated): bool
    {
        return $this->repository->updateInputActive($formId, $inputId, $activated);
    }

    /**
     * Save the requirement status of a given input
     *
     * @param int $formId Form ID
     * @param int $inputId Input ID
     * @param int $required
     * @return bool
     */
    public function saveRequiredInputStatus(int $formId, int $inputId, int $required): bool
    {
        return $this->repository->updateInputRequired($formId, $inputId, $required);
    }

    /**
     * Get translation strings of a given input
     */
    public function getTranslations(int $formId, int $inputId): array
    {
        $translations = $this->repository->fetchTranslationsByFormAndInput($formId, $inputId);

        foreach ($translations as $translation) {
            if ($translation->input_lang === 'default') {
                $translation->input_label = Translation::get($translation->input_label);
            }
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
}
