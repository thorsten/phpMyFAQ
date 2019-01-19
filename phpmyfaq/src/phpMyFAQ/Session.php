<?php

namespace phpMyFAQ;

/**
 * The main Session class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2007-03-31
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Db;
use phpMyFAQ\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Network;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Class Session.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2007-03-31
 */
class Session
{
    /** Constants. */
    const PMF_COOKIE_NAME_REMEMBERME = 'pmf_rememberme';
    const PMF_COOKIE_NAME_AUTH = 'pmf_auth';
    const PMF_COOKIE_NAME_SESSIONID = 'pmf_sid';

    /** @var Configuration */
    private $config;

    /**
     * Constructor.
     *
     * @param Configuration
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Tracks the user and log what he did.
     *
     * @param string $action Action string
     * @param int    $id     Current ID
     *
     * @throws Exception
     */
    public function userTracking(string $action, int $id = 0)
    {
        global $sessionId, $user, $botBlacklist;

        if ($this->config->get('main.enableUserTracking')) {
            $bots = 0;
            $banned = false;
            $agent = $_SERVER['HTTP_USER_AGENT'];
            $sessionId = Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
            $cookieId = Filter::filterInput(INPUT_COOKIE, self::PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);

            if (!is_null($cookieId)) {
                $sessionId = $cookieId;
            }
            if ($action == 'old_session') {
                $sessionId = null;
            }

            foreach ($botBlacklist as $bot) {
                if ((bool) Strings::strstr($agent, $bot)) {
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

            if (!$network->checkIp($remoteAddress)) {
                $banned = true;
            }

            if (0 === $bots && false === $banned) {
                if (!isset($sessionId)) {
                    $sessionId = $this->config->getDb()->nextId(Db::getTablePrefix().'faqsessions', 'sid');
                    // Sanity check: force the session cookie to contains the current $sid
                    if (!is_null($cookieId) && (!$cookieId != $sessionId)) {
                        self::setCookie(self::PMF_COOKIE_NAME_SESSIONID, $sessionId);
                    }

                    $query = sprintf("
                        INSERT INTO 
                            %sfaqsessions
                        (sid, user_id, ip, time)
                            VALUES
                        (%d, %d, '%s', %d)",
                        Db::getTablePrefix(),
                        $sessionId,
                        ($user ? $user->getUserId() : -1),
                        $remoteAddress,
                        $_SERVER['REQUEST_TIME']
                    );
                    $this->config->getDb()->query($query);
                }

                $data = $sessionId.';'.
                        str_replace(';', ',', $action).';'.
                        $id.';'.
                        $remoteAddress.';'.
                        str_replace(';', ',', isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '').';'.
                        str_replace(';', ',', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '').';'.
                        str_replace(';', ',', urldecode($_SERVER['HTTP_USER_AGENT'])).';'.
                        $_SERVER['REQUEST_TIME'].";\n";
                $file = PMF_ROOT_DIR.'/data/tracking'.date('dmY');

                if (!is_file($file)) {
                    touch($file);
                }

                if (is_writeable($file)) {
                    file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
                } else {
                    throw new Exception('Cannot write to '.$file);
                }
            }
        }
    }

    /**
     * Returns the timestamp of a session.
     *
     * @param int $sid Session ID
     *
     * @return int
     */
    public function getTimeFromSessionId(int $sid)
    {
        $timestamp = 0;

        $query = sprintf('
            SELECT
                time
            FROM
                %sfaqsessions
            WHERE
                sid = %d',
            Db::getTablePrefix(),
            $sid);

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
     * @param int $lastHour  Last hour
     *
     * @return array
     */
    public function getSessionsByDate(int $firstHour, int $lastHour): array
    {
        $sessions = [];

        $query = sprintf('
            SELECT
                sid, ip, time
            FROM
                %sfaqsessions
            WHERE
                time > %d
            AND
                time < %d
            ORDER BY
                time',
            Db::getTablePrefix(),
            $firstHour,
            $lastHour
        );

        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchObject($result)) {
            $sessions[$row->sid] = array(
                'ip' => $row->ip,
                'time' => $row->time,
            );
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

        $query = sprintf('
            SELECT
                COUNT(sid) as num_sessions
            FROM
                %sfaqsessions',
            Db::getTablePrefix());

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
     * @param int $first Frist session ID
     * @param int $last  Last session ID
     *
     * @return bool
     */
    public function deleteSessions(int $first, int $last): bool
    {
        $query = sprintf('
            DELETE FROM
                %sfaqsessions
            WHERE
                time >= %d
            AND
                time <= %d',
            Db::getTablePrefix(),
            $first,
            $last);

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
        $query = sprintf('DELETE FROM %sfaqsessions', Db::getTablePrefix());

        return $this->config->getDb()->query($query);
    }

    /**
     * Checks the Session ID.
     *
     * @param int $sessionIdToCheck Session ID
     * @param string $ip IP
     * @throws
     */
    public function checkSessionId(int $sessionIdToCheck, string $ip)
    {
        global $sessionId, $user;

        $query = sprintf("
            SELECT
                sid
            FROM
                %sfaqsessions
            WHERE
                sid = %d
            AND
                ip = '%s'
            AND
                time > %d",
            Db::getTablePrefix(),
            $sessionIdToCheck,
            $ip,
            $_SERVER['REQUEST_TIME'] - 86400
        );
        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) == 0) {
            $this->userTracking('old_session', $sessionIdToCheck);
        } else {
            // Update global session id
            $sessionId = $sessionIdToCheck;
            // Update db tracking
            $query = sprintf("
                UPDATE
                    %sfaqsessions
                SET
                    time = %d,
                    user_id = %d
                WHERE
                    sid = %d
                    AND ip = '%s'",
                Db::getTablePrefix(),
                $_SERVER['REQUEST_TIME'],
                ($user ? $user->getUserId() : '-1'),
                $sessionIdToCheck,
                $ip
            );
            $this->config->getDb()->query($query);
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
     * @return array
     */
    public function getUsersOnline(int $activityTimeWindow = 300): array
    {
        $users = array(0, 0);

        if ($this->config->get('main.enableUserTracking')) {
            $timeNow = ($_SERVER['REQUEST_TIME'] - $activityTimeWindow);

            if (!$this->config->get('security.enableLoginOnly')) {
                // Count all sids within the time window for public installations
                $query = sprintf('
                    SELECT
                        count(sid) AS anonymous_users
                    FROM
                        %sfaqsessions
                    WHERE
                        user_id = -1
                    AND
                        time > %d',
                    Db::getTablePrefix(),
                    $timeNow
                );

                $result = $this->config->getDb()->query($query);

                if (isset($result)) {
                    $row = $this->config->getDb()->fetchObject($result);
                    $users[0] = $row->anonymous_users;
                }
            }

            // Count all faquser records within the time window
            $query = sprintf('
                SELECT
                    count(session_id) AS registered_users
                FROM
                    %sfaquser
                WHERE
                    session_timestamp > %d',
                Db::getTablePrefix(),
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
     * @returns array
     */
    public function getLast30DaysVisits(): array
    {
        $stats = $visits = [];

        $startDate = strtotime('-1 month');
        $endDate = $_SERVER['REQUEST_TIME'];

        $query = sprintf('
            SELECT
                time
            FROM
                %sfaqsessions
            WHERE
                time > %d
            AND
                time < %d;',
            Db::getTablePrefix(),
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

        return $stats;
    }

    /**
     * Store the Session ID into a persistent cookie expiring
     * PMF_SESSION_EXPIRED_TIME seconds after the page request.
     *
     * @param string $name Cookie name
     * @param string $sessionId Session ID
     * @param int $timeout Cookie timeout
     *
     * @return bool
     */
    public function setCookie(string $name, string $sessionId = '', int $timeout = PMF_SESSION_EXPIRED_TIME): bool
    {
        $protocol = 'http';
        if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
            $protocol = 'https';
        }
        return setcookie(
            $name,
            $sessionId,
            $_SERVER['REQUEST_TIME'] + $timeout,
            dirname($_SERVER['SCRIPT_NAME']),
            $this->config->getDefaultUrl(),
            ('https' === $protocol) ? true : false,
            true
        );
    }
}
