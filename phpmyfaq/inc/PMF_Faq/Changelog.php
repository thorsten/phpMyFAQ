<?php
/**
 * FAQ record changelog class
 *
 * PHP Version 5.2.0
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
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-02-20
 */

/**
 * PMF_Faq_User
 * 
 * @category  phpMyFAQ
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-02-20
 */
class PMF_Faq_Changelog extends PMF_Faq_Abstract implements PMF_Faq_Interface 
{
    /**
     * Changelog ID
     * 
     * @var integer
     */
    private $_changelogId;
    
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Creates a new entry
     *
     * @param array $data Array of data
     * 
     * @return boolean
     * @throws PMF_Exception
     */
    public function create(Array $data)
    {
        if (is_null($data['id'])) {
            $this->_changelogId = $this->db->nextID(SQLPREFIX.'faqchanges', 'id');
        }
        
        $query = sprintf("
            INSERT INTO
                %sfaqchanges
            (id, beitrag, lang, revision_id, usr, datum, what)
                VALUES
            (%d, %d, '%s', %d, %d, %d, '%s')",
            SQLPREFIX,
            $this->_changelogId,
            $data['record_id'],
            $data['record_lang'],
            $data['revision_id'],
            $data['user_id'],
            $data['date'],
            $this->db->escape_string($data['changelog']));
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    
    /**
     * Updates an existing entry
     *
     * @param integer $id   ID
     * @param array   $data Array of data
     * 
     * @return boolean
     * @throws PMF_Exception
     */
    public function update($id, Array $data)
    {
    }
    
    /**
     * Deletes an entry
     *
     * @param integer $id ID
     * 
     * @return boolean
     * @throws PMF_Exception
     */
    public function delete($id)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqchanges
            WHERE
                id = %d",
            SQLPREFIX,
            (int)$id);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    
    /**
     * Fetches one entry
     *
     * @param integer $id ID
     * 
     * @return array
     * @throws PMF_Exception
     */
    public function fetch($id)
    {
        $changelog = array();
        $query     = sprintf("
            SELECT
                id,
                beitrag AS record_id,
                lang AS record_lang,
                revision_id,
                usr AS user_id,
                datum AS date,
                what AS changelog
            FROM
                %sfaqchanges
            WHERE
                record_id = %d",
            SQLPREFIX,
            (int)$id);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $changelog = $this->db->fetchAll($result);
        }
        
        return $changelog;
    }
    
    /**
     * Fetches all entries, if parameter = null, otherwise all from the given
     * array like array(1, 2, 3)
     *
     * @param array $ids Array of IDs
     * 
     * @return array
     * @throws PMF_Exception
     */
    public function fetchAll(Array $ids = null)
    {
    }
    
}