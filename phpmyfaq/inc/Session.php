<?php
/**
 * $Id$
 *
 * The main User seession class
 *
 * @package      phpMyFAQ
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since        2007-03-31
 * @copyright    (c) 2007 phpMyFAQ Team
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

}