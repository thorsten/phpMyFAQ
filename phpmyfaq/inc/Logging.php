<?php
/**
 * The main Logging class
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 *  http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Logging
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
 * @copyright 2006-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
                    $this->db->nextID(SQLPREFIX.'faqadminlog', 'id'),
                    $_SERVER['REQUEST_TIME'],
                    $user->userdata->get('user_id'),
                    $this->db->escapeString(nl2br($logText)),
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

