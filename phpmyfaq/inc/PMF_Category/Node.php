<?php
/**
 * Node class for all phpMyFAQ category
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
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */

/**
 * PMF_Category_Node
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */
class PMF_Category_Node extends PMF_Category_Abstract implements PMF_Category_Interface 
{
    /**
     * Category ID
     * 
     * @var integer
     */
    private $_categoryId = null;
    
    /**
     * Data of the node
     * 
     * @var object
     */
    public $nodeData = null;
    
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
            $this->_categoryId = $this->db->nextID(SQLPREFIX.'faqcategories', 'id');
        }
        
        $query = sprintf("
            INSERT INTO
                %sfaqcategories
            (id, lang, parent_id, name, description, user_id)
                VALUES
            (%d, '%s', %d, '%s', '%s', %d)",
            SQLPREFIX,
            $this->_categoryId,
            $data['lang'],
            $data['parent_id'],
            $data['name'],
            $data['description'],
            $data['user_id']);
        
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
                %sfaqcategories
            SET
                name = '%s',
                description = '%s',
                user_id = %d
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $data['name'],
            $data['description'],
            $data['user_id'],
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
                %sfaqcategories
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
                id, lang, parent_id, name, description, user_id
            FROM
                %sfaqcategories
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
            $this->nodeData = array_shift($this->db->fetchAll($result));
        }
        
        return $this->nodeData;
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
        $categories = array();
        $query      = sprintf("
            SELECT
                id, lang, parent_id, name, description, user_id
            FROM
                %sfaqcategories
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
            $categories = $this->db->fetchAll($result);
        }
        
        return $categories;
    }
    
    /**
     * Setter for category ID
     *
     * @param integer $_categoryId Category ID
     * 
     * @return void
     */
    public function setCategoryId($categoryId)
    {
        $this->_categoryId = (int)$categoryId;
    }
    
    /**
     * Getter for category ID
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->_categoryId;
    }
}