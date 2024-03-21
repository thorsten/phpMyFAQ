<?php

namespace phpMyFAQ;

use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Translation;
use Symfony\Component\VarDumper\Cloner\Data;

class Forms
{
    private Configuration $config;

    private $translation;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->translation = new Translation();
    }

    public function getFormData(int $formid): array
    {
        $query = sprintf(
            "SELECT form_id, input_id, input_type, input_label, input_active, input_required, input_lang FROM %sfaqforms WHERE form_id = %d",
            Database::getTablePrefix(),
            $formid
        );

        $result = $this->config->getDb()->query($query);
        $formData = $this->config->getDb()->fetchAll($result);

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
            }
            else {
                $formDataHasntMatchingLanguage[] = $entry;
            }
        }

        foreach ($formDataHasntMatchingLanguage as $item) {
            if ($item->input_lang === 'default' && !in_array($item->input_id, $idsAlreadyFiltered)) {
                $filteredEntries[] = $item;
            }
        }

        usort($filteredEntries, array($this, 'sortByInputId'));

        return $filteredEntries;
    }

    private function sortByInputId($a, $b)
    {
        return $a->input_id - $b->input_id;
    }

    public function getTranslatedLanguages(int $formId, int $inputId): array
    {
        $translations = $this->getTranslations($formId, $inputId);
        $languages = [];

        foreach ($translations as $translation) {
            $languages[] = $translation->input_lang;
        }

        return $languages;
    }

    public function saveActivateInputStatus(int $formId, int $inputId, int $activated): bool
    {
        $query = sprintf(
            'UPDATE %sfaqforms SET input_active = %d WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $activated,
            $formId,
            $inputId
        );

        return (bool)$this->config->getDb()->query($query);
    }

    public function saveRequiredInputStatus(int $formId, int $inputId, int $required): bool
    {
        $query = sprintf(
            'UPDATE %sfaqforms SET input_required = %d WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $required,
            $formId,
            $inputId
        );

        return (bool)$this->config->getDb()->query($query);
    }

    public function getTranslations(int $formid, int $inputid): array
    {
        $query = sprintf(
            'SELECT input_lang, input_label FROM %sfaqforms WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $formid,
            $inputid
        );

        $result = $this->config->getDb()->query($query);
        $translations = $this->config->getDb()->fetchAll($result);

        foreach ($translations as $translation) {
            if ($translation->input_lang === 'default') {
                $translation->input_label = Translation::get($translation->input_label);
            }
        }

        return $translations;
    }

    public function editTranslation(string $label, int $formId, int $inputId, string $lang): bool
    {
        $query = sprintf(
            "UPDATE %sfaqforms SET input_label='%s' WHERE form_id=%d AND input_id=%d AND input_lang='%s'",
            Database::getTablePrefix(),
            $label,
            $formId,
            $inputId,
            $lang
        );

        return (bool) $this->config->getDb()->query($query);
    }

    public function deleteTranslation(int $formId, int $inputId, string $lang): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqforms WHERE form_id=%d AND input_id=%d AND input_lang='%s'",
            Database::getTablePrefix(),
            $formId,
            $inputId,
            $lang
        );

        return (bool) $this->config->getDb()->query($query);
    }

    public function addTranslation(int $formId, int $inputId, string $lang, string $translation): bool
    {
        $selectQuery = sprintf(
            "SELECT input_type, input_active, input_required FROM %sfaqforms WHERE input_id=%d AND form_id=%d AND input_lang='default'",
            Database::getTablePrefix(),
            $inputId,
            $formId
        );

        $response = $this->config->getDb()->query($selectQuery);
        $inputData = $this->config->getDb()->fetchObject($response);

        $requestQuery = sprintf(
            "INSERT INTO %sfaqforms(form_id, input_id, input_type, input_label, input_active, input_required, 
                    input_lang) VALUES (%d, %d, '%s', '%s', %d, %d, '%s')",
            Database::getTablePrefix(),
            $formId,
            $inputId,
            $inputData->input_type,
            $translation,
            $inputData->input_active,
            $inputData->input_required,
            LanguageCodes::getKey($lang)
        );

        return (bool) $this->config->getDb()->query($requestQuery);
    }

    public function checkIfRequired(int $formId, int $inputId): bool
    {
        $query = sprintf(
            'SELECT input_required, input_active FROM %sfaqforms WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $formId,
            $inputId
        );

        $response = $this->config->getDb()->query($query);
        $data = $this->config->getDb()->fetchObject($response);

        if ($data->input_active !== 0 && $data->input_required !== 0) {
            return true;
        } else {
            return false;
        }
    }
}
