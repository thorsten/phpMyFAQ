<?php

/**
 * Database-backed configuration storage
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage;

use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;

readonly class DatabaseConfigurationStore implements ConfigurationStoreInterface
{
    public function __construct(
        private DatabaseDriver $databaseDriver,
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
            $this->databaseDriver->escape(trim($value)),
            $this->databaseDriver->escape(trim($key)),
        );

        return (bool) $this->databaseDriver->query($query);
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

        $result = $this->databaseDriver->query($query);
        $rows = $this->databaseDriver->fetchAll($result);
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
            $this->databaseDriver->escape(trim($name)),
            $this->databaseDriver->escape(trim($value)),
        );

        return (bool) $this->databaseDriver->query($query);
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
            $this->databaseDriver->escape(trim($name)),
        );

        return (bool) $this->databaseDriver->query($query);
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
            $this->databaseDriver->escape(trim($newKey)),
            $this->databaseDriver->escape(trim($currentKey)),
        );

        return (bool) $this->databaseDriver->query($query);
    }

    public function fetchValue(string $name): ?string
    {
        $sql = <<<'SQL'
                SELECT config_value FROM %s%s WHERE config_name = '%s'
            SQL;
        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->tableName,
            $this->databaseDriver->escape(trim($name)),
        );

        $result = $this->databaseDriver->query($query);
        $row = $this->databaseDriver->fetchObject($result);

        return is_object($row) && property_exists($row, 'config_value') ? (string) $row->config_value : null;
    }

    /**
     * @param array<int, string> $names
     * @return array<string, ?string>
     */
    public function fetchValues(array $names): array
    {
        if ($names === []) {
            return [];
        }

        $trimmedNames = array_values(array_unique(array_map(static fn(string $name): string => trim($name), $names)));
        $quotedNames = array_map(
            fn(string $name): string => "'" . $this->databaseDriver->escape($name) . "'",
            $trimmedNames,
        );

        $sql = <<<'SQL'
                SELECT config_name, config_value FROM %s%s WHERE config_name IN (%s)
            SQL;
        $query = sprintf($sql, Database::getTablePrefix(), $this->tableName, implode(', ', $quotedNames));

        $result = $this->databaseDriver->query($query);
        $rows = $this->databaseDriver->fetchAll($result);

        $values = array_fill_keys($trimmedNames, null);
        if (!is_array($rows)) {
            return $values;
        }

        foreach ($rows as $row) {
            if (is_object($row) && property_exists($row, 'config_name') && property_exists($row, 'config_value')) {
                $values[(string) $row->config_name] = (string) $row->config_value;
            }
        }

        return $values;
    }
}
