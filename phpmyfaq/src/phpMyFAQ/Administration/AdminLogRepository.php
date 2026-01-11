<?php

/**
 * AdminLog Repository.
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

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\AdminLog as AdminLogEntity;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\Request;

readonly class AdminLogRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function getNumberOfEntries(): int
    {
        $query = sprintf('SELECT id FROM %sfaqadminlog', Database::getTablePrefix());

        return $this->configuration->getDb()->numRows($this->configuration->getDb()->query($query));
    }

    /**
     * @return array<int, AdminLogEntity>
     */
    public function getAll(): array
    {
        $data = [];

        $query = sprintf(
            'SELECT id, time, usr AS user, text, ip, hash, previous_hash FROM %sfaqadminlog ORDER BY id DESC',
            Database::getTablePrefix(),
        );

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $adminLog = new AdminLogEntity();
            $adminLog
                ->setId((int) $row->id)
                ->setTime((int) $row->time)
                ->setUserId((int) $row->user)
                ->setText($row->text)
                ->setIp($row->ip)
                ->setHash($row->hash ?? null)
                ->setPreviousHash($row->previous_hash ?? null);
            $data[$row->id] = $adminLog;
        }

        return $data;
    }

    /**
     * Adds a new logging entry with hash chain integrity.
     *
     * @param User    $user         User object
     * @param string  $logText      Logged string
     * @param Request $request      Request object
     * @param string|null $previousHash Hash of the previous entry
     */
    public function add(User $user, string $logText, Request $request, ?string $previousHash = null): bool
    {
        $time = (int) $request->server->get('REQUEST_TIME', time());
        $userId = $user->getUserId();
        $ip = $request->getClientIp() ?? '';

        // Create a temporary entity to calculate hash
        $entity = new AdminLogEntity();
        $entity->setTime($time);
        $entity->setUserId($userId);
        $entity->setIp($ip);
        $entity->setText($logText);
        $entity->setPreviousHash($previousHash);

        // Calculate hash for this entry
        $hash = $entity->calculateHash();

        $insert = sprintf(
            'INSERT INTO %sfaqadminlog (id, time, usr, ip, text, hash, previous_hash) '
            . "VALUES (%d, %d, %d, '%s', '%s', '%s', %s)",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqadminlog', 'id'),
            $time,
            $userId,
            $this->configuration->getDb()->escape($ip),
            $this->configuration->getDb()->escape($logText),
            $hash,
            $previousHash !== null ? "'" . $this->configuration->getDb()->escape($previousHash) . "'" : 'NULL',
        );

        return (bool) $this->configuration->getDb()->query($insert);
    }

    public function deleteOlderThan(int $timestamp): bool
    {
        $table = Database::getTablePrefix() . 'faqadminlog';
        $query = strtr('DELETE FROM table: WHERE time < ts:', [
            'table:' => $table,
            'ts:' => (string) $timestamp,
        ]);

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Returns the hash of the most recent log entry for chain linking.
     *
     * @return string|null Hash of the last entry or null if no entries exist
     */
    public function getLastHash(): ?string
    {
        $query = sprintf('SELECT hash FROM %sfaqadminlog ORDER BY id DESC LIMIT 1', Database::getTablePrefix());

        $result = $this->configuration->getDb()->query($query);

        if ($result && ($row = $this->configuration->getDb()->fetchObject($result))) {
            return $row->hash;
        }

        return null;
    }
}
