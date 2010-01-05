<?php
/**
 * User permission class for all phpMyFAQ category
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
 * PMF_Category_User
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */
class PMF_Category_User extends PMF_Category_Abstract implements PMF_Category_Interface 
{
    /**
     * Permission information
     * 
     * @var object
     */
    public $permission = null;
    
    /**
     * Permission container with all informations
     * 
     * @var array
     */
    public $permissionContainer = null;
    
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
        $query = sprintf("
            INSERT INTO
                %sfaqcategory_user
            (category_id, user_id)
                VALUES
            (%d, %d)",
            SQLPREFIX,
            (int)$data['category_id'],
            (int)$data['user_id']);
        
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
                %sfaqcategory_user
            SET
                user_id = %d
            WHERE
                category_id = %d",
            SQLPREFIX,
            $data['user_id'],
            (int)$id);
        
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
                %sfaqcategory_user
            WHERE
                category_id = %d",
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
     * @throws PMF_Category_Exception
     */
    public function fetch($id)
    {
        $query = sprintf("
            SELECT
                category_id, user_id
            FROM
                %sfaqcategory_user
            WHERE
                category_id = %d",
            SQLPREFIX,
            (int)$id);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $this->permission = array_shift($this->db->fetchAll($result));
        }
        
        return $this->permission;
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
        
    }
    
}