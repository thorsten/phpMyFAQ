<?php
/**
 * Handles all the stuff for visits
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Visits
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-08
 */
class PMF_Visits
{
    /**
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Visits
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
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
            PMF_Db::getTablePrefix(),
            $id,
            $this->_config->getLanguage()->getLanguage()
        );

        $result = $this->_config->getDb()->query($query);
        if ($this->_config->getDb()->numRows($result)) {
            $row     = $this->_config->getDb()->fetchObject($result);
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
            PMF_Db::getTablePrefix(),
            $id,
            $this->_config->getLanguage()->getLanguage(),
            1,
            $_SERVER['REQUEST_TIME']
        );
        $this->_config->getDb()->query($query);

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
            PMF_Db::getTablePrefix(),
            $_SERVER['REQUEST_TIME'],
            $id,
            $this->_config->getLanguage()->getLanguage()
        );
        $this->_config->getDb()->query($query);

        return true;
    }

    /**
     * Get all the entries from the table faqvisits
     *
     * @return array
     */
    public function getAllData()
    {
        $data = array();

        $query = sprintf("
            SELECT
                *
             FROM
                %sfaqvisits
             ORDER BY
                visits DESC",
            PMF_Db::getTablePrefix()
            );
        $result = $this->_config->getDb()->query($query);

        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $data[] = array(
                'id'         => $row->id,
                'lang'       => $row->lang,
                'visits'     => $row->visits,
                'last_visit' => $row->last_visit
            );
        }

        return $data;
    }
}