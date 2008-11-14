<?php
/**
 * The main User seession class
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2007-03-31
 * @copyright 2007-2008 phpMyFAQ Team
 * @version   CVS: $Id$
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
    function __construct(&$db, $language)
    {
        $this->db       = &$db;
        $this->language = $language;
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
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @author  Marco Fester <webmaster@marcof.de>
     */
    public function userTracking($action, $id = 0)
    {
        global $PMF_CONF, $sid, $user, $botBlacklist;
    
        if (isset($PMF_CONF['main.enableUserTracking'])) {
    
            $bots   = 0;
            $agent  = $_SERVER['HTTP_USER_AGENT'];
    
            if (isset($_GET["sid"])) {
                $sid = (int)$_GET["sid"];
            }
    
            if (isset($_COOKIE['pmf_sid'])) {
                $sid = (int)$_COOKIE['pmf_sid'];
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
                    
                    if (isset($_COOKIE["pmf_sid"]) && ((int)$_COOKIE['pmf_sid'] != $sid)) {
                        setcookie('pmf_sid', $sid, $_SERVER['REQUEST_TIME'] + 3600);
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
                        $_SERVER['REQUEST_TIME']);
                    $this->db->query($query);
                }
                
                $data = $sid.';' . 
                        str_replace(';', ',', $action) . ';' . 
                        $id . ';' . 
                        $_SERVER['REMOTE_ADDR'] . ';' . 
                        str_replace(';', ',', $_SERVER['QUERY_STRING']) . ';' . 
                        str_replace(';', ',', $_SERVER['HTTP_REFERER']) . ';' . 
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
                'ip'    => $row->ip,
                'time'  => $row->time);
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
            $num = $this->db->num_rows($result);
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
     * @param  integer $sid Session ID
     * @param  string  $ip  IP
     * @return void
     * @since  2007-04-22
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function checkSessionId($sid, $ip)
    {
        global $user;

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
            $sid,
            $ip,
            $_SERVER['REQUEST_TIME'] - 86400);

        $result = $this->db->query($query);

        if ($this->db->num_rows($result) == 0) {
            Tracking('old_session', $sid);
        } else {
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
                $sid,
                $ip);
            $this->db->query($query);
        }
    }
}