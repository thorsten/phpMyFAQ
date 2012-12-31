<?php
/**
 * Handles all the stuff for visits
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
 * @package   PMF_Visits
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-08
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Visits
 *
 * @category  phpMyFAQ
 * @package   PMF_Visits
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-08
 */
class PMF_Visits
{
    /**
     * The database handle
     *
     * @var PMF_DB_Driver
     */
    private $db = null;
    
    /**
     * The user agent language
     *
     * @var string
     */
    private $language;
    
    /**
     * Instance
     * 
     * @var PMF_Visits
     */
    private static $instance = null;
    
    /**
     * Constructor
     * 
     * @return void
     */
    private function __construct()
    {
        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Language::$language;
    }


    /**
     * Returns the single instance
     *
     * @access static
     * @return PMF_Visits
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className();
        }
        return self::$instance;
    }
   
    /**
     * __clone() Magic method to prevent cloning
     * 
     * @return void
     */
    private function __clone()
    {
    }
       
    /**
     * Counting the views of a FAQ record
     *
     * @param integer $id      FAQ record ID
     *
     * @return void
     */
    public function logViews($id)
    {
        $nVisits = 0;
        $query   = sprintf("
            SELECT
                visits
            FROM
                %sfaqvisits
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $id,
            $this->language);

        $result = $this->db->query($query);
        if ($this->db->num_rows($result)) {
            $row     = $this->db->fetch_object($result);
            $nVisits = $row->visits;
        }
        if ($nVisits == 0) {
            $this->add($id);
        } else {
            $this->update($id);
        }
    }

    /**
     * Adds a new entry in the table faqvisits
     *
     * @param  integer $id Record ID
     * @return boolean
     */
    public function add($id)
    {
        if (!is_numeric($id)) {
            return false;
        }

        $query = sprintf("
            INSERT INTO
                %sfaqvisits
            VALUES
                (%d, '%s', %d, %d)",
            SQLPREFIX,
            $id,
            $this->language,
            1,
            $_SERVER['REQUEST_TIME']);
        $this->db->query($query);

        return true;
    }

    /**
     * Updates an entry in the table faqvisits
     *
     * @param  integer $id id
     * @return boolean
     */
    private function update($id)
    {
        if (!is_numeric($id)) {
            return false;
        }

        $query = sprintf("
            UPDATE
                %sfaqvisits
            SET
                visits = visits+1,
                last_visit = %d
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            $_SERVER['REQUEST_TIME'],
            $id,
            $this->language);
        $this->db->query($query);

        return true;
    }

    /**
     * Get all the entries from the table faqvisits
     *
     * @return array
     */
    function getAllData()
    {
        $data = array();

        $query = sprintf("
            SELECT
                *
             FROM
                %sfaqvisits
             ORDER BY
                visits DESC",
            SQLPREFIX
            );
        $result = $this->db->query($query);

        while ($row = $this->db->fetch_object($result)) {
            $data[] = array('id'         => $row->id,
                            'lang'       => $row->lang,
                            'visits'     => $row->visits,
                            'last_visit' => $row->last_visit);
        }

        return $data;
    }
}