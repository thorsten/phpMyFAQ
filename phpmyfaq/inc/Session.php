<?php
/**
 * The main User session class
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Session
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-31
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Session
 *
 * @category  phpMyFAQ
 * @package   PMF_Session
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-31
 */
class PMF_Session
{
    /**
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * Constructor
     *
     * @param PMF_Configuration
     *
     * @return PMF_Session
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }
    
    /**
     * Tracks the user and log what he did
     *
     * @param  string  $action Action string
     * @param  integer $id     Current ID
     *
     * @return void
     */
    public function userTracking($action, $id = 0)
    {
        global $sid, $user, $botBlacklist;

        if ($this->_config->get('main.enableUserTracking')) {

            $bots   = 0;
            $banned = false;
            $agent  = $_SERVER['HTTP_USER_AGENT'];
            $sid    = PMF_Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
            $sidc   = PMF_Filter::filterInput(INPUT_COOKIE, PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);
            
            if (!is_null($sidc)) {
                $sid = $sidc;
            }
            if ($action == 'old_session') {
                $sid = null;
            }

            foreach ($botBlacklist as $bot) {
                if ((bool)PMF_String::strstr($agent, $bot)) {
                    $bots++;
                }
            }

            $network = new PMF_Network($this->_config);

            if (!$network->checkIp($_SERVER['REMOTE_ADDR'])) {
                $banned = true;
            }

            if (0 == $bots && false == $banned) {
                if (!isset($sid)) {
                    $sid = $this->_config->getDb()->nextId(SQLPREFIX . 'faqsessions', 'sid');
                    // Sanity check: force the session cookie to contains the current $sid
                    if (!is_null($sidc) && (!$sidc != $sid)) {
                        self::setCookie($sid);
                    }

                    $query = sprintf("
                        INSERT INTO 
                            %sfaqsessions
                        (sid, user_id, ip, time)
                            VALUES
                        (%d, %d, '%s', %d)",
                        SQLPREFIX,
                        $sid,
                        ($user ? $user->getUserId() : -1),
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['REQUEST_TIME']
                    );
                    $this->_config->getDb()->query($query);
                }

                $data = $sid.';' . 
                        str_replace(';', ',', $action) . ';' . 
                        $id . ';' . 
                        $_SERVER['REMOTE_ADDR'] . ';' . 
                        str_replace(';', ',', $_SERVER['QUERY_STRING']) . ';' . 
                        str_replace(';', ',', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . ';' . 
                        str_replace(';', ',', urldecode($_SERVER['HTTP_USER_AGENT'])) . ';' . 
                        $_SERVER['REQUEST_TIME'] . ";\n";
                $file = './data/tracking' . date('dmY');
                file_put_contents($file, $data, FILE_APPEND);
            }
        }
    }

    /**
     * Returns the timestamp of a session
     *
     * @param  integer $sid Session ID
     *
     * @return integer
     */
    public function getTimeFromSessionId($sid)
    {
        $timestamp = 0;

        $query = sprintf("
            SELECT
                time
            FROM
                %sfaqsessions
            WHERE
                sid = %d",
            SQLPREFIX,
            $sid);

        $result = $this->_config->getDb()->query($query);

        if ($result) {
        	$res       = $this->_config->getDb()->fetchObject($result);
        	$timestamp = $res->time;
        }

        return $timestamp;
    }

    /**
     * Returns all session from a date
     *
     * @param integer $firstHour First hour
     * @param integer $lastHour  Last hour
     *
     * @return array
     */
    public function getSessionsbyDate($firstHour, $lastHour)
    {
        $sessions = array();

        $query = sprintf("
            SELECT
                sid, ip, time
            FROM
                %sfaqsessions
            WHERE
                time > %d
            AND
                time < %d
            ORDER BY
                time",
            SQLPREFIX,
            $firstHour,
            $lastHour);

        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $sessions[$row->sid] = array(
                'ip'   => $row->ip,
                'time' => $row->time);
        }

        return $sessions;
    }

    /**
     * Returns the number of sessions
     *
     * @return integer
     */
    public function getNumberOfSessions()
    {
        $num = 0;

        $query = sprintf("
            SELECT
                sid
            FROM
                %sfaqsessions",
            SQLPREFIX);

        $result = $this->_config->getDb()->query($query);
        if ($result) {
            $num = $this->_config->getDb()->numRows($result);
        }

        return $num;
    }

    /**
     * Deletes the sessions for a given timespan
     *
     * @param  integer $first Frist session ID
     * @param  integer $last  Last session ID
     *
     * @return boolean
     */
    public function deleteSessions($first, $last)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqsessions
            WHERE
                time >= %d
            AND
                time <= %d",
            SQLPREFIX,
            $first,
            $last);

        $this->_config->getDb()->query($query);

        return true;
    }

    /**
     * Checks the Session ID
     *
     * @param integer $sessionId Session ID
     * @param string  $ip  IP
     *
     * @return void
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
            SQLPREFIX,
            $sessionId,
            $ip,
            $_SERVER['REQUEST_TIME'] - 86400
        );
        $result = $this->_config->getDb()->query($query);

        if ($this->_config->getDb()->numRows($result) == 0) {
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
                SQLPREFIX,
                $_SERVER['REQUEST_TIME'],
                ($user ? $user->getUserId() : '-1'),
                $sessionId,
                $ip
            );
            $this->_config->getDb()->query($query);
        }
    }
    /**
     * Returns the number of anonymous users and registered ones.
     * These are the numbers of unique users who have perfomed
     * some activities within the last five minutes
     *
     * @param  integer $activityTimeWindow Optionally set the time window size in sec. 
     *                                     Default: 300sec, 5 minutes
     *
     * @return array
     */
    public function getUsersOnline($activityTimeWindow = 300)
    {
        $users = array(0, 0);
        
        if (PMF_Configuration::getInstance()->get('main.enableUserTracking')) {
            $timeNow = ($_SERVER['REQUEST_TIME'] - $activityTimeWindow);
            // Count all sids within the time window
            // TODO: add a new field in faqsessions in order to find out only sids of anonymous users
            $query = sprintf("
                SELECT
                    count(sid) AS anonymous_users
                FROM
                    %sfaqsessions
                WHERE
                    user_id = -1
                AND 
                    time > %d",
                SQLPREFIX,
                $timeNow);
            $result = $this->_config->getDb()->query($query);
            
            if (isset($result)) {
                $row      = $this->_config->getDb()->fetchObject($result);
                $users[0] = $row->anonymous_users;
            }
            
            // Count all faquser records within the time window
            $query = sprintf("
                SELECT
                    count(session_id) AS registered_users
                FROM
                    %sfaquser
                WHERE
                    session_timestamp > %d",
                SQLPREFIX,
                $timeNow);
            $result = $this->_config->getDb()->query($query);
            
            if (isset($result)) {
                $row      = $this->_config->getDb()->fetchObject($result);
                $users[1] = $row->registered_users;
            }
        }
        
        return $users;
    }

    /**
     * Store the Session ID into a persistent cookie expiring
     * PMF_SESSION_EXPIRED_TIME seconds after the page request.
     *
     * @param integer $sessionId Session ID
     *
     * @return void
     */
    public static function setCookie($sessionId)
    {
        setcookie(
            PMF_COOKIE_NAME_SESSIONID,
            $sessionId, 
            $_SERVER['REQUEST_TIME'] + PMF_SESSION_EXPIRED_TIME,
            dirname($_SERVER['SCRIPT_NAME'])
        );
    }
}
