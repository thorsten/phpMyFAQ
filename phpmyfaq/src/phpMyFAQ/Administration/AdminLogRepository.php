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
 * @copyright 2025 phpMyFAQ Team
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
            'SELECT id, time, usr AS user, text, ip FROM %sfaqadminlog ORDER BY id DESC',
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
                ->setIp($row->ip);
            $data[$row->id] = $adminLog;
        }

        return $data;
    }

    public function add(User $user, string $logText, Request $request): bool
    {
        $table = Database::getTablePrefix() . 'faqadminlog';
        $id = $this->configuration->getDb()->nextId($table, 'id');
        $time = (int) $request->server->get('REQUEST_TIME');
        $userId = $user->getUserId();
        $text = $this->configuration->getDb()->escape(nl2br($logText));
        $ip = $this->configuration->getDb()->escape((string) $request->getClientIp());

        $query = strtr("INSERT INTO table: (id, time, usr, text, ip) VALUES (id:, time:, userId:, 'text:', 'ip:')", [
            'table:' => $table,
            'id:' => (string) $id,
            'time:' => (string) $time,
            'userId:' => (string) $userId,
            'text:' => $text,
            'ip:' => $ip,
        ]);

        return (bool) $this->configuration->getDb()->query($query);
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
}
