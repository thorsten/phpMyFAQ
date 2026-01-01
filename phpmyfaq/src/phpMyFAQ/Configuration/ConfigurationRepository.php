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
        private CoreConfiguration $configuration,
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
            $this->configuration->getDb()->escape(trim($value)),
            $this->configuration->getDb()->escape(trim($key)),
        );

        return (bool) $this->configuration->getDb()->query($query);
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

        $result = $this->configuration->getDb()->query($query);
        $rows = $this->configuration->getDb()->fetchAll($result);
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
            $this->configuration->getDb()->escape(trim($name)),
            $this->configuration->getDb()->escape(trim($value)),
        );

        return (bool) $this->configuration->getDb()->query($query);
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
            $this->configuration->getDb()->escape(trim($name)),
        );

        return (bool) $this->configuration->getDb()->query($query);
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
            $this->configuration->getDb()->escape(trim($newKey)),
            $this->configuration->getDb()->escape(trim($currentKey)),
        );

        return (bool) $this->configuration->getDb()->query($query);
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
        $response = $this->configuration->getDb()->query($query);
        $rows = $this->configuration->getDb()->fetchAll($response);
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
            $this->configuration->getDb()->escape($newContent),
            $this->configuration->getDb()->escape($oldContent),
        );
        return (bool) $this->configuration->getDb()->query($query);
    }
}
