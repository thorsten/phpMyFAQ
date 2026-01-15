<?php

/**
 * The configuration repository class
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
 * @since     2025-11-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;
use phpMyFAQ\Database;

readonly class ConfigurationRepository
{
    public function __construct(
        private CoreConfiguration $coreConfiguration,
        private string $tableName = 'faqconfig',
    ) {
    }

    public function updateConfigValue(string $key, string $value): bool
    {
        $sql = <<<'SQL'
                UPDATE %s%s SET config_value = '%s' WHERE config_name = '%s'
            SQL;
        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->tableName,
            $this->coreConfiguration->getDb()->escape(trim($value)),
            $this->coreConfiguration->getDb()->escape(trim($key)),
        );

        return (bool) $this->coreConfiguration->getDb()->query($query);
    }

    /**
     * @return array<int, object>
     */
    public function fetchAll(): array
    {
        $sql = <<<'SQL'
                SELECT config_name, config_value FROM %s%s
            SQL;
        $query = sprintf($sql, Database::getTablePrefix(), $this->tableName);

        $result = $this->coreConfiguration->getDb()->query($query);
        $rows = $this->coreConfiguration->getDb()->fetchAll($result);
        return is_array($rows) ? $rows : [];
    }

    public function insert(string $name, string $value): bool
    {
        $sql = <<<'SQL'
                INSERT INTO %s%s (config_name, config_value) VALUES ('%s', '%s')
            SQL;
        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->tableName,
            $this->coreConfiguration->getDb()->escape(trim($name)),
            $this->coreConfiguration->getDb()->escape(trim($value)),
        );

        return (bool) $this->coreConfiguration->getDb()->query($query);
    }

    public function delete(string $name): bool
    {
        $sql = <<<'SQL'
                DELETE FROM %s%s WHERE config_name = '%s'
            SQL;
        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->tableName,
            $this->coreConfiguration->getDb()->escape(trim($name)),
        );

        return (bool) $this->coreConfiguration->getDb()->query($query);
    }

    public function renameKey(string $currentKey, string $newKey): bool
    {
        $sql = <<<'SQL'
                UPDATE %s%s SET config_name = '%s' WHERE config_name = '%s'
            SQL;
        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->tableName,
            $this->coreConfiguration->getDb()->escape(trim($newKey)),
            $this->coreConfiguration->getDb()->escape(trim($currentKey)),
        );

        return (bool) $this->coreConfiguration->getDb()->query($query);
    }

    /**
     * @return array<int, object>
     */
    public function getFaqDataContents(): array
    {
        $sql = <<<'SQL'
                SELECT content FROM %sfaqdata
            SQL;
        $query = sprintf($sql, Database::getTablePrefix());
        $response = $this->coreConfiguration->getDb()->query($query);
        $rows = $this->coreConfiguration->getDb()->fetchAll($response);
        return is_array($rows) ? $rows : [];
    }

    public function updateFaqDataContent(string $oldContent, string $newContent): bool
    {
        $sql = <<<'SQL'
                UPDATE %sfaqdata SET content='%s' WHERE content='%s'
            SQL;
        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->coreConfiguration->getDb()->escape($newContent),
            $this->coreConfiguration->getDb()->escape($oldContent),
        );
        return (bool) $this->coreConfiguration->getDb()->query($query);
    }
}
