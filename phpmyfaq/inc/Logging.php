<?php
/**
 * The main Logging class
 *
 * PHP Version 5.2
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
     * @var PMF_Configuration
     */
    private $_config = null;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Logging
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
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

        return $this->_config->getDb()->numRows(
            $this->_config->getDb()->query($query)
        );
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

        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
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
        if ($this->_config->get('main.enableAdminLog')) {
            
            $query = sprintf("
                INSERT INTO
                    %sfaqadminlog
                (id, time, usr, text, ip)
                    VALUES 
                (%d, %d, %d, '%s', '%s')",
                    SQLPREFIX,
                    $this->$this->_config->nextId(SQLPREFIX.'faqadminlog', 'id'),
                    $_SERVER['REQUEST_TIME'],
                    $user->userdata->get('user_id'),
                    $this->$this->_config->escape(nl2br($logText)),
                    $_SERVER['REMOTE_ADDR']);
            
            return $this->$this->_config->query($query);
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

        if ($this->$this->_config->query($query)) {
            return true;
        }
        return false;
    }
}

