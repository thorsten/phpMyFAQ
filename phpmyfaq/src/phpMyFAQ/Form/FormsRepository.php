<?php

/**
 * Forms repository class.
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
 * @since     2025-11-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Form;

use phpMyFAQ\Configuration as CoreConfiguration;
use phpMyFAQ\Database;

readonly class FormsRepository implements FormsRepositoryInterface
{
    public function __construct(
        private CoreConfiguration $configuration,
    ) {
    }

    /**
     * @return array<int, object>
     */
    public function fetchFormDataByFormId(int $formId): array
    {
        $sql = <<<SQL
                SELECT
                    form_id, input_id, input_type, input_label, input_active, input_required, input_lang
                FROM
                    %sfaqforms
                WHERE
                    form_id = %d
            SQL;

        $query = sprintf($sql, Database::getTablePrefix(), $formId);

        $result = $this->configuration->getDb()->query($query);
        $rows = $this->configuration->getDb()->fetchAll($result);
        return is_array($rows) ? $rows : [];
    }

    public function updateInputActive(int $formId, int $inputId, int $activated): bool
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

    public function updateInputRequired(int $formId, int $inputId, int $required): bool
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
     * @return array<int, object>
     */
    public function fetchTranslationsByFormAndInput(int $formId, int $inputId): array
    {
        $query = sprintf(
            'SELECT input_lang, input_label FROM %sfaqforms WHERE form_id = %d AND input_id = %d',
            Database::getTablePrefix(),
            $formId,
            $inputId,
        );

        $result = $this->configuration->getDb()->query($query);
        $rows = $this->configuration->getDb()->fetchAll($result);
        return is_array($rows) ? $rows : [];
    }

    public function updateTranslation(string $label, int $formId, int $inputId, string $lang): bool
    {
        $query = sprintf(
            "UPDATE %sfaqforms SET input_label='%s' WHERE form_id=%d AND input_id=%d AND input_lang='%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($label),
            $formId,
            $inputId,
            $this->configuration->getDb()->escape($lang),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function deleteTranslation(int $formId, int $inputId, string $lang): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqforms WHERE form_id=%d AND input_id=%d AND input_lang='%s'",
            Database::getTablePrefix(),
            $formId,
            $inputId,
            $this->configuration->getDb()->escape($lang),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function fetchDefaultInputData(int $formId, int $inputId): ?object
    {
        $query = sprintf(
            'SELECT input_type, input_active, input_required FROM %sfaqforms WHERE input_id=%d AND form_id=%d '
            . "AND input_lang='default'",
            Database::getTablePrefix(),
            $inputId,
            $formId,
        );

        $response = $this->configuration->getDb()->query($query);
        $obj = $this->configuration->getDb()->fetchObject($response);

        return $obj ?: null;
    }

    public function insertTranslationRow(
        int $formId,
        int $inputId,
        string $inputType,
        string $label,
        int $inputActive,
        int $inputRequired,
        string $langCode,
    ): bool {
        $query = sprintf(
            'INSERT INTO %sfaqforms(form_id, input_id, input_type, input_label, input_active, input_required, '
            . " input_lang) VALUES (%d, %d, '%s', '%s', %d, %d, '%s')",
            Database::getTablePrefix(),
            $formId,
            $inputId,
            $this->configuration->getDb()->escape($inputType),
            $this->configuration->getDb()->escape($label),
            $inputActive,
            $inputRequired,
            $this->configuration->getDb()->escape($langCode),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function insertInput(array $input): bool
    {
        $query = $this->buildInsertQuery($input);
        return (bool) $this->configuration->getDb()->query($query);
    }

    public function buildInsertQuery(array $input): string
    {
        return sprintf(
            'INSERT INTO %sfaqforms(form_id, input_id, input_type, input_label, input_lang, input_active, '
            . " input_required) VALUES (%d, %d, '%s', '%s', '%s', %d, %d)",
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
