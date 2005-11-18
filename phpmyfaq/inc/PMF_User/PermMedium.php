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

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

/**
 * The basic permission class provides user rights.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-17
 * @version 0.1
 */
require_once('PMF/PermBasic.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fde-includes begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fde-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fde-constants begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fde-constants end

/**
 * The medium permission class provides group rights.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
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
    var $groups = array();

    /**
     * Short description of attribute group_rights
     *
     * @access private
     * @var array
     */
    var $_group_rights = array();

    /**
     * Short description of attribute default_group_data
     *
     * @access public
     * @var array
     */
    var $default_group_data = array('name' => 'DEFAULT_GROUP', 'description' => 'Short group description. ', 'auto_join' => false);

    // --- OPERATIONS ---

    /**
     * Short description of method checkGroupRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function checkGroupRight($group_id, $right_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7ffa begin
        if (!$this->_initialized)
        	return false;
        // check input
        if ($right_id <= 0 or $group_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."right.right_id AS right_id
            FROM
                ".SQLPREFIX."right,
                ".SQLPREFIX."group_right,
                ".SQLPREFIX."group
            WHERE
                ".SQLPREFIX."right.right_id = '".$right_id."' AND
                ".SQLPREFIX."right.right_id = ".SQLPREFIX."group_right.right_id AND
                ".SQLPREFIX."group.group_id = ".SQLPREFIX."group_right.group_id AND
                ".SQLPREFIX."group.group_id   = '".$group_id."'
        ");
        // return result
        if ($this->_db->num_rows($res) == 1)
            return true;
        return false;
        // section -64--88-1-5-15e2075:10637248df4:-7ffa end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getGroupRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getGroupRights($group_id)
    {
        $returnValue = array();

        // section -64--88-1-5-15e2075:10637248df4:-7fbe begin
        if (!$this->_initialized)
        	return false;
        if ($group_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."right.right_id AS right_id
            FROM
                ".SQLPREFIX."right,
                ".SQLPREFIX."group_right,
                ".SQLPREFIX."group
            WHERE
                ".SQLPREFIX."group.group_id   = '".$group_id."' AND
                ".SQLPREFIX."group.group_id = ".SQLPREFIX."group_right.group_id AND
                ".SQLPREFIX."right.right_id = ".SQLPREFIX."group_right.right_id
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['right_id'];
        }
        return $result;
        // section -64--88-1-5-15e2075:10637248df4:-7fbe end

        return (array) $returnValue;
    }

    /**
     * Short description of method checkRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param mixed
     * @return bool
     */
    function checkRight($user_id, $right)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7fb4 begin
        // get right id
        if (!is_numeric($right) and is_string($right))
            $right = $this->getRightId($right);
        // check user right and group right
        if ($this->checkUserRight($user_id, $right) or $this->checkUserGroupRight($user_id, $right))
            return true;
        return false;
        // section -64--88-1-5-15e2075:10637248df4:-7fb4 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method PMF_PermMedium
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_PermMedium()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd5 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd5 end
    }

    /**
     * Short description of method __destruct
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __destruct()
    {
        // section -64--88-1-10--2ab496b6:106d484ef91:-7fa7 begin
        // section -64--88-1-10--2ab496b6:106d484ef91:-7fa7 end
    }

    /**
     * Short description of method grantGroupRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function grantGroupRight($group_id, $right_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7fa5 begin
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
                ".SQLPREFIX."group_right
            SET
                group_id = '".$group_id."',
                right_id = '".$right_id."'
        ");
        if (!$res) 
            return false;
        return true;
        // section -64--88-1-10--2ab496b6:106d484ef91:-7fa5 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method refuseGroupRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function refuseGroupRight($group_id, $right_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7fa3 begin
        if (!$this->_initialized)
        	return false;
        // check input
        if ($right_id <= 0 or $group_id <= 0)
            return false;
        // grant right
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."group_right
            WHERE
                group_id = '".$group_id."' AND
                right_id = '".$right_id."'
        ");
        if (!$res) 
            return false;
        return true;
        // section -64--88-1-10--2ab496b6:106d484ef91:-7fa3 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method addGroup
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @return int
     */
    function addGroup($group_data)
    {
        $returnValue = (int) 0;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7f9d begin
        if (!$this->_initialized)
        	return 0;
        // check if group already exists
        if ($this->getGroupId($group_data['name']) > 0)
            return 0;
        // get next id
		$next_id = $this->_db->nextID(SQLPREFIX."group", "group_id");
        // check group data input
        $group_data = $this->checkGroupData($group_data);
        // insert group
        $res = $this->_db->query("
            INSERT INTO
                ".SQLPREFIX."group
            SET
                group_id    = '".$next_id."',
                name        = '".$group_data['name']."',
                description = '".$group_data['description']."',
                auto_join   = '".$this->bool_to_int($group_data['auto_join'])."'
        ");
        if (!$res) 
            return 0;
        return $next_id;
        // section -64--88-1-10--2ab496b6:106d484ef91:-7f9d end

        return (int) $returnValue;
    }

    /**
     * Short description of method changeGroup
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param array
     * @return bool
     */
    function changeGroup($group_id, $group_data)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7f99 begin
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
                ".SQLPREFIX."group
            SET
                ".$set."
            WHERE
                group_id = '".$group_id."'
        ");
        if (!$res) 
            return false;
        return true;
        // section -64--88-1-10--2ab496b6:106d484ef91:-7f99 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method deleteGroup
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function deleteGroup($group_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7f8d begin
        if (!$this->_initialized)
        	return false;
        // delete group
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."group
            WHERE
                group_id = '".$group_id."'
        ");
        if (!$res) 
            return false;
        // delete group-user links
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."user_group
            WHERE
                group_id = '".$group_id."'
        ");
        if (!$res) 
            return false;
        // delete group-right links
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."group_right
            WHERE
                group_id = '".$group_id."'
        ");
        if (!$res) 
            return false;
        return true;
        // section -64--88-1-10--2ab496b6:106d484ef91:-7f8d end

        return (bool) $returnValue;
    }

    /**
     * Short description of method isGroupMember
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function isGroupMember($user_id, $group_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--785a539b:106d9d6c253:-7fcc begin
        if (!$this->_initialized)
        	return false;
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."user.user_id AS user_id
            FROM
                ".SQLPREFIX."user,
                ".SQLPREFIX."user_group,
                ".SQLPREFIX."group
            WHERE
            	".SQLPREFIX."user.user_id   = '".$user_id."' AND
            	".SQLPREFIX."user.user_id   = ".SQLPREFIX."user_group.user_id AND
            	".SQLPREFIX."group.group_id = ".SQLPREFIX."user_group.group_id AND
            	".SQLPREFIX."group.group_id = '".$group_id."'
        ");
        if ($this->_db->num_rows($res) == 1)
        	return true;
        return false;
        // section -64--88-1-10--785a539b:106d9d6c253:-7fcc end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getGroupMembers
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getGroupMembers($group_id)
    {
        $returnValue = array();

        // section -64--88-1-10--785a539b:106d9d6c253:-7fc9 begin
        if (!$this->_initialized)
        	return false;
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."user.user_id AS user_id
            FROM
                ".SQLPREFIX."user,
                ".SQLPREFIX."user_group,
                ".SQLPREFIX."group
            WHERE
            	".SQLPREFIX."group.group_id = '".$group_id."' AND
            	".SQLPREFIX."group.group_id = ".SQLPREFIX."user_group.group_id AND
            	".SQLPREFIX."user.user_id   = ".SQLPREFIX."user_group.user_id
        ");
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['user_id'];
        }
        return $result;
        // section -64--88-1-10--785a539b:106d9d6c253:-7fc9 end

        return (array) $returnValue;
    }

    /**
     * Short description of method addToGroup
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function addToGroup($user_id, $group_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--785a539b:106d9d6c253:-7fc6 begin
        if (!$this->_initialized)
        	return false;
        // check group
        if (!$this->getGroupData($group_id))
        	return false;
        // add user to group
        $res = $this->_db->query("
        	INSERT INTO
        		".SQLPREFIX."user_group
        	SET
        		user_id  = '".$user_id."',
        		group_id = '".$group_id."'
        ");
        // return
        if (!$res)
        	return false;
        return true;
        // section -64--88-1-10--785a539b:106d9d6c253:-7fc6 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method removeFromGroup
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function removeFromGroup($user_id, $group_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--785a539b:106d9d6c253:-7fc3 begin
        if (!$this->_initialized)
        	return false;
        // check input
        if ($user_id <= 0 or $group_id <= 0)
            return false;
        // remove user from group
        $res = $this->_db->query("
        	DELETE FROM
        		".SQLPREFIX."user_group
        	WHERE
        		user_id  = '".$user_id."' AND
        		group_id = '".$group_id."'
        ");
        // return
        if (!$res)
        	return false;
        return true;
        // section -64--88-1-10--785a539b:106d9d6c253:-7fc3 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getGroupId
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return int
     */
    function getGroupId($name)
    {
        $returnValue = (int) 0;

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fcb begin
        if (!$this->_initialized)
        	return false;
        // get group id
        $res = $this->_db->query("
        	SELECT 
        		group_id
        	FROM
        		".SQLPREFIX."group
        	WHERE
        		name = '".$name."'
        ");
        // return
        if ($this->_db->num_rows($res) != 1)
        	return 0;
        $row = $this->_db->fetch_assoc($res);
        return $row['group_id'];
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fcb end

        return (int) $returnValue;
    }

    /**
     * Short description of method getGroupData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getGroupData($group_id)
    {
        $returnValue = array();

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fc8 begin
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
        		".SQLPREFIX."group
        	WHERE
        		group_id = '".$group_id."'
        ");
        // return
        if ($this->_db->num_rows($res) != 1)
        	return array();
        return $this->_db->fetch_assoc($res);
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fc8 end

        return (array) $returnValue;
    }

    /**
     * Short description of method getUserGroups
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getUserGroups($user_id)
    {
        $returnValue = array();

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fc5 begin
        if (!$this->_initialized)
        	return false;
        // get user groups
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."group.group_id AS group_id
            FROM
                ".SQLPREFIX."user,
                ".SQLPREFIX."user_group,
                ".SQLPREFIX."group
            WHERE
            	".SQLPREFIX."user.user_id   = '".$user_id."' AND
            	".SQLPREFIX."user.user_id   = ".SQLPREFIX."user_group.user_id AND
            	".SQLPREFIX."group.group_id = ".SQLPREFIX."user_group.group_id
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['group_id'];
        }
        return $result;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fc5 end

        return (array) $returnValue;
    }

    /**
     * Short description of method getAllGroups
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getAllGroups()
    {
        $returnValue = array();

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7faa begin
        if (!$this->_initialized)
        	return false;
        // get all groups
        $res = $this->_db->query("
            SELECT
                group_id
            FROM
                ".SQLPREFIX."group
            WHERE
            	1
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['group_id'];
        }
        return $result;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7faa end

        return (array) $returnValue;
    }

    /**
     * Short description of method checkUserGroupRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return void
     */
    function checkUserGroupRight($user_id, $right_id = 0)
    {
        // section -64--88-1-10--a35403d:1079da3e1d1:-7fd3 begin
        if (!$this->_initialized)
        	return false;
        // check right id
        if ($right_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."right.right_id AS right_id
            FROM
                ".SQLPREFIX."right,
                ".SQLPREFIX."group_right,
                ".SQLPREFIX."group,
                ".SQLPREFIX."user_group,
                ".SQLPREFIX."user
            WHERE
                ".SQLPREFIX."right.right_id = '".$right_id."' AND
                ".SQLPREFIX."right.right_id = ".SQLPREFIX."group_right.right_id AND
                ".SQLPREFIX."group.group_id = ".SQLPREFIX."group_right.group_id AND
                ".SQLPREFIX."group.group_id = ".SQLPREFIX."user_group.group_id AND
                ".SQLPREFIX."user.user_id   = ".SQLPREFIX."user_group.user_id AND
                ".SQLPREFIX."user.user_id   = '".$user_id."'
        ");
        // return result
        if ($this->_db->num_rows($res) == 1)
            return true;
        return false;
        // section -64--88-1-10--a35403d:1079da3e1d1:-7fd3 end
    }

    /**
     * Short description of method checkGroupData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @return array
     */
    function checkGroupData($group_data)
    {
        $returnValue = array();

        // section -64--88-1-10--a35403d:1079da3e1d1:-7fcf begin
        if (!isset($group_data['name']) or !is_string($group_data['name']))
            $group_data['name'] = $this->default_group_data['name'];
        if (!isset($group_data['description']) or !is_string($group_data['description']))
            $group_data['description'] = $this->default_group_data['description'];
        if (!isset($group_data['auto_join']))
            $group_data['auto_join'] = $this->default_group_data['auto_join'];
        $group_data['auto_join'] = $this->bool_to_int($group_data['auto_join']);
        return $group_data;
        // section -64--88-1-10--a35403d:1079da3e1d1:-7fcf end

        return (array) $returnValue;
    }

    /**
     * Short description of method getAllUserRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getAllUserRights($user_id)
    {
        $returnValue = array();

        // section -64--88-1-10--a35403d:1079da3e1d1:-7fcb begin
        if (!$this->_initialized)
            return false;
        // check input
        if ($user_id <= 0)
            return false;
        $user_rights  = $this->getUserRights($user_id);
        $group_rights = $this->getUserGroupRights($user_id);
        return array_unique(array_merge($user_rights, $group_rights));
        // section -64--88-1-10--a35403d:1079da3e1d1:-7fcb end

        return (array) $returnValue;
    }

    /**
     * Short description of method autoJoin
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function autoJoin($user_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--a35403d:1079da3e1d1:-7fc7 begin
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
                ".SQLPREFIX."group
            WHERE
                auto_join = '1'
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
        // section -64--88-1-10--a35403d:1079da3e1d1:-7fc7 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method removeFromAllGroups
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function removeFromAllGroups($user_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--a35403d:1079da3e1d1:-7fc4 begin
        if (!$this->_initialized)
        	return false;
        // check input
        if ($user_id <= 0)
            return false;
        // remove user from all groups
        $res = $this->_db->query("
        	DELETE FROM
        		".SQLPREFIX."user_group
        	WHERE
        		user_id  = '".$user_id."'
        ");
        // return
        if (!$res)
        	return false;
        return true;
        // section -64--88-1-10--a35403d:1079da3e1d1:-7fc4 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getUserGroupRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getUserGroupRights($user_id)
    {
        $returnValue = array();

        // section -64--88-1-10--a35403d:1079da3e1d1:-7fc1 begin
        if (!$this->_initialized)
        	return false;
        if ($user_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."right.right_id AS right_id
            FROM
                ".SQLPREFIX."right,
                ".SQLPREFIX."group_right,
                ".SQLPREFIX."group,
                ".SQLPREFIX."user_group,
                ".SQLPREFIX."user
            WHERE
                ".SQLPREFIX."user.user_id   = '".$user_id."' AND
                ".SQLPREFIX."user.user_id   = ".SQLPREFIX."user_group.user_id AND
                ".SQLPREFIX."group.group_id = ".SQLPREFIX."user_group.group_id AND
                ".SQLPREFIX."group.group_id = ".SQLPREFIX."group_right.group_id AND
                ".SQLPREFIX."right.right_id = ".SQLPREFIX."group_right.right_id
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['right_id'];
        }
        return $result;
        // section -64--88-1-10--a35403d:1079da3e1d1:-7fc1 end

        return (array) $returnValue;
    }

} /* end of class PMF_PermMedium */

?>
