<?php
/**
 * The main phpMyFAQ instances class
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
 * @package   PMF_Instance
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-02-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Instance
 *
 * @category  phpMyFAQ
 * @package   PMF_Instance
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-02-20
 */
class PMF_Instance
{
    /**
     * Tablename
     */
    const TABLE_FAQINSTANCES = 'faqinstances';

    /**
     * DB handle
     *
     * @var PMF_DB
     */
    private $_db = null;

    /**
     * Instance ID
     *
     * @var integer
     */
    private $_id;

    /**
     * Constructor
     *
     * @return PMF_Instance
     */
    public function __construct(PMF_DB_Driver $database)
    {
        $this->_db = $database;
    }

    /**
     * Adds a new instance
     *
     * @param array $data
     *
     * @return integer $id
     */
    public function addInstance(Array $data)
    {
        $this->_id = $this->_db->nextId(SQLPREFIX . self::TABLE_FAQINSTANCES, 'id');

        $insert = sprintf(
            "INSERT INTO %sfaqinstances VALUES (%d, '%s', '%s', '%s', NOW(), NOW())",
            SQLPREFIX,
            $this->_id,
            $data['url'],
            $data['instance'],
            $data['comment']
        );

        if (! $this->_db->query($insert)) {
            return 0;
        }

        return $this->_id;
    }

    /**
     * Returns all instances
     *
     * @return array
     */
    public function getAllInstances()
    {
        $select = sprintf(
            "SELECT * FROM %sfaqinstances",
            SQLPREFIX
        );

        $result = $this->_db->query($select);

        return $this->_db->fetchAll($result);
    }

    /**
     * Returns the instance
     *
     * @param integer $id
     *
     * @return array
     */
    public function getInstanceById($id)
    {
        $select = sprintf(
            "SELECT * FROM %sfaqinstances WHERE id = %d",
            SQLPREFIX,
            (int)$id
        );

        $result = $this->_db->query($select);

        return $this->_db->fetchAll($result);
    }

    /**
     * Returns the instance
     *
     * @param string $url
     *
     * @return array
     */
    public function getInstanceByUrl($url)
    {
        $select = sprintf(
            "SELECT * FROM %sfaqinstances WHERE url = '%s'",
            SQLPREFIX,
            $url
        );

        $result = $this->_db->query($select);

        return $this->_db->fetchAll($result);
    }

    /**
     * Deletes an instance
     *
     * @return boolean
     */
    public function removeInstance($id)
    {
        $delete = sprintf(
            "DELETE FROM %sfaqinstances WHERE id = %d",
            SQLPREFIX,
            (int)$id
        );

        return $this->_db->query($delete);
    }
}