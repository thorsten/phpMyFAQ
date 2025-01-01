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

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

readonly class Session
{
    public function __construct(private Configuration $configuration)
    {
    }

    public function getTimeFromSessionId(int $sessionId): int
    {
        $timestamp = 0;

        $query = sprintf('SELECT time FROM %sfaqsessions WHERE sid = %d', Database::getTablePrefix(), $sessionId);

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            $res = $this->configuration->getDb()->fetchObject($result);
            $timestamp = $res->time;
        }

        return $timestamp;
    }


    /**
     * Returns all session from a date.
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
            $lastHour
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
            $num = $row->num_sessions;
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
            $last
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
        $startDate = strtotime('-1 month');

        $query = sprintf(
            'SELECT time FROM %sfaqsessions WHERE time > %d AND time < %d;',
            Database::getTablePrefix(),
            $startDate,
            $endDate
        );
        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $visits[] = $row->time;
        }

        for ($date = $startDate; $date <= $endDate; $date += 86400) {
            $stats[date('Y-m-d', $date)] = 0;
        }

        foreach ($visits as $visitDate) {
            if (isset($stats[date('Y-m-d', $visitDate)])) {
                ++$stats[date('Y-m-d', $visitDate)];
            }
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
