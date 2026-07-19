<?php

/**
 * Per-admin dashboard widget layout storage.
 *
 * Persists which dashboard widgets an admin sees and in which order, as a
 * JSON document in the faqadmindashboard table (one row per user).
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
 * @since     2026-05-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use JsonException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

readonly class DashboardLayout
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Returns the stored widget layout for a user, or an empty array when none exists.
     *
     * @return array<int, array{key: string, position: int, visible: bool}>
     */
    public function get(int $userId): array
    {
        $query = sprintf(
            'SELECT config FROM %sfaqadmindashboard WHERE user_id = %d',
            Database::getTablePrefix(),
            $userId,
        );

        $result = $this->configuration->getDb()->query($query);
        if ($result === false) {
            return [];
        }

        $row = $this->configuration->getDb()->fetchObject($result);
        if (!is_object($row) || !is_string($row->config ?? null) || $row->config === '') {
            return [];
        }

        try {
            $config = json_decode($row->config, associative: true, depth: 16, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (!is_array($config)) {
            return [];
        }

        $layout = [];
        foreach ($config as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $layout[] = [
                'key' => (string) ($entry['key'] ?? ''),
                'position' => (int) ($entry['position'] ?? 0),
                'visible' => (bool) ($entry['visible'] ?? true),
            ];
        }

        return $layout;
    }

    /**
     * Stores the widget layout for a user, replacing any previous layout.
     *
     * @param array<int, array{key: string, position: int, visible: bool}> $config
     * @throws JsonException
     */
    public function save(int $userId, array $config): bool
    {
        $database = $this->configuration->getDb();
        $prefix = Database::getTablePrefix();
        $encoded = $database->escape(json_encode($config, JSON_THROW_ON_ERROR));

        // Update an existing row in place rather than delete-then-insert: this avoids
        // a window where a failing insert would leave the user without any layout.
        if ($this->hasRow($userId)) {
            $update = sprintf(
                "UPDATE %sfaqadmindashboard SET config = '%s' WHERE user_id = %d",
                $prefix,
                $encoded,
                $userId,
            );

            return $database->query($update) !== false;
        }

        $insert = sprintf(
            "INSERT INTO %sfaqadmindashboard (user_id, config) VALUES (%d, '%s')",
            $prefix,
            $userId,
            $encoded,
        );

        return $database->query($insert) !== false;
    }

    /**
     * Returns whether a layout row already exists for the given user.
     */
    private function hasRow(int $userId): bool
    {
        $query = sprintf(
            'SELECT user_id FROM %sfaqadmindashboard WHERE user_id = %d',
            Database::getTablePrefix(),
            $userId,
        );

        $result = $this->configuration->getDb()->query($query);
        if ($result === false) {
            return false;
        }

        return is_object($this->configuration->getDb()->fetchObject($result));
    }

    /**
     * Removes the stored layout for a user, reverting them to the default layout.
     */
    public function reset(int $userId): bool
    {
        $query = sprintf('DELETE FROM %sfaqadmindashboard WHERE user_id = %d', Database::getTablePrefix(), $userId);

        return $this->configuration->getDb()->query($query) !== false;
    }
}
