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
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Database;

class ConfigurationRepository
{
    private DatabaseConfigurationStore $databaseConfigurationStore;

    private HybridConfigurationStore $hybridConfigurationStore;

    public function __construct(
        private CoreConfiguration $coreConfiguration,
        private string $tableName = 'faqconfig',
    ) {
        $this->databaseConfigurationStore = new DatabaseConfigurationStore(
            $this->coreConfiguration->getDb(),
            $tableName,
        );
        $settingsResolver = new ConfigurationStorageSettingsResolver($this->databaseConfigurationStore);
        $this->hybridConfigurationStore = new HybridConfigurationStore(
            $this->databaseConfigurationStore,
            $settingsResolver,
            $this->coreConfiguration->getLogger(),
        );
    }

    public function updateConfigValue(string $key, string $value): bool
    {
        return $this->hybridConfigurationStore->updateConfigValue($key, $value);
    }

    /**
     * @return array<int, object>
     */
    public function fetchAll(): array
    {
        return $this->hybridConfigurationStore->fetchAll();
    }

    public function insert(string $name, string $value): bool
    {
        return $this->hybridConfigurationStore->insert($name, $value);
    }

    public function delete(string $name): bool
    {
        return $this->hybridConfigurationStore->delete($name);
    }

    public function renameKey(string $currentKey, string $newKey): bool
    {
        return $this->hybridConfigurationStore->renameKey($currentKey, $newKey);
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
