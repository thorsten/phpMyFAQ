<?php

/**
 * The main Session class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-31
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\User\CurrentUser;
use stdClass;

/**
 * Class Session
 *
 * @package phpMyFAQ
 */
class Session
{
    /** @var string Name of the remember me cookie */
    public const PMF_COOKIE_NAME_REMEMBERME = 'pmf_rememberme';

    /** @var string Name of the session cookie */
    public const PMF_COOKIE_NAME_SESSIONID = 'pmf_sid';

    /** @var Configuration */
    private $config;

    /** @var int */
    private $currentSessionId;

    /** @var CurrentUser*/
    private $currentUser;

    /** @var string[] List of bots we don't track */
    private $botIgnoreList = [
        'nustcrape',
        'webpost',
        'GoogleBot',
        'msnbot',
        'crawler',
        'scooter',
        'bravobrian',
        'archiver',
        'w3c',
        'control',
        'wget',
        'bot',
        'spider',
        'Yahoo! Slurp',
        'htdig',
        'gsa-crawler',
        'AirControl'
    ];

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the current session ID.
     * @return int|null
     */
    public function getCurrentSessionId(): ?int
    {
        return $this->currentSessionId;
    }

    /**
     * Sets the current session ID.
     * @param int $currentSessionId
     * @return Session
     */
    public function setCurrentSessionId(int $currentSessionId): Session
    {
        $this->currentSessionId = $currentSessionId;
        return $this;
    }

    /**
     * Sets current User object
     * @param CurrentUser $currentUser
     * @return Session
     */
    public function setCurrentUser(CurrentUser $currentUser): Session
    {
        $this->currentUser = $currentUser;
        return $this;
    }

    /**
     * Returns the timestamp of a session.
     *
     * @param int $sessionId Session ID
     *
     * @return int
     */
    public function getTimeFromSessionId(int $sessionId): int
    {
        $timestamp = 0;

        $query = sprintf('SELECT time FROM %sfaqsessions WHERE sid = %d', Database::getTablePrefix(), $sessionId);

        $result = $this->config->getDb()->query($query);

        if ($result) {
            $res = $this->config->getDb()->fetchObject($result);
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

        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchObject($result)) {
            $sessions[$row->sid] = [
                'ip' => $row->ip,
                'time' => $row->time,
            ];
        }

        return $sessions;
    }

    /**
     * Returns the number of sessions.
     *
     * @return int
     */
    public function getNumberOfSessions(): int
    {
        $num = 0;

        $query = sprintf('SELECT COUNT(sid) as num_sessions FROM %sfaqsessions', Database::getTablePrefix());

        $result = $this->config->getDb()->query($query);
        if ($result) {
            $row = $this->config->getDb()->fetchObject($result);
            $num = $row->num_sessions;
        }

        return $num;
    }

    /**
     * Deletes the sessions for a given timespan.
     *
     * @param int $first First session ID
     * @param int $last Last session ID
     *
     * @return bool
     */
    public function deleteSessions(int $first, int $last): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqsessions WHERE time >= %d AND time <= %d',
            Database::getTablePrefix(),
            $first,
            $last
        );

        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Deletes all entries in the table.
     *
     * @return mixed
     */
    public function deleteAllSessions()
    {
        $query = sprintf('DELETE FROM %sfaqsessions', Database::getTablePrefix());

        return $this->config->getDb()->query($query);
    }

    /**
     * Checks the Session ID.
     *
     * @param int $sessionIdToCheck Session ID
     * @param string $ip IP
     * @throws Exception
     */
    public function checkSessionId(int $sessionIdToCheck, string $ip): void
    {
        $query = sprintf(
            "SELECT sid FROM %sfaqsessions WHERE sid = %d AND ip = '%s' AND time > %d",
            Database::getTablePrefix(),
            $sessionIdToCheck,
            $ip,
            $_SERVER['REQUEST_TIME'] - 86400
        );
        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) == 0) {
            $this->userTracking('old_session', $sessionIdToCheck);
        } else {
            // Update global session id
            $this->setCurrentSessionId($sessionIdToCheck);
            // Update db tracking
            $query = sprintf(
                "UPDATE %sfaqsessions SET time = %d, user_id = %d WHERE sid = %d AND ip = '%s'",
                Database::getTablePrefix(),
                $_SERVER['REQUEST_TIME'],
                $this->currentUser->getUserId(),
                $sessionIdToCheck,
                $ip
            );
            $this->config->getDb()->query($query);
        }
    }

    /**
     * Tracks the user and log what he did.
     *
     * @param string $action Action string
     * @param string|int|null $data
     *
     * @throws Exception
     */
    public function userTracking(string $action, $data = null): void
    {
        if ($this->config->get('main.enableUserTracking')) {
            $bots = 0;
            $banned = false;
            $agent = $_SERVER['HTTP_USER_AGENT'];
            $this->currentSessionId = Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
            $cookieId = Filter::filterInput(INPUT_COOKIE, self::PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);

            if (!is_null($cookieId)) {
                $this->setCurrentSessionId($cookieId);
            }
            if ($action === 'old_session') {
                $this->setCurrentSessionId(0);
            }

            foreach ($this->botIgnoreList as $bot) {
                if ((bool)Strings::strstr($agent, $bot)) {
                    ++$bots;
                }
            }

            $network = new Network($this->config);

            // if we're running behind a reverse proxy like nginx/varnish, fix the client IP
            $remoteAddress = $_SERVER['REMOTE_ADDR'];
            $localAddresses = ['127.0.0.1', '::1'];

            if (in_array($remoteAddress, $localAddresses) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $remoteAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            // clean up as well
            $remoteAddress = preg_replace('([^0-9a-z:\.]+)i', '', $remoteAddress);

            // Anonymize IP address
            $remoteAddress = $network->anonymizeIp($remoteAddress);

            if (!$network->checkIp($remoteAddress)) {
                $banned = true;
            }

            if (0 === $bots && false === $banned) {
                if (!isset($this->currentSessionId)) {
                    $this->currentSessionId = $this->config->getDb()->nextId(
                        Database::getTablePrefix() . 'faqsessions',
                        'sid'
                    );
                    // Sanity check: force the session cookie to contains the current $sid
                    if (!is_null($cookieId) && (!$cookieId != $this->getCurrentSessionId())) {
                        self::setCookie(self::PMF_COOKIE_NAME_SESSIONID, $this->getCurrentSessionId());
                    }

                    $query = sprintf(
                        "INSERT INTO %sfaqsessions (sid, user_id, ip, time) VALUES (%d, %d, '%s', %d)",
                        Database::getTablePrefix(),
                        $this->getCurrentSessionId(),
                        $this->currentUser->getUserId(),
                        $remoteAddress,
                        $_SERVER['REQUEST_TIME']
                    );

                    $this->config->getDb()->query($query);
                }

                $data = $this->getCurrentSessionId() . ';' .
                    str_replace(';', ',', $action) . ';' .
                    $data . ';' .
                    $remoteAddress . ';' .
                    str_replace(';', ',', isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '') . ';' .
                    str_replace(';', ',', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . ';' .
                    str_replace(';', ',', urldecode($_SERVER['HTTP_USER_AGENT'])) . ';' .
                    $_SERVER['REQUEST_TIME'] . ";\n";

                $file = PMF_ROOT_DIR . '/data/tracking' . date('dmY');

                if (!is_file($file)) {
                    touch($file);
                }

                if (is_writeable($file)) {
                    file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
                } else {
                    throw new Exception('Cannot write to ' . $file);
                }
            }
        }
    }

    /**
     * Store the Session ID into a persistent cookie expiring
     * 3600 seconds after the page request.
     *
     * @param string   $name Cookie name
     * @param int|string|null $sessionId Session ID
     * @param int      $timeout Cookie timeout
     * @return bool
     */
    public function setCookie(string $name, $sessionId, int $timeout = 3600): bool
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
            $protocol = 'https';
        }

        if (PHP_VERSION_ID < 70300) {
            return setcookie(
                $name,
                $sessionId,
                $_SERVER['REQUEST_TIME'] + $timeout,
                dirname($_SERVER['SCRIPT_NAME']) . '; samesite=strict',
                parse_url($this->config->getDefaultUrl(), PHP_URL_HOST),
                'https' === $protocol,
                true
            );
        } else {
            return setcookie(
                $name,
                $sessionId,
                [
                    'expires' => $_SERVER['REQUEST_TIME'] + $timeout,
                    'path' => dirname($_SERVER['SCRIPT_NAME']),
                    'domain' => parse_url($this->config->getDefaultUrl(), PHP_URL_HOST),
                    'samesite' => 'strict',
                    'secure' => 'https' === $protocol,
                    'httponly' => true,
                ]
            );
        }
    }

    /**
     * Returns the number of anonymous users and registered ones.
     * These are the numbers of unique users who have performed
     * some activities within the last five minutes.
     *
     * @param int $activityTimeWindow Optionally set the time window size in sec.
     *                                Default: 300sec, 5 minutes
     *
     * @return array<int>
     */
    public function getUsersOnline(int $activityTimeWindow = 300): array
    {
        $users = [0, 0];

        if ($this->config->get('main.enableUserTracking')) {
            $timeNow = ($_SERVER['REQUEST_TIME'] - $activityTimeWindow);

            if (!$this->config->get('security.enableLoginOnly')) {
                // Count all sids within the time window for public installations
                $query = sprintf(
                    'SELECT count(sid) AS anonymous_users FROM %sfaqsessions WHERE user_id = -1 AND time > %d',
                    Database::getTablePrefix(),
                    $timeNow
                );

                $result = $this->config->getDb()->query($query);

                if (isset($result)) {
                    $row = $this->config->getDb()->fetchObject($result);
                    $users[0] = $row->anonymous_users;
                }
            }

            // Count all faq user records within the time window
            $query = sprintf(
                'SELECT count(session_id) AS registered_users FROM %sfaquser WHERE session_timestamp > %d',
                Database::getTablePrefix(),
                $timeNow
            );

            $result = $this->config->getDb()->query($query);

            if (isset($result)) {
                $row = $this->config->getDb()->fetchObject($result);
                $users[1] = $row->registered_users;
            }
        }

        return $users;
    }

    /**
     * Calculates the number of visits per day the last 30 days.
     *
     * @return array<int, stdClass>
     */
    public function getLast30DaysVisits(): array
    {
        $stats = $visits = $completeData = [];

        $startDate = strtotime('-1 month');
        $endDate = $_SERVER['REQUEST_TIME'];

        $query = sprintf(
            'SELECT time FROM %sfaqsessions WHERE time > %d AND time < %d;',
            Database::getTablePrefix(),
            $startDate,
            $endDate
        );
        $result = $this->config->getDb()->query($query);

        while ($row = $this->config->getDb()->fetchObject($result)) {
            $visits[] = $row->time;
        }

        for ($date = $startDate; $date <= $endDate; $date += 86400) {
            $stats[date('Y-m-d', $date)] = 0;
        }

        foreach ($visits as $visitDate) {
            isset($stats[date('Y-m-d', $visitDate)]) ? $stats[date('Y-m-d', $visitDate)]++ : null;
        }

        foreach ($stats as $date => $stat) {
            $visit = new stdClass();
            $visit->date = $date;
            $visit->number = $stats[$date];
            $completeData[] = $visit;
        }

        return $completeData;
    }
}
