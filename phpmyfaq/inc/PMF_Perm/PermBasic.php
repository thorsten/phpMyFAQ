<?php
/**
 * The basic permission class provides user rights.
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
 * @package   PMF_Perm
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Perm_PermBasic
 *
 * @category  phpMyFAQ 
 * @package   PMF_Perm
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-17
 */
class PMF_Perm_PermBasic extends PMF_Perm
{
    // --- ATTRIBUTES ---

    /**
     * default_right_data
     *
     * default right data stored when a new right is created.
     *
     * @access public
     * @var array
     */
    public $default_right_data = array(
        'name'          => 'DEFAULT_RIGHT',
        'description'   => 'Short description.',
        'for_users'     => true,
        'for_groups'    => true
    );

    // --- OPERATIONS ---

    /**
     * Returns true if the user given by user_id has the right
     * specified by right_id, otherwise false.
     *
     * @param  integer $user_id  User ID
     * @param  integer $right_id Right ID
     * @return bool
     */
    public function checkUserRight($user_id, $right_id)
    {
        // check right id
        if ($right_id <= 0) {
            return false;
        }
        
        // check right
        $select = sprintf("
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaquser_right fur,
                %sfaquser fu
            WHERE
                fr.right_id = %d AND
                fr.right_id = fur.right_id AND
                fu.user_id  = %d AND
                fu.user_id  = fur.user_id",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $right_id,
            $user_id);
            
        $res = $this->db->query($select);
        // return result
        if ($this->db->num_rows($res) == 1) {
            return true;
        }
        
        return false;
    }

    /**
     * Returns an array with the IDs of all user-rights the user
     * specified by user_id owns. Group rights are not taken into
     * account.
     *
     * @param  integer $user_id User ID
     * @return array
     */
    public function getUserRights($user_id)
    {
        // get user rights
        $select = sprintf("
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaquser_right fur,
                %sfaquser fu
            WHERE
                fr.right_id = fur.right_id AND
                fu.user_id  = %d AND
                fu.user_id  = fur.user_id",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $user_id);
            
        $res    = $this->db->query($select);
        $result = array();
        while ($row = $this->db->fetch_assoc($res)) {
            $result[] = $row['right_id'];
        }
        return $result;
    }

    /**
     * Gives the user a new user-right.
     * Returns true on success, otherwise false.
     *
     * @param  integer $user_id  User ID
     * @param  integer $right_id Right ID
     * @return boolean
     */
    public function grantUserRight($user_id, $right_id)
    {
        // is right for users?
        $right_data = $this->getRightData($right_id);
        
        if (!$right_data['for_users']) {
            return false;
        }
        
        $insert = sprintf("
            INSERT INTO
                %sfaquser_right
            (user_id, right_id)
                VALUES
            (%d, %d)",
            SQLPREFIX,
            $user_id,
            $right_id);
        
        $res = $this->db->query($insert);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Refuses the user a user-right.
     * Returns true on succes, otherwise false.
     *
     * @param  integer $user_id  User ID
     * @param  integer $right_id Right ID
     * @return boolean
     */
    public function refuseUserRight($user_id, $right_id)
    {
        $delete = sprintf("
            DELETE FROM
                %sfaquser_right
            WHERE
                user_id  = %d AND
                right_id = %d",
            SQLPREFIX,
            $user_id,
            $right_id);
            
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Returns true if the user given by user_id has the right,
     * otherwise false. Unlike checkUserRight(), right may be a
     * right-ID or a right-name. Another difference is, that also
     * group-rights are taken into account.
     *
     * @param  integer $user_id User ID
     * @param  mixed   $right   Right ID or right name
     * @return bool
     */
    public function checkRight($user_id, $right)
    {
        if (!is_numeric($right) and is_string($right)) {
            $right = $this->getRightId($right);
        }
        
        return $this->checkUserRight($user_id, $right);
    }

    /**
     * Returns an associative array with all data stored for in the
     * database for the specified right. The keys of the returned
     * array are the fieldnames.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    public function getRightData($right_id)
    {
        // get right data
        $select = sprintf("
            SELECT
                right_id,
                name,
                description,
                for_users,
                for_groups
            FROM
                %sfaqright
            WHERE
                right_id = %d",
            SQLPREFIX,
            $right_id);
        
        $res = $this->db->query($select);
        if ($this->db->num_rows($res) != 1) {
            return false;
        }
        
        // process right data
        $right_data               = $this->db->fetch_assoc($res);
        $right_data['for_users']  = (bool)$right_data['for_users'];
        $right_data['for_groups'] = (bool)$right_data['for_groups'];
        return $right_data;
    }

    /**
     * Returns an array that contains the IDs of all user-rights
     * the user owns.
     *
     * @param  integer $user_id User ID
     * @return array
     */
    public function getAllUserRights($user_id)
    {
        return $this->getUserRights($user_id);
    }

    /**
     * Dummy function; this function is only relevant when the
     * permission mode is set to Medium.
     *
     * @param  integer $user_id User ID
     * @return boolean
     */
    public function autoJoin($user_id)
    {
        return true;
    }

    /**
     * Adds a new right into the database. Returns the ID of the
     * new right. The associative array right_data contains the right 
     * data stored in the rights table. 
     *
     * @param array $right_data Array if rights
     * @return int
     */
    public function addRight(Array $right_data)
    {
        if ($this->getRightId($right_data['name']) > 0) {
            return 0;
        }
        
        $next_id    = $this->db->nextID(SQLPREFIX."faqright", "right_id");
        $right_data = $this->checkRightData($right_data);
        
        $insert = sprintf("
            INSERT INTO
                %sfaqright
            (right_id, name, description, for_users, for_groups)
                VALUES
            (%d, '%s', '%s', %d, %d)",
            SQLPREFIX,
            $next_id,
            $right_data['name'],
            $right_data['description'],
            (int)$right_data['for_users'],
            (int)$right_data['for_groups']);
            
        $res = $this->db->query($insert);
        if (!$res) {
            return 0;
        }
        
        return $next_id;
    }

    /**
     * Changes the right data. Returns true on success, otherwise false.
     *
     * @param  integer $right_id   Right ID
     * @param  array   $right_data Array of rights
     * @return boolean
     */
    public function changeRight($right_id, Array $right_data)
    {
        $checked_data = $this->checkRightData($right_data);
        $set          = '';
        $comma        = '';
        foreach ($right_data as $key => $val) {
            $set .= $comma.$key." = '".$checked_data[$key]."'";
            $comma = ",\n                ";
        }
        
        $update = sprintf("
            UPDATE
                %sfaqright
            SET
                %s
            WHERE
                right_id = %d",
            SQLPREFIX,
            $set,
            $right_id);
            
        $res = $this->db->query($update);
        if (!$res) {
            return false;
        }
            
        return true;
    }

    /**
     * Deletes the right from the database.
     * Returns true on success, otherwise false.
     *
     * @param  integer $right_id Right ID
     * @return boolean
     */
    public function deleteRight($right_id)
    {
        // delete right
        $delete = sprintf("
            DELETE FROM
                %sfaqright
            WHERE
                right_id = %d",
            SQLPREFIX,
            $right_id);
        
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        
        // delete user-right links
        $delete = sprintf("
            DELETE FROM
                %sfaquser_right
            WHERE
                right_id = %d",
            SQLPREFIX,
            $right_id);
        
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        
        // delete group-right links
        $delete = sprintf("
            DELETE FROM
                %sfaqgroup_right
            WHERE
                right_id = %d",
            SQLPREFIX,
            $right_id);
        
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
            
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        
        return true;
    }

    /**
     * Returns the right-ID of the right with the name $name.
     *
     * @param  string $name Name
     * @return int
     */
    public function getRightId($name)
    {
        // get right id
        $select = sprintf("
            SELECT
                right_id
            FROM
                %sfaqright
            WHERE
                name = '%s'",
            SQLPREFIX,
            $this->db->escape_string($name));
        
        $res = $this->db->query($select);
        if ($this->db->num_rows($res) != 1) {
            return 0;
        }
        $row = $this->db->fetch_assoc($res);
        return $row['right_id'];
    }

    /**
     * Returns an array that contains the IDs of all rights stored
     * in the database.
     *
     * @return array
     */
    public function getAllRights()
    {
        $select = sprintf("
            SELECT
                right_id
            FROM
                %sfaqright",
            SQLPREFIX);
            
        $res    = $this->db->query($select);
        $result = array();
        while ($row = $this->db->fetch_assoc($res)) {
            $result[] = $row['right_id'];
        }

        return $result;
    }

    /**
     * Returns an array that contains all rights stored in the
     * database. Each array element is an associative array with
     * the complete right-data. By passing the optional parameter
     * $order, the order of the array may be specified. Default is
     * $order = 'right_id ASC'.
     *
     * @param  string $order Ordering
     * @return array
     */
    public function getAllRightsData($order = 'ASC')
    {
        $select = sprintf("
            SELECT
                right_id,
                name,
                description,
                for_users,
                for_groups
            FROM
                %sfaqright
            ORDER BY
                right_id %s",
            SQLPREFIX,
            $order);
            
        $res    = $this->db->query($select);
        $result = array();
        $i      = 0;
        
        while ($row = $this->db->fetch_assoc($res)) {
            $result[$i] = $row;
            $i++;
        }

        return $result;
    }

    /**
     * Checks the given associative array $right_data. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->default_right_data.
     * Returns the corrected $right_data associative array.
     *
     * @param  array $right_data Array of rights
     * @return array
     */
    public function checkRightData(Array $right_data)
    {
        if (!isset($right_data['name']) || !is_string($right_data['name'])) {
            $right_data['name'] = $this->default_right_data['name'];
        }
        if (!isset($right_data['description']) || !is_string($right_data['description'])) {
            $right_data['description'] = $this->default_right_data['description'];
        }
        if (!isset($right_data['for_users'])) {
            $right_data['for_users'] = $this->default_right_data['for_users'];
        }
        if (!isset($right_data['for_groups'])) {
            $right_data['for_groups'] = $this->default_right_data['for_groups'];
        }
        
        $right_data['for_users']  = (int)$right_data['for_users'];
        $right_data['for_groups'] = (int)$right_data['for_groups'];
        
        return $right_data;
    }

    /**
     * Refuses all user rights.
     * Returns true on success, otherwise false.
     *
     * @param  integer $user_id User ID
     * @return boolean
     */
    public function refuseAllUserRights($user_id)
    {
        $delete = sprintf("
            DELETE FROM
                %sfaquser_right
            WHERE
                user_id  = %d",
            SQLPREFIX,
            $user_id);
        
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }
}
