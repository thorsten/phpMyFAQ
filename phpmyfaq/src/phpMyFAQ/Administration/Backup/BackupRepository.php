<?php

/**
 * Backup Repository.
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
 * @since     2025-10-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration\Backup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

readonly class BackupRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function getNumberOfEntries(): int
    {
        $query = sprintf('SELECT id FROM %sfaqbackup', Database::getTablePrefix());

        return $this->configuration->getDb()->numRows($this->configuration->getDb()->query($query));
    }

    /**
     * @return array<int, object>
     */
    public function getAll(): array
    {
        $table = Database::getTablePrefix() . 'faqbackup';
        $query = sprintf('SELECT id, filename, authkey, authcode, created FROM %s ORDER BY id DESC', $table);

        $result = $this->configuration->getDb()->query($query);
        $data = $this->configuration->getDb()->fetchAll($result);

        return is_array($data) ? $data : [];
    }

    public function findByFilename(string $filename): ?object
    {
        if ($filename === '') {
            return null;
        }

        $table = Database::getTablePrefix() . 'faqbackup';
        $filenameEscaped = $this->configuration->getDb()->escape($filename);
        $query = sprintf(
            "SELECT id, filename, authkey, authcode, created FROM %s WHERE filename = '%s'",
            $table,
            $filenameEscaped,
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            return $this->configuration->getDb()->fetchObject($result) ?: null;
        }

        return null;
    }

    public function add(string $filename, string $authKeyHex, string $authCodeHex, string $created): bool
    {
        if ($filename === '' || $authKeyHex === '' || $authCodeHex === '' || $created === '') {
            return false;
        }

        $table = Database::getTablePrefix() . 'faqbackup';
        $id = $this->configuration->getDb()->nextId(table: $table, column: 'id');

        $filenameEscaped = $this->configuration->getDb()->escape($filename);
        $authKeyEscaped = $this->configuration->getDb()->escape($authKeyHex);
        $authCodeEscaped = $this->configuration->getDb()->escape($authCodeHex);
        $createdEscaped = $this->configuration->getDb()->escape($created);

        $query = sprintf(
            "INSERT INTO %s (id, filename, authkey, authcode, created) VALUES (%d, '%s', '%s', '%s', '%s')",
            $table,
            $id,
            $filenameEscaped,
            $authKeyEscaped,
            $authCodeEscaped,
            $createdEscaped,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function deleteById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $table = Database::getTablePrefix() . 'faqbackup';
        $query = sprintf('DELETE FROM %s WHERE id = %d', $table, $id);

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function deleteByFilename(string $filename): bool
    {
        if ($filename === '') {
            return false;
        }

        $table = Database::getTablePrefix() . 'faqbackup';
        $filenameEscaped = $this->configuration->getDb()->escape($filename);
        $query = sprintf("DELETE FROM %s WHERE filename = '%s'", $table, $filenameEscaped);

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function deleteAll(): bool
    {
        $table = Database::getTablePrefix() . 'faqbackup';
        $query = sprintf('DELETE FROM %s', $table);

        return (bool) $this->configuration->getDb()->query($query);
    }
}
