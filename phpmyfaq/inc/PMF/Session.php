<?php

/**
 * The main User session class.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-31
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Session.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-31
 */
class PMF_Session
{
    /**
     * Constants.
     */
    const PMF_COOKIE_NAME_REMEMBERME = 'pmf_rememberme';
    const PMF_COOKIE_NAME_AUTH = 'pmf_auth';
    const PMF_COOKIE_NAME_SESSIONID = 'pmf_sid';

    /**
     * @var PMF_Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param PMF_Configuration
     *
     * @return PMF_Session
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Tracks the user and log what he did.
     *
     * @param string $action Action string
     * @param int    $id     Current ID
     *
     * @throws PMF_Exception
     */
    public function userTracking($action, $id = 0)
    {
        global $sid, $user, $botBlacklist;

        if ($this->config->get('main.enableUserTracking')) {
            $bots = 0;
            $banned = false;
            $agent = $_SERVER['HTTP_USER_AGENT'];
            $sid = PMF_Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
            $sidc = PMF_Filter::filterInput(INPUT_COOKIE, self::PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);

            if (!is_null($sidc)) {
                $sid = $sidc;
            }
            if ($action == 'old_session') {
                $sid = null;
            }

            foreach ($botBlacklist as $bot) {
                if ((bool) PMF_String::strstr($agent, $bot)) {
                    ++$bots;
                }
            }

            $network = new PMF_Network($this->config);

            // if we're running behind a reverse proxy like nginx/varnish, fix the client IP
            $remoteAddr = $_SERVER['REMOTE_ADDR'];
            $localAddresses = ['127.0.0.1', '::1'];

            if (in_array($remoteAddr, $localAddresses) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $remoteAddr = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            // clean up as well
            $remoteAddr = preg_replace('([^0-9a-z:\.]+)i', '', $remoteAddr);

            if (!$network->checkIp($remoteAddr)) {
                $banned = true;
            }

            if (0 === $bots && false === $banned) {
                if (!isset($sid)) {
                    $sid = $this->config->getDb()->nextId(PMF_Db::getTablePrefix().'faqsessions', 'sid');
                    // Sanity check: force the session cookie to contains the current $sid
                    if (!is_null($sidc) && (!$sidc != $sid)) {
                        self::setCookie(self::PMF_COOKIE_NAME_SESSIONID, $sid);
                    }

                    $query = sprintf("
                        INSERT INTO 
                            %sfaqsessions
                        (sid, user_id, ip, time)
                            VALUES
                        (%d, %d, '%s', %d)",
                        PMF_Db::getTablePrefix(),
                        $sid,
                        ($user ? $user->getUserId() : -1),
                        $remoteAddr,
                        $_SERVER['REQUEST_TIME']
                    );
                    $this->config->getDb()->query($query);
                }

                $data = $sid.';'.
                        str_replace(';', ',', $action).';'.
                        $id.';'.
                        $remoteAddr.';'.
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
                    throw new PMF_Exception('Cannot write to '.$file);
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
    public function getTimeFromSessionId($sid)
    {
        $timestamp = 0;

        $query = sprintf('
            SELECT
                time
            FROM
                %sfaqsessions
            WHERE
                sid = %d',
            PMF_Db::getTablePrefix(),
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
    public function getSessionsbyDate($firstHour, $lastHour)
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
            PMF_Db::getTablePrefix(),
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
    public function getNumberOfSessions()
    {
        $num = 0;

        $query = sprintf('
            SELECT
                COUNT(sid) as num_sessions
            FROM
                %sfaqsessions',
            PMF_Db::getTablePrefix());

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
    public function deleteSessions($first, $last)
    {
        $query = sprintf('
            DELETE FROM
                %sfaqsessions
            WHERE
                time >= %d
            AND
                time <= %d',
            PMF_Db::getTablePrefix(),
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
        $query = sprintf('DELETE FROM %sfaqsessions', PMF_Db::getTablePrefix());

        return $this->config->getDb()->query($query);
    }

    /**
     * Checks the Session ID.
     *
     * @param int    $sessionId Session ID
     * @param string $ip        IP
     */
    public function checkSessionId($sessionId, $ip)
    {
        global $sid, $user;

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
            PMF_Db::getTablePrefix(),
            $sessionId,
            $ip,
            $_SERVER['REQUEST_TIME'] - 86400
        );
        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) == 0) {
            $this->userTracking('old_session', $sessionId);
        } else {
            // Update global session id
            $sid = $sessionId;
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
                PMF_Db::getTablePrefix(),
                $_SERVER['REQUEST_TIME'],
                ($user ? $user->getUserId() : '-1'),
                $sessionId,
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
    public function getUsersOnline($activityTimeWindow = 300)
    {
        $users = array(0, 0);

        if ($this->config->get('main.enableUserTracking')) {
            $timeNow = ($_SERVER['REQUEST_TIME'] - $activityTimeWindow);

            if (!$this->config->get('security.enableLoginOnly')) {
                // Count all sids within the time window for public installations
                // @todo add a new field in faqsessions in order to find out only sids of anonymous users
                $query = sprintf('
                    SELECT
                        count(sid) AS anonymous_users
                    FROM
                        %sfaqsessions
                    WHERE
                        user_id = -1
                    AND
                        time > %d',
                    PMF_Db::getTablePrefix(),
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
                PMF_Db::getTablePrefix(),
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
    public function getLast30DaysVisits()
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
            PMF_Db::getTablePrefix(),
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
     * @param string $name      Cookie name
     * @param string $sessionId Session ID
     * @param int    $timeout   Cookie timeout
     *
     * @return bool
     */
    public static function setCookie($name, $sessionId = '', $timeout = PMF_SESSION_EXPIRED_TIME)
    {
        return setcookie(
            $name,
            $sessionId,
            $_SERVER['REQUEST_TIME'] + $timeout,
            dirname($_SERVER['SCRIPT_NAME'])
        );
    }
}
