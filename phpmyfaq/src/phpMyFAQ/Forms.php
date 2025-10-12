<?php

declare(strict_types=1);

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

namespace phpMyFAQ;

use phpMyFAQ\Language\LanguageCodes;

class Forms
{
    private readonly Translation $translation;

    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->translation = new Translation();
    }

    /**
     * Get inputs of a given form.
     *
     * @param int $formId Form ID
     */
    public function getFormData(int $formId): array
    {
        $query = sprintf('SELECT form_id, input_id, input_type, input_label, input_active, input_required, input_lang
                    FROM %sfaqforms WHERE form_id = %d', Database::getTablePrefix(), $formId);

        $result = $this->configuration->getDb()->query($query);
        $formData = $this->configuration->getDb()->fetchAll($result);

        foreach ($formData as $input) {
            if ($input->input_lang === 'default') {
                $input->input_label = Translation::get($input->input_label);
            }
        }

        $filteredEntries = [];

        $formDataHasntMatchingLanguage = [];
        $idsAlreadyFiltered = [];

        foreach ($formData as $entry) {
            if ($entry->input_lang === $this->translation->getCurrentLanguage()) {
                $filteredEntries[] = $entry;
                $idsAlreadyFiltered[] = $entry->input_id;
            } else {
                $formDataHasntMatchingLanguage[] = $entry;
            }
        }

        foreach ($formDataHasntMatchingLanguage as $item) {
            if ($item->input_lang === 'default' && !in_array($item->input_id, $idsAlreadyFiltered)) {
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
        $query = sprintf(
            'UPDATE %sfaqforms SET input_active = %d WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $activated,
            $formId,
            $inputId,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Save the requirement status of a given input
     *
     * @param int $formId Form ID
     * @param int $inputId Input ID
     * @param int $activated Requirement status
     */
    public function saveRequiredInputStatus(int $formId, int $inputId, int $required): bool
    {
        $query = sprintf(
            'UPDATE %sfaqforms SET input_required = %d WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $required,
            $formId,
            $inputId,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Get translation strings of a given input
     */
    public function getTranslations(int $formId, int $inputId): array
    {
        $query = sprintf(
            'SELECT input_lang, input_label FROM %sfaqforms WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $formId,
            $inputId,
        );

        $result = $this->configuration->getDb()->query($query);
        $translations = $this->configuration->getDb()->fetchAll($result);

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
        $query = sprintf(
            "UPDATE %sfaqforms SET input_label='%s' WHERE form_id=%d AND input_id=%d AND input_lang='%s'",
            Database::getTablePrefix(),
            $label,
            $formId,
            $inputId,
            $lang,
        );

        return (bool) $this->configuration->getDb()->query($query);
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
        $query = sprintf(
            "DELETE FROM %sfaqforms WHERE form_id=%d AND input_id=%d AND input_lang='%s'",
            Database::getTablePrefix(),
            $formId,
            $inputId,
            $lang,
        );

        return (bool) $this->configuration->getDb()->query($query);
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
        $selectQuery = sprintf(
            "SELECT input_type, input_active, input_required FROM %sfaqforms WHERE input_id=%d AND form_id=%d
                    AND input_lang='default'",
            Database::getTablePrefix(),
            $inputId,
            $formId,
        );

        $response = $this->configuration->getDb()->query($selectQuery);
        $inputData = $this->configuration->getDb()->fetchObject($response);

        $requestQuery = sprintf(
            "INSERT INTO %sfaqforms(form_id, input_id, input_type, input_label, input_active, input_required, 
                    input_lang) VALUES (%d, %d, '%s', '%s', %d, %d, '%s')",
            Database::getTablePrefix(),
            $formId,
            $inputId,
            $this->configuration->getDb()->escape($inputData->input_type),
            $this->configuration->getDb()->escape($translation),
            $inputData->input_active,
            $inputData->input_required,
            LanguageCodes::getKey($lang),
        );

        return (bool) $this->configuration->getDb()->query($requestQuery);
    }

    /**
     * Check if a given input is required
     *
     * @param int $formId Form ID
     * @param int $inputId Input ID
     */
    public function checkIfRequired(int $formId, int $inputId): bool
    {
        $query = sprintf(
            'SELECT input_required, input_active FROM %sfaqforms WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $formId,
            $inputId,
        );

        $response = $this->configuration->getDb()->query($query);
        $data = $this->configuration->getDb()->fetchObject($response);

        return $data->input_active !== 0 && $data->input_required !== 0;
    }

    /**
     * Inserts a given input into the database (Only used in install-routine)
     *
     * @param array $input Input array
     */
    public function insertInputIntoDatabase(array $input): bool
    {
        $query = $this->getInsertQueries($input);

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function getInsertQueries(array $input): string
    {
        return sprintf(
            "INSERT INTO %sfaqforms(form_id, input_id, input_type, input_label, input_lang, input_active, 
                    input_required) VALUES (%d, %d, '%s', '%s', '%s', %d, %d)",
            Database::getTablePrefix(),
            (int) $input['form_id'],
            (int) $input['input_id'],
            $this->configuration->getDb()->escape($input['input_type']),
            $this->configuration->getDb()->escape($input['input_label']),
            $this->configuration->getDb()->escape($input['input_lang']),
            (int) $input['input_active'],
            (int) $input['input_required'],
        );
    }
}
