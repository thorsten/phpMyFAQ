<?php
/**
 * The main User session class
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Session
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-31
 */

/**
 * PMF_Session
 *
 * @category  phpMyFAQ
 * @package   PMF_Session
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-31
 */
class PMF_Session
{
    /**
     * DB handle
     *
     * @var PMF_Db
     */
    private $db;

    /**
     * Language
     *
     * @var string
     */
    private $language;

    /**
     * Constructor
     *
     * @param   object  PMF_Db
     * @param   string  $language
     * @since   2007-03-31
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function __construct()
    {
        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Language::$language;
    }
    
    /**
     * Tracks the user and log what he did
     * 
     * @param   string
     * @param   integer
     * @return  void
     * @since   2001-02-18
     * @since   Bastian Poettner <bastian@poettner.net>
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@phpmyfaq.de>
     * @author  Marco Fester <webmaster@marcof.de>
     */
    public function userTracking($action, $id = 0)
    {
        global $sid, $user, $botBlacklist;

        $faqconfig = PMF_Configuration::getInstance();
        
        if ($faqconfig->get('main.enableUserTracking')) {

            $bots  = 0;
            $agent = $_SERVER['HTTP_USER_AGENT'];
            $sid   = PMF_Filter::filterInput(INPUT_GET, PMF_GET_KEY_NAME_SESSIONID, FILTER_VALIDATE_INT);
            $sidc  = PMF_Filter::filterInput(INPUT_COOKIE, PMF_COOKIE_NAME_SESSIONID, FILTER_VALIDATE_INT);
            
            if (!is_null($sidc)) {
                $sid = $sidc;
            }
            if ($action == "old_session") {
                $sid = null;
            }

            foreach ($botBlacklist as $bot) {
                if (strpos($agent, $bot)) {
                    $bots++;
                }
            }
            if (0 == $bots) {
                if (!isset($sid)) {
                    $sid = $this->db->nextID(SQLPREFIX."faqsessions", "sid");
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
                        $_SERVER["REMOTE_ADDR"],
                        $_SERVER['REQUEST_TIME']
                    );
                    $this->db->query($query);
                }

                $data = $sid.';' . 
                        str_replace(';', ',', $action) . ';' . 
                        $id . ';' . 
                        $_SERVER['REMOTE_ADDR'] . ';' . 
                        str_replace(';', ',', $_SERVER['QUERY_STRING']) . ';' . 
                        str_replace(';', ',', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . ';' . 
                        str_replace(';', ',', urldecode($_SERVER['HTTP_USER_AGENT'])) . ';' . 
                        $_SERVER['REQUEST_TIME'] . ";\n";
                $file = './data/tracking'.date('dmY');
                file_put_contents($file, $data, FILE_APPEND);
            }
        }
    }

    /**
     * Returns the timestamp of a session
     *
     * @param  integer $sid Session ID
     * @return integer
     * @since  2007-03-31
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
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

        $result = $this->db->query($query);

        if ($result) {
        	$res       = $this->db->fetch_object($result);
        	$timestamp = $res->time;
        }

        return $timestamp;
    }

    /**
     * Returns all session from a date
     *
     * @param  integer $timestamp Timestamp
     * @return array
     * @since  2007-03-31
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
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

        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $sessions[$row->sid] = array(
                'ip'   => $row->ip,
                'time' => $row->time);
        }

        return $sessions;
    }

    /**
     * Returns the number of sessions
     *
     * @return  integer
     * @since   2007-04-21
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
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

        $result = $this->db->query($query);
        if ($result) {
            $num = $this->db->numRows($result);
        }

        return $num;
    }

    /**
     * Deletes the sessions for a given timespan
     *
     * @param  integer $first Frist session ID
     * @param  integer $last  Last session ID
     * @return boolean
     * @since  2007-04-21
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
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

        $this->db->query($query);

        return true;
    }

    /**
     * Checks the Session ID
     *
     * @param  integer $sessionId Session ID
     * @param  string  $ip  IP
     * @return void
     * @since  2007-04-22
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
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
        $result = $this->db->query($query);

        if ($this->db->numRows($result) == 0) {
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
            $this->db->query($query);
        }
    }

    /**
     * Store the Session ID into a persistent cookie expiring PMF_SESSION_EXPIRED_TIME seconds after the page request.
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
            dirname(dirname(__FILE__)),
            $_SERVER['HTTP_HOST'],
            false);
    }
}
