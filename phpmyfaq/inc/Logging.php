<?php
/**
 * The main Logging class
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2006-08-15
 * @copyright 2006-2008 phpMyFAQ Team
 * @version   CVS: $Id: Logging.php,v 1.4 2008-05-22 11:22:59 thorstenr Exp $
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

/**
 * PMF_Logging
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2006-08-15
 * @copyright 2006-2008 phpMyFAQ Team
 * @version   CVS: $Id: Logging.php,v 1.4 2008-05-22 11:22:59 thorstenr Exp $
 *
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
     * @param  PMF_Db $db PMF_Db object
     * @since  2006-08-10
     * @return void
     */
    public function __construct($db)
    {
        $this->db = &$db;
    }

    /**
     * Returns the number of entries
     *
     * @return  integer
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function getNumberOfEntries()
    {
        $query = sprintf('
            SELECT
                id
            FROM
                %sfaqadminlog',
            SQLPREFIX);

        return $this->db->num_rows($this->db->query($query));
    }

    /**
     * Returns all data from the adminlog
     *
     * @return array
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
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
        while ($row = $this->db->fetch_object($result)) {
            $data[$row->id] = array(
               'time'  => $row->time,
               'usr'   => $row->usr,
               'text'  => $row->text,
               'ip'    => $row->ip);
        }

        return $data;
    }

    /**
     * Deletes logging data older than 30 days
     *
     * @return boolean
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
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

