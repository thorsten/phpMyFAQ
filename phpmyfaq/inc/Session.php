<?php
/**
 * $Id$
 *
 * The main User seession class
 *
 * @package      phpMyFAQ
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since        2007-03-31
 * @copyright    2007-2008 phpMyFAQ Team
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
     * @var object PMF_Db
     */
    var $db;

    /**
     * Language
     *
     * @var string
     */
    var $language;

    /**
     * Constructor
     *
     * @param   object  PMF_Db
     * @param   string  $language
     * @since   2007-03-31
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function PMF_Session(&$db, $language)
    {
        $this->db       = &$db;
        $this->language = $language;
    }

    /**
     * Returns the timestamp of a session
     *
     * @param   integer $sid
     * @return  integer
     * @access  public
     * @since   2007-03-31
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getTimeFromSessionId($sid)
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
        	$res = $this->db->fetch_object($result);
        	$timestamp = $res->time;
        }

        return $timestamp;
    }

    /**
     * Returns all session from a date
     *
     * @param   integer $timestamp
     * @return  array
     * @access  public
     * @since   2007-03-31
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getSessionsbyDate($firstHour, $lastHour)
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
     * @access  public
     * @since   2007-04-21
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getNumberOfSessions()
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
     * @param   integer $first
     * @param   integer $last
     * @return  boolean
     * @access  public
     * @since   2007-04-21
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deleteSessions($first, $last)
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
     * @param   integer $sid
     * @param   string  $ip
     * @return  void
     * @access  public
     * @since   2007-04-22
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function checkSessionId($sid, $ip)
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