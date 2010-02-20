<?php
/**
 * FAQ record revisions class
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
 * PMF_Faq_Record
 * 
 * @category  phpMyFAQ
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-02-20
 */
class PMF_Faq_Revision extends PMF_Faq_Abstract implements PMF_Faq_Interface 
{
    /**
     * FAQ record ID
     * 
     * @var integer
     */
    private $_recordId = null;
    
    /**
     * FAQ revision ID
     * 
     * @var integer
     */
    private $_revisionId = null;
    
    /**
     * Data of the record
     * 
     * @var object
     */
    public $recordData = null;
    
    /**
     * Data of all records
     * 
     * @var array
     */
    public $recordContainer = array();
    
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
     * @param array   $data Array of data
     * 
     * @return boolean
     * @throws PMF_Exception
     */
    public function create(Array $data)
    {
        $query = sprintf("
            INSERT INTO
                %sfaqdata_revisions
            SELECT * FROM
                %sfaqdata
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            SQLPREFIX,
            $data['id'],
            $data['lang']);
        
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
                %sfaqdata_revisions
            WHERE
                id = %d",
            SQLPREFIX,
            $id);
            
        if (!is_null($this->language)) {
            $query .= sprintf(" 
            AND 
                lang = '%s'",
            $this->language);
        }
        
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
     * @throws PMF_Category_Exception
     */
    public function fetch($id)
    {
        $query = sprintf("
            SELECT
                id,
                lang,
                solution_id,
                revision_id,
                active,
                sticky,
                keywords,
                thema AS question,
                content AS answer,
                author,
                email,
                comment,
                datum AS date,
                links_state,
                links_check_date,
                date_start
                date_end
            FROM
                %sfaqdata_revisions
            WHERE
                id = %d
            AND
                revision_id = %d",
            SQLPREFIX,
            (int)$id,
            $this->_revisionId);
        
        if (!is_null($this->language)) {
            $query .= sprintf("
            AND 
                lang = '%s'",
            $this->language);
        }
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $this->recordData = array_shift($this->db->fetchAll($result));
        }
        
        return $this->recordData;
    }
    
    /**
     * Fetches all entries, if parameter = null, otherwise all from the given
     * array like array(1, 2, 3)
     *
     * @param array $ids Array of IDs
     * 
     * @return array
     * @throws PMF_Category_Exception
     */
    public function fetchAll(Array $ids = null)
    {
        $query   = sprintf("
            SELECT
                id,
                lang,
                solution_id,
                revision_id,
                active,
                sticky,
                keywords,
                thema AS question,
                content AS answer,
                author,
                email,
                comment,
                datum AS date,
                links_state,
                links_check_date,
                date_start
                date_end
            FROM
                %sfaqdata
            WHERE
                1=1",
            SQLPREFIX);
        
        if (!is_null($ids)) {
            $query .= sprintf("
            AND 
                id IN (%s)",
            implode(', ', $ids));
        }
        
        if (!is_null($this->language)) {
            $query .= sprintf("
            AND 
                lang = '%s'",
            $this->language);
        }
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $this->recordContainer = $this->db->fetchAll($result);
        }
        
        return $this->recordContainer;
    }
    
    /**
     * Setter for FAQ record ID
     *
     * @param integer $recordId FAQ record ID
     * 
     * @return void
     */
    public function setRecordId($recordId)
    {
        $this->_recordId = (int)$recordId;
    }
    
    /**
     * Getter for FAQ record ID
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->_recordId;
    }
    
    /**
     * Setter for FAQ revision ID
     *
     * @param integer $revisionId FAQ revision ID
     * 
     * @return void
     */
    public function setRevisionId($revisionId)
    {
        $this->_revisionId = (int)$revisionId;
    }
    
    /**
     * Getter for FAQ revision ID
     *
     * @return integer
     */
    public function getRevisionId()
    {
        return $this->_revisionId;
    }
}