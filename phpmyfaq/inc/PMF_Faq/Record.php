<?php
/**
 * FAQ records class
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
 * @since     2001-01-24
 */

/**
 * PMF_Faq_Record
 * 
 * @category  phpMyFAQ
 * @package   PMF_FAq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-24
 */
class PMF_Faq_Record extends PMF_Faq_Abstract implements PMF_Faq_Interface 
{
    /**
     * FAQ record ID
     * 
     * @var integer
     */
    private $_recordId = null;
    
    /**
     * FAQ solution ID
     * 
     * @var integer
     */
    private $_solutionId = null;
    
    /**
     * Data of the record
     * 
     * @var object
     */
    public $recordData = null;
    
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
     * @throws PMF_Category_Exception
     */
    public function create(Array $data)
    {
        if (is_null($data['id'])) {
            $this->_recordId = $this->db->nextID(SQLPREFIX.'faqdata', 'id');
        }
        
        $query = sprintf("
            INSERT INTO
                %sfaqdata
            VALUES
                (%d, '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s')",
            SQLPREFIX,
            $this->_recordId,
            $data['lang'],
            $this->_solutionId,
            0,
            $data['active'],
            $data['sticky'],
            $this->db->escape_string($data['keywords']),
            $this->db->escape_string($data['thema']),
            $this->db->escape_string($data['content']),
            $this->db->escape_string($data['author']),
            $data['email'],
            $data['comment'],
            $data['date'],
            $data['linkState'],
            $data['linkDateCheck'],
            $data['dateStart'],
            $data['dateEnd']);
        
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
     * @throws PMF_Category_Exception
     */
    public function update($id, Array $data)
    {
        $query = sprintf("
            UPDATE
                %sfaqdata
            SET
                revision_id = %d,
                active = '%s',
                sticky = %d,
                keywords = '%s',
                thema = '%s',
                content = '%s',
                author = '%s',
                email = '%s',
                comment = '%s',
                datum = '%s',
                links_state = '%s',
                links_check_date = %d,
                date_start = '%s',
                date_end = '%s'
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $data['revision_id'],
            $data['active'],
            $data['sticky'],
            $this->db->escape_string($data['keywords']),
            $this->db->escape_string($data['thema']),
            $this->db->escape_string($data['content']),
            $this->db->escape_string($data['author']),
            $data['email'],
            $data['comment'],
            $data['date'],
            $data['linkState'],
            $data['linkDateCheck'],
            $data['dateStart'],
            $data['dateEnd'],
            (int)$id,
            $data['lang']);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    
    /**
     * Deletes an entry
     *
     * @param integer $id ID
     * 
     * @return boolean
     * @throws PMF_Category_Exception
     */
    public function delete($id)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqdata
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
                %sfaqdata
            WHERE
                id = %d",
            SQLPREFIX,
            (int)$id);
        
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
        $records = array();
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
            $records = $this->db->fetchAll($result);
        }
        
        return $records;
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
     * Setter for FAQ solution ID
     *
     * @param integer $solutionId FAQ solution ID
     * 
     * @return void
     */
    public function setSolutionId($solutionId)
    {
        $this->_solutionId = (int)$solutionId;
    }
    
    /**
     * Getter for FAQ solution ID
     *
     * @return integer
     */
    public function getSolutionId()
    {
        return $this->_solutionId;
    }
}