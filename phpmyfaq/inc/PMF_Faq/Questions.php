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
 * PMF_Faq_Questions
 * 
 * @category  phpMyFAQ
 * @package   PMF_FAq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-24
 */
class PMF_Faq_Questions extends PMF_Faq_Abstract implements PMF_Faq_Interface 
{
    /**
     * FAQ question ID
     * 
     * @var integer
     */
    private $_questionId = null;
    
    /**
     * Data of the record
     * 
     * @var object
     */
    public $questionData = null;
    
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
            $this->_questionId = $this->db->nextID(SQLPREFIX.'faqquestions', 'id');
        }
        
        $query = sprintf("
            INSERT INTO
                %sfaqquestions
            VALUES
                (%d, '%s', '%s', %d, '%s', '%s', '%s')",
            SQLPREFIX,
            $this->_questionId,
            $this->db->escape_string($data['username']),
            $this->db->escape_string($data['email']),
            $data['category_id'],
            $this->db->escape_string($data['question']),
            $data['date'],
            $data['is_visible']);
        
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
        return false;
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
                %sfaqquestion
            WHERE
                id = %d",
            SQLPREFIX,
            $id);
        
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
                 ask_username AS username,
                 ask_usermail AS email,
                 ask_rubrik AS category_id,
                 ask_content AS question,
                 ask_date AS date,
                 is_visible
            FROM
                %sfaqquestions
            WHERE
                id = %d",
            SQLPREFIX,
            (int)$id);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $this->questionData = array_shift($this->db->fetchAll($result));
        }
        
        return $this->questionData;
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
        $questions = array();
        $query     = sprintf("
            SELECT
                 id,
                 ask_username AS username,
                 ask_usermail AS email,
                 ask_rubrik AS category_id,
                 ask_content AS question,
                 ask_date AS date,
                 is_visible
            FROM
                %sfaqquestions
            WHERE
                1=1",
            SQLPREFIX);
        
        if (!is_null($ids)) {
            $query .= sprintf("
            AND 
                id IN (%s)",
            implode(', ', $ids));
        }
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $questions = $this->db->fetchAll($result);
        }
        
        return $questions;
    }
    
    /**
     * Sets the visibilty of a question
     *
     * @param   integer $questionId FAQ question ID
     * @param   string  $isVisible  Is visible?
     * 
     * @return  boolean
     */
    function setVisibility($questionId, $isVisible)
    {
        $query = sprintf("
            UPDATE
                %sfaqquestions
            SET
                is_visible = '%s'
            WHERE
                id = %d",
            SQLPREFIX,
            $isVisible,
            $questionId);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }

    /**
     * Returns the visibilty of a question
     *
     * @param   integer $questionId FAQ question ID
     * 
     * @return  string
     */
     function getVisibility($questionId)
     {
        $query = sprintf('
            SELECT
                is_visible
            FROM
                %sfaqquestions
            WHERE
                id = %d',
            SQLPREFIX,
            $questionId);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            return $this->db->fetch_object($result)->is_visible;
        }
        
        return null;
     }
    /**
     * Setter for FAQ question ID
     *
     * @param integer $questionId FAQ question ID
     * 
     * @return void
     */
    public function setQuestionId($questionId)
    {
        $this->_questionId = (int)$questionId;
    }
    
    /**
     * Getter for FAQ question ID
     *
     * @return integer
     */
    public function getQuestionId()
    {
        return $this->_questionId;
    }
}