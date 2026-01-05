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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-10-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

readonly class Session
{
    private SessionRepository $sessionRepository;

    public function __construct(
        private Configuration $configuration,
    ) {
        $this->sessionRepository = new SessionRepository($configuration);
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
                $count = $this->sessionRepository->countOnlineUsersFromSessions($minTimestamp
                - (PMF_AUTH_TIMEOUT * 60));
            } else {
                $count = $this->sessionRepository->countOnlineUsersFromFaqUser($minTimestamp);
            }
        } catch (Throwable) {
            $count = 0;
        }

        return $count;
    }

    public function getTimeFromSessionId(int $sessionId): int
    {
        return $this->sessionRepository->getTimeBySessionId($sessionId);
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
        $rows = $this->sessionRepository->getSessionsByDateRange($firstHour, $lastHour);

        foreach ($rows as $row) {
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
        return $this->sessionRepository->countTotalSessions();
    }

    /**
     * Deletes the sessions for a given timespan.
     *
     * @param int $first First session ID
     * @param int $last Last session ID
     */
    public function deleteSessions(int $first, int $last): bool
    {
        return $this->sessionRepository->deleteSessionsByTimeRange($first, $last);
    }

    /**
     * Deletes all entries in the table.
     */
    public function deleteAllSessions(): bool
    {
        return $this->sessionRepository->deleteAllSessions();
    }

    /**
     * Calculates the number of visits per day the last 30 days.
     *
     * @return array<int, stdClass>
     */
    public function getLast30DaysVisits(int $endDate): array
    {
        $stats = [];
        $completeData = [];
        $startDate = strtotime(datetime: '-1 month');

        $visits = $this->sessionRepository->getSessionTimestamps($startDate, $endDate);

        for ($date = $startDate; $date <= $endDate; $date += 86400) {
            $stats[date(format: 'Y-m-d', timestamp: $date)] = 0;
        }

        foreach ($visits as $visitDate) {
            if (!isset($stats[date(format: 'Y-m-d', timestamp: $visitDate)])) {
                continue;
            }

            ++$stats[date(format: 'Y-m-d', timestamp: $visitDate)];
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
