<?php
/**
 * The main Logging class
 *
 * PHP Version 5.2
 *
 *  http://www.mozilla.org/MPL/
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Logging
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-08-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Logging
 *
 * @category  phpMyFAQ
 * @package   PMF_Logging
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-08-15
 */
class PMF_Logging
{
    /**
     * DB handle
     *
     * @var PMF_Db
     */
    private $db = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->db = PMF_Db::getInstance();
    }

    /**
     * Returns the number of entries
     *
     * @return integer
     */
    public function getNumberOfEntries()
    {
        $query = sprintf('
            SELECT
                id
            FROM
                %sfaqadminlog',
            SQLPREFIX);

        return $this->db->numRows($this->db->query($query));
    }

    /**
     * Returns all data from the adminlog
     *
     * @return array
     */
    public function getAll()
    {
        $data = array();

        $query = sprintf('
            SELECT
                id, time, usr, text, ip
            FROM
                %sfaqadminlog
            ORDER BY id DESC',
            SQLPREFIX);

        $result = $this->db->query($query);
        while ($row = $this->db->fetchObject($result)) {
            $data[$row->id] = array(
               'time'  => $row->time,
               'usr'   => $row->usr,
               'text'  => $row->text,
               'ip'    => $row->ip);
        }

        return $data;
    }
    
    /**
     * Adds a new adminlog entry
     *
     * @param PMF_User $user    PMF_User object
     * @param string   $logText Logged string
     *
     * @return boolean
     */
    public function logAdmin(PMF_User $user, $logText = '')
    {
        if (PMF_Configuration::getInstance()->get('main.enableAdminLog')) {
            
            $query = sprintf("
                INSERT INTO
                    %sfaqadminlog
                (id, time, usr, text, ip)
                    VALUES 
                (%d, %d, %d, '%s', '%s')",
                    SQLPREFIX,
                    $this->db->nextId(SQLPREFIX.'faqadminlog', 'id'),
                    $_SERVER['REQUEST_TIME'],
                    $user->userdata->get('user_id'),
                    $this->db->escape(nl2br($logText)),
                    $_SERVER['REMOTE_ADDR']);
            
            return $this->db->query($query);
        } else {
        	return false;
        }
    }
    
    /**
     * Deletes logging data older than 30 days
     *
     * @return boolean
     */
    public function delete()
    {
        $query = sprintf(
            "DELETE FROM
                %sfaqadminlog
            WHERE
                time < %d",
            SQLPREFIX,
            $_SERVER['REQUEST_TIME'] - 30 * 86400);

        if ($this->db->query($query)) {
            return true;
        }
        return false;
    }
}

