<?php
/**
 * The medium permission class provides group rights.
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

/**
 * PMF_Perm_PermMedium
 *
 * @category  phpMyFAQ 
 * @package   PMF_Perm
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-17
 */
class PMF_Perm_PermMedium extends PMF_Perm_PermBasic
{
    /**
     * Default data for new groups.
     *
     * @var array
     */
    public $default_group_data = array(
        'name'        => 'DEFAULT_GROUP',
        'description' => 'Short group description. ',
        'auto_join'   => false
    );

    /**
     * Returns true if the group specified by $group_id owns the
     * right given by $right_id, otherwise false.
     *
     * @param  integer $group_id Group ID
     * @param  integer $right_id Right ID
     * @return bool
     */
    public function checkGroupRight($group_id, $right_id)
    {
        // check input
        if ($right_id <= 0 || $group_id <= 0 || !is_numeric($right_id) || !is_numeric($group_id)) {
            return false;
        }
        // check right
        $select = sprintf("
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaqgroup_right fgr,
                %sfaqgroup fg
            WHERE
                fr.right_id = %d AND
                fr.right_id = fgr.right_id AND
                fg.group_id = fgr.group_id AND
                fg.group_id = %d",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $right_id,
            $group_id);
        
        $res = $this->db->query($select);
        if ($this->db->numRows($res) == 1) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array that contains the right-IDs of all
     * group-rights the group $group_id owns.
     *
     * @param  integer $group_id Group ID
     * @return array
     */
    public function getGroupRights($group_id)
    {
        if ($group_id <= 0 || !is_numeric($group_id)) {
            return false;
        }
        // check right
        $select = sprintf("
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaqgroup_right fgr,
                %sfaqgroup fg
            WHERE
                fg.group_id = %d AND
                fg.group_id = fgr.group_id AND
                fr.right_id = fgr.right_id",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $group_id);
        
        $res    = $this->db->query($select);
        $result = array();
        while ($row = $this->db->fetch_assoc($res)) {
            $result[] = $row['right_id'];
        }
        return $result;
    }

    /**
     * Returns true, if the user given by $user_id owns the right
     * specified by $right. It does not matter if the user owns this
     * right as a user-right or because of a group-membership.
     * The parameter $right may be a right-ID (recommended for
     * performance) or a right-name.
     *
     * @param  integer $group_id Group ID
     * @param  mixed   $right    Rights
     * @return boolean
     */
    public function checkRight($user_id, $right)
    {
        // get right id
        if (!is_numeric($right) && is_string($right)) {
            $right = $this->getRightId($right);
        }
        // check user right and group right
        if ($this->checkUserGroupRight($user_id, $right) || $this->checkUserRight($user_id, $right)) {
            return true;
        }
        return false;
    }

    /**
     * Grants the group given by $group_id the right specified by
     * $right_id.
     *
     * @param  integer $group_id Group ID
     * @param  integer $right_id Right ID
     * @return boolean
     */
    public function grantGroupRight($group_id, $right_id)
    {
        // check input
        if ($right_id <= 0 || $group_id <= 0 || !is_numeric($right_id) || !is_numeric($group_id)) {
            return false;
        }
        // is right for users?
        $right_data = $this->getRightData($right_id);
        if (!$right_data['for_groups']) {
            return false;
        }
        // grant right
        $insert = sprintf("
            INSERT INTO
                %sfaqgroup_right
            (group_id, right_id)
                VALUES
            (%d, %d)",
            SQLPREFIX,
            $group_id,
            $right_id);
            
        $res = $this->db->query($insert);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Refuses the group given by $group_id the right specified by
     * $right_id.
     *
     * @param  integer $group_id Group ID
     * @param  integer $right_id Right ID
     * @return boolean
     */
    public function refuseGroupRight($group_id, $right_id)
    {
        // check input
        if ($right_id <= 0 || $group_id <= 0 || !is_numeric($right_id) || !is_numeric($group_id)) {
            return false;
        }
        // grant right
        $delete = sprintf("
            DELETE FROM
                %sfaqgroup_right
            WHERE
                group_id = %d AND
                right_id = %d",
            SQLPREFIX,
            $group_id,
            $right_id);
            
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Adds a new group to the database and returns the ID of the
     * new group. The associative array $group_data contains the
     * data for the new group.
     *
     * @param  array $group_data Array of group data
     * @return int
     */
    public function addGroup(Array $group_data)
    {
        // check if group already exists
        if ($this->getGroupId($group_data['name']) > 0) {
            return 0;
        }
        
        $next_id    = $this->db->nextID(SQLPREFIX."faqgroup", "group_id");
        $group_data = $this->checkGroupData($group_data);
        $insert     = sprintf("
            INSERT INTO
                %sfaqgroup
            (group_id, name, description, auto_join)
                VALUES
            (%d, '%s', '%s', '%s')",
            SQLPREFIX,
            $next_id,
            $group_data['name'],
            $group_data['description'],
            (int)$group_data['auto_join']);

        $res = $this->db->query($insert);
        if (!$res) {
            return 0;
        }
        return $next_id;
    }

    /**
     * Changes the group data of the given group.
     *
     * @param  integer $group_id   Group ID
     * @param  array   $group_data Array of group data
     * @return boolean
     */
    public function changeGroup($group_id, Array $group_data)
    {
        $checked_data = $this->checkGroupData($group_data);
        $set          = "";
        $comma        = "";
        
        foreach ($group_data as $key => $val) {
            $set  .= $comma.$key." = '".$this->db->escapeString($checked_data[$key])."'";
            $comma = ",\n                ";
        }
        
        $update = sprintf("
            UPDATE
                %sfaqgroup
            SET
                %s
            WHERE
                group_id = %d",
                SQLPREFIX,
                $set,
                $group_id);
                
        $res = $this->db->query($update);
        
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Removes the group given by $group_id from the database.
     * Returns true on success, otherwise false.
     *
     * @param  integer $group_id Group ID
     * @return boolean
     */
    public function deleteGroup($group_id)
    {
        if ($group_id <= 0 || !is_numeric($group_id)) {
            return false;
        }

        $delete = sprintf("
            DELETE FROM
                %sfaqgroup
            WHERE
                group_id = %d",
            SQLPREFIX,
            $group_id);
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        
        $delete = sprintf("
            DELETE FROM
                %sfaquser_group
            WHERE
                group_id = %d",
            SQLPREFIX,
            $group_id);
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        
        $delete = sprintf("
            DELETE FROM
                %sfaqgroup_right
            WHERE
                group_id = %d",
            SQLPREFIX,
            $group_id);
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        
        return true;
    }

    /**
     * Returns true if the user given by $user_id is a member of
     * the group specified by $group_id, otherwise false.
     *
     * @param  integer $group_id Group ID
     * @param  integer $right_id Right ID
     * @return boolean
     */
    public function isGroupMember($user_id, $group_id)
    {
        if ($user_id <= 0 || $group_id <= 0 || !is_numeric($user_id) || !is_numeric($group_id)) {
            return false;
        }
        
        $select = sprintf("
            SELECT
                fu.user_id AS user_id
            FROM
                %sfaquser fu,
                %sfaquser_group fug,
                %sfaqgroup fg
            WHERE
                fu.user_id  = %d AND
                fu.user_id  = fug.user_id AND
                fg.group_id = fug.group_id AND
                fg.group_id = %d",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $user_id,
            $group_id);
        
        $res = $this->db->query($select);
        if ($this->db->numRows($res) == 1) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array that contains the user-IDs of all members
     * of the group $group_id.
     *
     * @param  integer $group_id Group ID
     * @return array
     */
    public function getGroupMembers($group_id)
    {
        if ($group_id <= 0 || !is_numeric($group_id)) {
            return false;
        }
        
        $select = sprintf("
            SELECT
                fu.user_id AS user_id
            FROM
                %sfaquser fu,
                %sfaquser_group fug,
                %sfaqgroup fg
            WHERE
                fg.group_id = %d AND
                fg.group_id = fug.group_id AND
                fu.user_id  = fug.user_id",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $group_id);
        
        $res    = $this->db->query($select);
        $result = array();
        while ($row = $this->db->fetch_assoc($res)) {
            $result[] = $row['user_id'];
        }
        return $result;
    }

    /**
     * Adds a new member $user_id to the group $group_id.
     * Returns true on success, otherwise false.
     *
     * @param  integer $group_id Group ID
     * @param  integer $right_id Right ID
     * @return boolean
     */
    function addToGroup($user_id, $group_id)
    {
        if ($user_id <= 0 || $group_id <= 0 || !is_numeric($user_id) || !is_numeric($group_id)) {
            return false;
        }
        
        if (!$this->getGroupData($group_id)) {
            return false;
        }
        
        // add user to group
        $insert = sprintf("
            INSERT INTO
                %sfaquser_group
            (user_id, group_id)
               VALUES
            (%d, %d)",
            SQLPREFIX,
            $user_id,
            $group_id);
            
        $res = $this->db->query($insert);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Removes a user $user_id from the group $group_id.
     * Returns true on success, otherwise false.
     *
     * @param  integer $group_id Group ID
     * @param  integer $right_id Right ID
     * @return boolean
     */
    public function removeFromGroup($user_id, $group_id)
    {
        if ($user_id <= 0 || $group_id <= 0 || !is_numeric($user_id) || !is_numeric($group_id)) {
            return false;
        }
        
        $delete = sprintf("
            DELETE FROM
                %sfaquser_group
            WHERE
                user_id  = %d AND
                group_id = %d",
            SQLPREFIX,
            $user_id,
            $group_id);
            
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Returns the ID of the group that has the name $name. Returns
     * 0 if the group-name cannot be found.
     *
     * @param  string $name Group name
     * @return int
     */
    public function getGroupId($name)
    {
        $select = sprintf("
            SELECT
                group_id
            FROM
                %sfaqgroup
            WHERE
                name = '%s'",
            SQLPREFIX,
            $this->db->escapeString($name));
            
        $res = $this->db->query($select);
        if ($this->db->numRows($res) != 1) {
            return 0;
        }
        $row = $this->db->fetch_assoc($res);
        return $row['group_id'];
    }

    /**
     * Returns an associative array with the group-data of the group
     * $group_id.
     *
     * @param  integer $group_id Group ID
     * @return array
     */
    public function getGroupData($group_id)
    {
        if ($group_id <= 0 || !is_numeric($group_id)) {
            return false;
        }
        
        $select = sprintf("
            SELECT
                group_id,
                name,
                description,
                auto_join
            FROM
                %sfaqgroup
            WHERE
                group_id = %d",
            SQLPREFIX,
            $group_id);
            
        $res = $this->db->query($select);
        if ($this->db->numRows($res) != 1) {
            return array();
        }
        return $this->db->fetch_assoc($res);
    }

    /**
     * Returns an array that contains the IDs of all groups in which
     * the user $user_id is a member.
     *
     * @param  integer $user_id User ID
     * 
     * @return array
     */
    public function getUserGroups($user_id)
    {
        if ($user_id <= 0 || !is_numeric($user_id)) {
            return array(-1);
        }
        
        $select = sprintf("
            SELECT
                fg.group_id AS group_id
            FROM
                %sfaquser fu,
                %sfaquser_group fug,
                %sfaqgroup fg
            WHERE
                fu.user_id  = %d AND
                fu.user_id  = fug.user_id AND
                fg.group_id = fug.group_id",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $user_id);
        
        $res    = $this->db->query($select);
        $result = array(-1);
        while ($row = $this->db->fetch_assoc($res)) {
            $result[] = $row['group_id'];
        }
        return $result;
    }

    /**
     * Returns an array with the IDs of all groups stored in the
     * database.
     *
     * @return array
     */
    public function getAllGroups()
    {
        $select = sprintf("
            SELECT
                group_id
            FROM
                %sfaqgroup",
            SQLPREFIX);
            
        $res    = $this->db->query($select);
        $result = array();
        while ($row = $this->db->fetch_assoc($res)) {
            $result[] = $row['group_id'];
        }

        return $result;
    }

    /**
     * Get all groups in <option> tags
     *
     * @param  integer $groups Selected group
     * @return string
     */
    public function getAllGroupsOptions($groups = -1)
    {
        $options = '';
        $allGroups = $this->getAllGroups();
        foreach ($allGroups as $group_id) {
            if (-1 != $group_id) {
                $options .= sprintf('<option value="%d"%s>%s</option>',
                    $group_id,
                    ($group_id == $groups) ? ' selected="selected"' : '',
                    $this->getGroupName($group_id));
            }
        }
        return $options;
    }

    /**
     * checkUserGroupRight
     *
     * Returns true if the user $user_id owns the right $right_id
     * because of a group-membership, otherwise false.
     *
     * @param  integer $user_id  User ID
     * @param  integer $right_id Right ID
     * @return boolean
     */
    public function checkUserGroupRight($user_id, $right_id)
    {
        // check input
        if ($right_id <= 0 || $user_id <= 0 || !is_numeric($right_id) || !is_numeric($user_id)) {
            return false;
        }
        
        $select = sprintf("
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaqgroup_right fgr,
                %sfaqgroup fg,
                %sfaquser_group fug,
                %sfaquser fu
            WHERE
                fr.right_id = %d AND
                fr.right_id = fgr.right_id AND
                fg.group_id = fgr.group_id AND
                fg.group_id = fug.group_id AND
                fu.user_id  = fug.user_id AND
                fu.user_id  = %d",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $right_id,
            $user_id);
        
        $res = $this->db->query($select);
        if ($this->db->numRows($res) == 1) {
            return true;
        }
        return false;
    }

    /**
     * Checks the given associative array $group_data. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->default_group_data.
     * Returns the corrected $group_data associative array.
     *
     * @param  array $group_data Array of group data
     * @return array
     */
    public function checkGroupData(Array $group_data)
    {
        if (!isset($group_data['name']) || !is_string($group_data['name'])) {
            $group_data['name'] = $this->default_group_data['name'];
        }
        if (!isset($group_data['description']) || !is_string($group_data['description'])) {
            $group_data['description'] = $this->default_group_data['description'];
        }
        if (!isset($group_data['auto_join'])) {
            $group_data['auto_join'] = $this->default_group_data['auto_join'];
        }
        $group_data['auto_join'] = (int)$group_data['auto_join'];
        return $group_data;
    }

    /**
     * Returns an array that contains the right-IDs of all rights
     * the user $user_id owns. User-rights and the rights the user
     * owns because of a group-membership are taken into account.
     *
     * @param  integer $user_id User ID
     * @return array
     */
    public function getAllUserRights($user_id)
    {
        if ($user_id <= 0 || !is_numeric($user_id)) {
            return false;
        }
        $user_rights  = $this->getUserRights($user_id);
        $group_rights = $this->getUserGroupRights($user_id);
        return array_unique(array_merge($user_rights, $group_rights));
    }

    /**
     * Adds the user $user_id to all groups with the auto_join
     * option. By using the auto_join option, user administration
     * can be much easier. For example by setting this option only
     * for a single group called 'All Users'. The autoJoin() method
     * then has to be called every time a new user registers.
     * Returns true on success, otherwise false.
     *
     * @param  integer $user_id User ID
     * @return boolean
     */
    public function autoJoin($user_id)
    {
        if ($user_id <= 0 || !is_numeric($user_id)) {
            return false;
        }
        
        $select = sprintf("
            SELECT
                group_id
            FROM
                %sfaqgroup
            WHERE
                auto_join = 1",
            SQLPREFIX);
            
        $res = $this->db->query($select);
        if (!$res) {
            return false;
        }
        
        $auto_join = array();
        while ($row = $this->db->fetch_assoc($res)) {
            $auto_join[] = $row['group_id'];
        }
        // add to groups
        foreach ($auto_join as $group_id) {
            $this->addToGroup($user_id, $group_id);
        }
        return true;
    }

    /**
     * Removes the user $user_id from all groups.
     * Returns true on success, otherwise false.
     *
     * @param  integer $user_id User ID
     * @return boolean
     */
    public function removeFromAllGroups($user_id)
    {
        if ($user_id <= 0 || !is_numeric($user_id)) {
            return false;
        }
        
        $delete = sprintf("
            DELETE FROM
                %sfaquser_group
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

    /**
     * getUserGroupRights
     *
     * Returns an array that contains the IDs of all rights the user
     * $user_id owns because of a group-membership.
     *
     * @param  integer $user_id User ID
     * @return array
     */
    public function getUserGroupRights($user_id)
    {
        if ($user_id <= 0 || !is_numeric($user_id)) {
            return false;
        }
        
        $select = sprintf("
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaqgroup_right fgr,
                %sfaqgroup fg,
                %sfaquser_group fug,
                %sfaquser fu
            WHERE
                fu.user_id  = %d AND
                fu.user_id  = fug.user_id AND
                fg.group_id = fug.group_id AND
                fg.group_id = fgr.group_id AND
                fr.right_id = fgr.right_id",
            SQLPREFIX,
            SQLPREFIX,
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
     * Refuses all group rights.
     * Returns true on success, otherwise false.
     *
     * @param  integer $group_id Group ID
     * @return boolean
     */
    public function refuseAllGroupRights($group_id)
    {
        if ($group_id <= 0 || !is_numeric($group_id)) {
            return false;
        }
        
        $delete = sprintf("
            DELETE FROM
                %sfaqgroup_right
            WHERE
                group_id  = %d",
            SQLPREFIX,
            $group_id);
            
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Returns the name of the group $group_id.
     *
     * @param  integer $group_id Group ID
     * @return string
     */
    public function getGroupName($group_id)
    {
        if ($group_id <= 0 || !is_numeric($group_id)) {
            return false;
        }
        
        $select = sprintf("
            SELECT
                name
            FROM
                %sfaqgroup
            WHERE
                group_id = %d",
            SQLPREFIX,
            $group_id);
            
        $res = $this->db->query($select);
        if ($this->db->numRows($res) != 1) {
            return array();
        }
        $row = $this->db->fetch_assoc($res);
        return $row['name'];
    }

    /**
     * Removes all users from the group $group_id.
     * Returns true on success, otherwise false.
     *
     * @param  integer $group_id Group ID
     * @return bool
     */
    public function removeAllUsersFromGroup($group_id)
    {
    	if ($group_id <= 0 or !is_numeric($group_id)) {
            return false;
    	}
        // remove all user from group
        $delete = sprintf("
            DELETE FROM
                %sfaquser_group
            WHERE
                group_id = %d",
            SQLPREFIX,
            $group_id);
            
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }
}