<?php

/**
 * Session Repository.
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
 * @since     2025-12-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

readonly class SessionRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Counts distinct user IDs from sessions within a time window.
     */
    public function countOnlineUsersFromSessions(int $minTimestamp): int
    {
        $query = sprintf(
            'SELECT COUNT(DISTINCT user_id) AS cnt FROM %sfaqsessions WHERE time >= %d AND user_id > 0',
            Database::getTablePrefix(),
            $minTimestamp,
        );

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchObject($result);
            return isset($row->cnt) ? (int) $row->cnt : 0;
        }

        return 0;
    }

    /**
     * Counts users with active sessions from the faquser table.
     */
    public function countOnlineUsersFromFaqUser(int $minTimestamp): int
    {
        $query = sprintf(
            'SELECT COUNT(*) AS cnt FROM %sfaquser WHERE session_id IS NOT NULL AND session_timestamp >= %d AND success = 1',
            Database::getTablePrefix(),
            $minTimestamp,
        );

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchObject($result);
            return isset($row->cnt) ? (int) $row->cnt : 0;
        }

        return 0;
    }

    /**
     * Fetches the timestamp for a specific session ID.
     */
    public function getTimeBySessionId(int $sessionId): int
    {
        $query = sprintf('SELECT time FROM %sfaqsessions WHERE sid = %d', Database::getTablePrefix(), $sessionId);

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            $res = $this->configuration->getDb()->fetchObject($result);
            if ($res && isset($res->time)) {
                return (int) $res->time;
            }
        }

        return 0;
    }

    /**
     * Fetches all sessions within a time range.
     *
     * @return array<int, object>
     */
    public function getSessionsByDateRange(int $firstHour, int $lastHour): array
    {
        $query = sprintf(
            'SELECT sid, ip, time FROM %sfaqsessions WHERE time > %d AND time < %d ORDER BY time',
            Database::getTablePrefix(),
            $firstHour,
            $lastHour,
        );

        $result = $this->configuration->getDb()->query($query);
        $sessions = [];

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $sessions[] = $row;
        }

        return $sessions;
    }

    /**
     * Counts the total number of sessions.
     */
    public function countTotalSessions(): int
    {
        $query = sprintf('SELECT COUNT(sid) as num_sessions FROM %sfaqsessions', Database::getTablePrefix());

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchObject($result);
            return (int) $row->num_sessions;
        }

        return 0;
    }

    /**
     * Deletes sessions within a time range.
     */
    public function deleteSessionsByTimeRange(int $first, int $last): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqsessions WHERE time >= %d AND time <= %d',
            Database::getTablePrefix(),
            $first,
            $last,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes all sessions.
     */
    public function deleteAllSessions(): bool
    {
        $query = sprintf('DELETE FROM %sfaqsessions', Database::getTablePrefix());

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Fetches all session timestamps within a time range.
     *
     * @return array<int, int>
     */
    public function getSessionTimestamps(int $startDate, int $endDate): array
    {
        $query = sprintf(
            'SELECT time FROM %sfaqsessions WHERE time > %d AND time < %d;',
            Database::getTablePrefix(),
            $startDate,
            $endDate,
        );

        $result = $this->configuration->getDb()->query($query);
        $timestamps = [];

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $timestamps[] = (int) $row->time;
        }

        return $timestamps;
    }
}
