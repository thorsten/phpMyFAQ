<?php

error_reporting(E_ALL);

/**
 * The medium permission class provides group rights.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */

/* user defined includes */

/**
 * The basic permission class provides user rights.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-17
 * @version 0.1
 */
require_once dirname(__FILE__).'/PermBasic.php';

/* user defined constants */

class PMF_PermMedium
    extends PMF_PermBasic
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute groups
     *
     * @access public
     * @var array
     */
    //var $groups = array();

    /**
     * Short description of attribute group_rights
     *
     * @access private
     * @var array
     */
    //var $_group_rights = array();

    /**
     * default_group_data
     *
     * Default data for new groups.
     *
     * @access public
     * @var array
     */
    var $default_group_data = array(
        'name' => 'DEFAULT_GROUP',
        'description' => 'Short group description. ',
        'auto_join' => false
    );

    // --- OPERATIONS ---

    /**
     * checkGroupRight
     *
     * Returns true if the group specified by $group_id owns the
     * right given by $right_id, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function checkGroupRight($group_id, $right_id)
    {
        if (!$this->_initialized)
        	return false;
        // check input
        if ($right_id <= 0 or $group_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".PMF_USER_SQLPREFIX."right.right_id AS right_id
            FROM
                ".PMF_USER_SQLPREFIX."right,
                ".PMF_USER_SQLPREFIX."group_right,
                ".PMF_USER_SQLPREFIX."group
            WHERE
                ".PMF_USER_SQLPREFIX."right.right_id = ".$right_id." AND
                ".PMF_USER_SQLPREFIX."right.right_id = ".PMF_USER_SQLPREFIX."group_right.right_id AND
                ".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."group_right.group_id AND
                ".PMF_USER_SQLPREFIX."group.group_id = '".$group_id."'
        ");
        // return result
        if ($this->_db->num_rows($res) == 1)
            return true;
        return false;
    }

    /**
     * getGroupRights
     *
     * Returns an array that contains the right-IDs of all
     * group-rights the group $group_id owns.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getGroupRights($group_id)
    {
        if (!$this->_initialized)
        	return false;
        if ($group_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".PMF_USER_SQLPREFIX."right.right_id AS right_id
            FROM
                ".PMF_USER_SQLPREFIX."right,
                ".PMF_USER_SQLPREFIX."group_right,
                ".PMF_USER_SQLPREFIX."group
            WHERE
                ".PMF_USER_SQLPREFIX."group.group_id = '".$group_id."' AND
                ".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."group_right.group_id AND
                ".PMF_USER_SQLPREFIX."right.right_id = ".PMF_USER_SQLPREFIX."group_right.right_id
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['right_id'];
        }
        return $result;
    }

    /**
     * checkRight
     *
     * Returns true, if the user given by $user_id owns the right
     * specified by $right. It does not matter if the user owns this
     * right as a user-right or because of a group-membership.
     * The parameter $right may be a right-ID (recommended for
     * performance) or a right-name.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param mixed
     * @return bool
     */
    function checkRight($user_id, $right)
    {
        // get right id
        if (!is_numeric($right) and is_string($right))
            $right = $this->getRightId($right);
        // check user right and group right
        if ($this->checkUserGroupRight($user_id, $right) or $this->checkUserRight($user_id, $right))
            return true;
        return false;
    }

    /**
     * PMF_PermMedium
     *
     * Constructor.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_PermMedium()
    {
    }

    /**
     * __destruct
     *
     * Destructor.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __destruct()
    {
    }

    /**
     * grantGroupRight
     *
     * Grants the group given by $group_id the right specified by
     * $right_id.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function grantGroupRight($group_id, $right_id)
    {
        if (!$this->_initialized)
        	return false;
        // check input
        if ($right_id <= 0 or $group_id <= 0)
            return false;
        // is right for users?
        $right_data = $this->getRightData($right_id);
        if (!$right_data['for_groups'])
            return false;
        // grant right
        $res = $this->_db->query("
            INSERT INTO
                ".PMF_USER_SQLPREFIX."group_right
            SET
                group_id = ".$group_id.",
                right_id = ".$right_id
        );
        if (!$res) 
            return false;
        return true;
    }

    /**
     * refuseGroupRight
     *
     * Refuses the group given by $group_id the right specified by
     * $right_id.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function refuseGroupRight($group_id, $right_id)
    {
        if (!$this->_initialized)
        	return false;
        // check input
        if ($right_id <= 0 or $group_id <= 0)
            return false;
        // grant right
        $res = $this->_db->query("
            DELETE FROM
                ".PMF_USER_SQLPREFIX."group_right
            WHERE
                group_id = ".$group_id." AND
                right_id = ".$right_id
        );
        if (!$res) 
            return false;
        return true;
    }

    /**
     * addGroup
     *
     * Adds a new group to the database and returns the ID of the
     * new group. The associative array $group_data contains the
     * data for the new group.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @return int
     */
    function addGroup($group_data)
    {
        if (!$this->_initialized)
        	return 0;
        // check if group already exists
        if ($this->getGroupId($group_data['name']) > 0)
            return 0;
        // get next id
		$next_id = $this->_db->nextID(PMF_USER_SQLPREFIX."group", "group_id");
        // check group data input
        $group_data = $this->checkGroupData($group_data);
        // insert group
        $res = $this->_db->query("
            INSERT INTO
                ".PMF_USER_SQLPREFIX."group
            SET
                group_id    = ".$next_id.",
                name        = '".$group_data['name']."',
                description = '".$group_data['description']."',
                auto_join   = '".$this->bool_to_int($group_data['auto_join'])."'
        ");
        if (!$res) 
            return 0;
        return $next_id;
    }

    /**
     * changeGroup
     *
     * Changes the group data of the given group.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param array
     * @return bool
     */
    function changeGroup($group_id, $group_data)
    {
        if (!$this->_initialized)
        	return false;
        // check input
        $checked_data = $this->checkGroupData($group_data);
        // create update SET
        $set = "";
        $comma = "";
        foreach ($group_data as $key => $val) {
            $set .= $comma.$key." = '".$checked_data[$key]."'";
            $comma = ",\n                ";
        }
        // update group
        $res = $this->_db->query("
            UPDATE
                ".PMF_USER_SQLPREFIX."group
            SET
                ".$set."
            WHERE
                group_id = ".$group_id
        );
        if (!$res) 
            return false;
        return true;
    }

    /**
     * deleteGroup
     *
     * Removes the group given by $group_id from the database.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function deleteGroup($group_id)
    {
        if (!$this->_initialized)
        	return false;
        // delete group
        $res = $this->_db->query("
            DELETE FROM
                ".PMF_USER_SQLPREFIX."group
            WHERE
                group_id = ".$group_id
        );
        if (!$res) 
            return false;
        // delete group-user links
        $res = $this->_db->query("
            DELETE FROM
                ".PMF_USER_SQLPREFIX."user_group
            WHERE
                group_id = ".$group_id
        );
        if (!$res) 
            return false;
        // delete group-right links
        $res = $this->_db->query("
            DELETE FROM
                ".PMF_USER_SQLPREFIX."group_right
            WHERE
                group_id = ".$group_id."
                
        ");
        if (!$res) 
            return false;
        return true;
    }

    /**
     * isGroupMember
     *
     * Returns true if the user given by $user_id is a member of
     * the group specified by $group_id, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function isGroupMember($user_id, $group_id)
    {
        if (!$this->_initialized)
        	return false;
        $res = $this->_db->query("
            SELECT
                ".PMF_USER_SQLPREFIX."user.user_id AS user_id
            FROM
                ".PMF_USER_SQLPREFIX."user,
                ".PMF_USER_SQLPREFIX."user_group,
                ".PMF_USER_SQLPREFIX."group
            WHERE
            	".PMF_USER_SQLPREFIX."user.user_id   = ".$user_id." AND
            	".PMF_USER_SQLPREFIX."user.user_id   = ".PMF_USER_SQLPREFIX."user_group.user_id AND
            	".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."user_group.group_id AND
            	".PMF_USER_SQLPREFIX."group.group_id = ".$group_id
        );
        if ($this->_db->num_rows($res) == 1)
        	return true;
        return false;
    }

    /**
     * getGroupMembers
     *
     * Returns an array that contains the user-IDs of all members
     * of the group $group_id.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getGroupMembers($group_id)
    {
        if (!$this->_initialized)
        	return false;
        $res = $this->_db->query("
            SELECT
                ".PMF_USER_SQLPREFIX."user.user_id AS user_id
            FROM
                ".PMF_USER_SQLPREFIX."user,
                ".PMF_USER_SQLPREFIX."user_group,
                ".PMF_USER_SQLPREFIX."group
            WHERE
            	".PMF_USER_SQLPREFIX."group.group_id = ".$group_id." AND
            	".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."user_group.group_id AND
            	".PMF_USER_SQLPREFIX."user.user_id   = ".PMF_USER_SQLPREFIX."user_group.user_id
        ");
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['user_id'];
        }
        return $result;
    }

    /**
     * addToGroup
     *
     * Adds a new member $user_id to the group $group_id.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function addToGroup($user_id, $group_id)
    {
        if (!$this->_initialized)
        	return false;
        // check group
        if (!$this->getGroupData($group_id))
        	return false;
        // add user to group
        $res = $this->_db->query("
        	INSERT INTO
        		".PMF_USER_SQLPREFIX."user_group
        	SET
        		user_id  = ".$user_id.",
        		group_id = ".$group_id
        );
        // return
        if (!$res)
        	return false;
        return true;
    }

    /**
     * removeFromGroup
     *
     * Removes a user $user_id from the group $group_id.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function removeFromGroup($user_id, $group_id)
    {
        if (!$this->_initialized)
        	return false;
        // check input
        if ($user_id <= 0 or $group_id <= 0)
            return false;
        // remove user from group
        $res = $this->_db->query("
        	DELETE FROM
        		".PMF_USER_SQLPREFIX."user_group
        	WHERE
        		user_id  = ".$user_id." AND
        		group_id = ".$group_id
        );
        // return
        if (!$res)
        	return false;
        return true;
    }

    /**
     * getGroupId
     *
     * Returns the ID of the group that has the name $name. Returns
     * 0 if the group-name cannot be found.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return int
     */
    function getGroupId($name)
    {
        if (!$this->_initialized)
        	return false;
        // get group id
        $res = $this->_db->query("
        	SELECT 
        		group_id
        	FROM
        		".PMF_USER_SQLPREFIX."group
        	WHERE
        		name = '".$name."'
        ");
        // return
        if ($this->_db->num_rows($res) != 1)
        	return 0;
        $row = $this->_db->fetch_assoc($res);
        return $row['group_id'];
    }

    /**
     * getGroupData
     *
     * Returns an associative array with the group-data of the group
     * $group_id.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getGroupData($group_id)
    {
        if (!$this->_initialized)
        	return false;
        // get group data
        $res = $this->_db->query("
        	SELECT 
        		group_id,
        		name,
        		description,
        		auto_join
        	FROM
        		".PMF_USER_SQLPREFIX."group
        	WHERE
        		group_id = ".$group_id
        );
        // return
        if ($this->_db->num_rows($res) != 1)
        	return array();
        return $this->_db->fetch_assoc($res);
    }

    /**
     * getUserGroups
     *
     * Returns an array that contains the IDs of all groups in which
     * the user $user_id is a member.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getUserGroups($user_id)
    {
        if (!$this->_initialized)
        	return false;
        // get user groups
        $res = $this->_db->query("
            SELECT
                ".PMF_USER_SQLPREFIX."group.group_id AS group_id
            FROM
                ".PMF_USER_SQLPREFIX."user,
                ".PMF_USER_SQLPREFIX."user_group,
                ".PMF_USER_SQLPREFIX."group
            WHERE
            	".PMF_USER_SQLPREFIX."user.user_id   = ".$user_id." AND
            	".PMF_USER_SQLPREFIX."user.user_id   = ".PMF_USER_SQLPREFIX."user_group.user_id AND
            	".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."user_group.group_id
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['group_id'];
        }
        return $result;
    }

    /**
     * getAllGroups
     *
     * Returns an array with the IDs of all groups stored in the
     * database.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getAllGroups()
    {
        if (!$this->_initialized)
        	return false;
        // get all groups
        $res = $this->_db->query("
            SELECT
                group_id
            FROM
                ".PMF_USER_SQLPREFIX."group
            WHERE
            	1
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['group_id'];
        }
        return $result;
    }

    /**
     * checkUserGroupRight
     *
     * Returns true if the user $user_id owns the right $right_id
     * because of a group-membership, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return void
     */
    function checkUserGroupRight($user_id, $right_id)
    {
        if (!$this->_initialized)
        	return false;
        // check right id
        if ($right_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".PMF_USER_SQLPREFIX."right.right_id AS right_id
            FROM
                ".PMF_USER_SQLPREFIX."right,
                ".PMF_USER_SQLPREFIX."group_right,
                ".PMF_USER_SQLPREFIX."group,
                ".PMF_USER_SQLPREFIX."user_group,
                ".PMF_USER_SQLPREFIX."user
            WHERE
                ".PMF_USER_SQLPREFIX."right.right_id = ".$right_id." AND
                ".PMF_USER_SQLPREFIX."right.right_id = ".PMF_USER_SQLPREFIX."group_right.right_id AND
                ".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."group_right.group_id AND
                ".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."user_group.group_id AND
                ".PMF_USER_SQLPREFIX."user.user_id   = ".PMF_USER_SQLPREFIX."user_group.user_id AND
                ".PMF_USER_SQLPREFIX."user.user_id   = ".$user_id
        );
        // return result
        if ($this->_db->num_rows($res) == 1)
            return true;
        return false;
    }

    /**
     * checkGroupData
     *
     * Checks the given associative array $group_data. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->default_group_data.
     * Returns the corrected $group_data associative array.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @return array
     */
    function checkGroupData($group_data)
    {
        if (!isset($group_data['name']) or !is_string($group_data['name']))
            $group_data['name'] = $this->default_group_data['name'];
        if (!isset($group_data['description']) or !is_string($group_data['description']))
            $group_data['description'] = $this->default_group_data['description'];
        if (!isset($group_data['auto_join']))
            $group_data['auto_join'] = $this->default_group_data['auto_join'];
        $group_data['auto_join'] = $this->bool_to_int($group_data['auto_join']);
        return $group_data;
    }

    /**
     * getAllUserRights
     *
     * Returns an array that contains the right-IDs of all rights
     * the user $user_id owns. User-rights and the rights the user
     * owns because of a group-membership are taken into account.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getAllUserRights($user_id)
    {
        if (!$this->_initialized)
            return false;
        // check input
        if ($user_id <= 0)
            return false;
        $user_rights  = $this->getUserRights($user_id);
        $group_rights = $this->getUserGroupRights($user_id);
        return array_unique(array_merge($user_rights, $group_rights));
    }

    /**
     * autoJoin
     *
     * Adds the user $user_id to all groups with the auto_join
     * option. By using the auto_join option, user administration
     * can be much easier. For example by setting this option only
     * for a single group called 'All Users'. The autoJoin() method
     * then has to be called every time a new user registers.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function autoJoin($user_id)
    {
        if (!$this->_initialized)
            return false;
        // check user id
        if ($user_id <= 0)
            return false;
        // get auto join groups
        $res = $this->_db->query("
            SELECT
                group_id
            FROM
                ".PMF_USER_SQLPREFIX."group
            WHERE
                auto_join = 1
        ");
        if (!$res)
            return false;
        $auto_join = array();
        while ($row = $this->_db->fetch_assoc($res)) {
            $auto_join[] = $row['group_id'];
        }
        // add to groups
        foreach ($auto_join as $group_id) {
            $this->addToGroup($user_id, $group_id);
        }
        return true;
    }

    /**
     * removeFromAllGroups
     *
     * Removes the user $user_id from all groups.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function removeFromAllGroups($user_id)
    {
        if (!$this->_initialized)
        	return false;
        // check input
        if ($user_id <= 0)
            return false;
        // remove user from all groups
        $res = $this->_db->query("
        	DELETE FROM
        		".PMF_USER_SQLPREFIX."user_group
        	WHERE
        		user_id  = ".$user_id
        );
        // return
        if (!$res)
        	return false;
        return true;
    }

    /**
     * getUserGroupRights
     *
     * Returns an array that contains the IDs of all rights the user
     * $user_id owns because of a group-membership.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getUserGroupRights($user_id)
    {
        if (!$this->_initialized)
        	return false;
        if ($user_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".PMF_USER_SQLPREFIX."right.right_id AS right_id
            FROM
                ".PMF_USER_SQLPREFIX."right,
                ".PMF_USER_SQLPREFIX."group_right,
                ".PMF_USER_SQLPREFIX."group,
                ".PMF_USER_SQLPREFIX."user_group,
                ".PMF_USER_SQLPREFIX."user
            WHERE
                ".PMF_USER_SQLPREFIX."user.user_id   = ".$user_id." AND
                ".PMF_USER_SQLPREFIX."user.user_id   = ".PMF_USER_SQLPREFIX."user_group.user_id AND
                ".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."user_group.group_id AND
                ".PMF_USER_SQLPREFIX."group.group_id = ".PMF_USER_SQLPREFIX."group_right.group_id AND
                ".PMF_USER_SQLPREFIX."right.right_id = ".PMF_USER_SQLPREFIX."group_right.right_id
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['right_id'];
        }
        return $result;
    }

    /**
     * refuseAllGroupRights
     *
     * Refuses all group rights.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function refuseAllGroupRights($group_id)
    {
        if (!$this->_initialized)
        	return false;
        $res = $this->_db->query("
            DELETE FROM
                ".PMF_USER_SQLPREFIX."group_right
            WHERE
                group_id  = ".$group_id
        );
        if (!$res)
            return false;
        return true;
    }

    /**
     * getGroupName
     *
     * Returns the name of the group $group_id.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getGroupName($group_id)
    {
        if (!$this->_initialized)
        	return false;
        // get group data
        $res = $this->_db->query("
        	SELECT
        		name
        	FROM
        		".PMF_USER_SQLPREFIX."group
        	WHERE
        		group_id = ".$group_id
        );
        // return
        if ($this->_db->num_rows($res) != 1)
        	return array();
        $row = $this->_db->fetch_assoc($res);
        return $row['name'];
    }

    /**
     * removeAllUsersFromGroup
     *
     * Removes all users from the group $group_id.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function removeAllUsersFromGroup($group_id)
    {
        if (!$this->_initialized)
        	return false;
        // check input
        if ($group_id <= 0)
            return false;
        // remove all user from group
        $res = $this->_db->query("
        	DELETE FROM
        		".PMF_USER_SQLPREFIX."user_group
        	WHERE
        		group_id = ".$group_id
        );
        // return
        if (!$res)
        	return false;
        return true;
    }

} /* end of class PMF_PermMedium */

?>
