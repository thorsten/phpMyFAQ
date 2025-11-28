<?php

/**
 * The Administration Session class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-10-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

readonly class Session
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Returns the number of currently online (logged-in) users within the last windowSeconds.
     */
    public function getNumberOfOnlineUsers(int $windowSeconds = 600): int
    {
        $count = 0;

        try {
            $minTimestamp = (int) Request::createFromGlobals()->server->get('REQUEST_TIME') - $windowSeconds;
            if ($this->configuration->get(item: 'main.enableUserTracking')) {
                $query = sprintf(
                    'SELECT COUNT(DISTINCT user_id) AS cnt FROM %sfaqsessions WHERE time >= %d AND user_id > 0',
                    Database::getTablePrefix(),
                    $minTimestamp - (PMF_AUTH_TIMEOUT * 60),
                );
            } else {
                $query = sprintf(
                    'SELECT COUNT(*) AS cnt FROM %sfaquser WHERE session_id IS NOT NULL AND session_timestamp >= %d AND success = 1',
                    Database::getTablePrefix(),
                    $minTimestamp,
                );
            }

            $result = $this->configuration->getDb()->query($query);
            if ($result) {
                $row = $this->configuration->getDb()->fetchObject($result);
                $count = isset($row->cnt) ? (int) $row->cnt : 0;
            }
        } catch (Throwable) {
            $count = 0;
        }

        return $count;
    }

    public function getTimeFromSessionId(int $sessionId): int
    {
        $timestamp = 0;

        $query = sprintf('SELECT time FROM %sfaqsessions WHERE sid = %d', Database::getTablePrefix(), $sessionId);

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            $res = $this->configuration->getDb()->fetchObject($result);
            $timestamp = (int) $res->time;
        }

        return $timestamp;
    }

    /**
     * Returns all sessions from a date.
     *
     * @param int $firstHour First hour
     * @param int $lastHour Last hour
     *
     * @return array<int, string[]>
     */
    public function getSessionsByDate(int $firstHour, int $lastHour): array
    {
        $sessions = [];

        $query = sprintf(
            'SELECT sid, ip, time FROM %sfaqsessions WHERE time > %d AND time < %d ORDER BY time',
            Database::getTablePrefix(),
            $firstHour,
            $lastHour,
        );

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $sessions[$row->sid] = [
                'ip' => $row->ip,
                'time' => $row->time,
            ];
        }

        return $sessions;
    }

    /**
     * Returns the number of sessions.
     */
    public function getNumberOfSessions(): int
    {
        $num = 0;

        $query = sprintf('SELECT COUNT(sid) as num_sessions FROM %sfaqsessions', Database::getTablePrefix());

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchObject($result);
            $num = (int) $row->num_sessions;
        }

        return $num;
    }

    /**
     * Deletes the sessions for a given timespan.
     *
     * @param int $first First session ID
     * @param int $last Last session ID
     */
    public function deleteSessions(int $first, int $last): bool
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
     * Deletes all entries in the table.
     */
    public function deleteAllSessions(): bool
    {
        $query = sprintf('DELETE FROM %sfaqsessions', Database::getTablePrefix());

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Calculates the number of visits per day the last 30 days.
     *
     * @return array<int, stdClass>
     */
    public function getLast30DaysVisits(int $endDate): array
    {
        $stats = [];
        $visits = [];
        $completeData = [];
        $startDate = strtotime(datetime: '-1 month');

        $query = sprintf(
            'SELECT time FROM %sfaqsessions WHERE time > %d AND time < %d;',
            Database::getTablePrefix(),
            $startDate,
            $endDate,
        );
        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $visits[] = $row->time;
        }

        for ($date = $startDate; $date <= $endDate; $date += 86400) {
            $stats[date(
                format: 'Y-m-d',
                timestamp: $date,
            )] = 0;
        }

        foreach ($visits as $visitDate) {
            if (
                !isset(
                    $stats[date(
                        format: 'Y-m-d',
                        timestamp: (int) $visitDate,
                    )],
                )
            ) {
                continue;
            }

            ++$stats[date(
                format: 'Y-m-d',
                timestamp: (int) $visitDate,
            )];
        }

        foreach (array_keys($stats) as $date) {
            $visit = new stdClass();
            $visit->date = $date;
            $visit->number = $stats[$date];
            $completeData[] = $visit;
        }

        return $completeData;
    }
}
