<?php
/**
* $Id: Logging.php,v 1.2 2006-11-10 15:47:47 matteo Exp $
*
* The main Logging class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @package      phpMyFAQ
* @since        2006-08-15
* @copyright    (c) 2006 phpMyFAQ Team
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

class PMF_Logging
{
    /**
     * DB handle
     *
     * @var object
     */
    var $db = null;

    /**
     * Constructor
     *
     * @param   object  PMF_Db
     * @since   2006-08-10
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function PMF_Logging(&$db)
    {
        $this->db = &$db;
    }

    /**
     * Returns the number of entries
     *
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getNumberOfEntries()
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
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAll()
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
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function delete()
    {
        $query = sprintf(
            "DELETE FROM
                %sfaqadminlog
            WHERE
                time < %d",
            SQLPREFIX,
            time() - 30 * 86400);

        if ($this->db->query($query)) {
            return true;
        }
        return false;
    }
}

