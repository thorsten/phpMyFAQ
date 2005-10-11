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

    // --- OPERATIONS ---

    /**
     * Short description of method checkGroupRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return string
     */
    function checkGroupRight($name)
    {
        $returnValue = (string) '';

        // section -64--88-1-5-15e2075:10637248df4:-7ffa begin
        // get right id
        $right_id = $this->getRightId($name);
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
                ".SQLPREFIX."user.user_id   = '".$this->_user_id."'
        ");
        // return result
        if ($this->_db->num_rows($res) == 1) 
            return true;
        return false;
        // section -64--88-1-5-15e2075:10637248df4:-7ffa end

        return (string) $returnValue;
    }

    /**
     * Short description of method getGroupRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getGroupRights()
    {
        $returnValue = array();

        // section -64--88-1-5-15e2075:10637248df4:-7fbe begin
        // get right id
        $right_id = $this->getRightId($name);
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
                ".SQLPREFIX."user.user_id   = '".$this->_user_id."' AND
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
        // section -64--88-1-5-15e2075:10637248df4:-7fbe end

        return (array) $returnValue;
    }

    /**
     * Short description of method checkRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function checkRight($name)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7fb4 begin
        if ($this->checkUserRight($name) or $this->checkGroupRight($name))
            return true;
        return false;
        // section -64--88-1-5-15e2075:10637248df4:-7fb4 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getRights()
    {
        $returnValue = array();

        // section -64--88-1-5-15e2075:10637248df4:-7fa3 begin
        $user_rights  = $this->getUserRights();
        $group_rights = $this->getGroupRights();
        $rights = array_merge($user_rights, $group_rights);
        return array_unique($rights);
        // section -64--88-1-5-15e2075:10637248df4:-7fa3 end

        return (array) $returnValue;
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
     * @param string
     * @return bool
     */
    function grantGroupRight($right_id, $group_name)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7fa5 begin
        // get group id
        $group_id = $this->getGroupId($name);
        if ($group_id == 0) 
            return false;
        // grant right
        $res = $this->_db->query("
            INSERT INTO
                ".SQLPREFIX."group_right
            SET
                user_id  = '".$group_id."',
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
     * @param string
     * @return bool
     */
    function refuseGroupRight($right_id, $group_name)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7fa3 begin
        // get group id
        $group_id = $this->getGroupId($group_name);
        if ($group_id == 0) 
            return false;
        // grant right
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."group_right
            WHERE
                user_id  = '".$group_id."' AND
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
     * @return object
     */
    function addGroup($group_data)
    {
        $returnValue = null;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7f9d begin
        // check if group already exists
        if ($this->getGroupId($group_data['name']) > 0)
            return false;
        // get next id
		$next_id = $this->_db->nextID(SQLPREFIX."group", "group_id");
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

        return $returnValue;
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
        // update group
        $res = $this->_db->query("
            UPDATE
                ".SQLPREFIX."group
            SET
                name        = '".$group_data['name']."',
                description = '".$group_data['description']."',
                auto_join   = '".$this->bool_to_int($group_data['auto_join'])."'
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
     * @return object
     */
    function deleteGroup($group_id)
    {
        $returnValue = null;

        // section -64--88-1-10--2ab496b6:106d484ef91:-7f8d begin
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

        return $returnValue;
    }

    /**
     * Short description of method isGroupMember
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function isGroupMember($group_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--785a539b:106d9d6c253:-7fcc begin
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
        // section -64--88-1-10--785a539b:106d9d6c253:-7fc9 end

        return (array) $returnValue;
    }

    /**
     * Short description of method addToGroup
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function addToGroup($group_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--785a539b:106d9d6c253:-7fc6 begin
        // section -64--88-1-10--785a539b:106d9d6c253:-7fc6 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method removeFromGroup
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function removeFromGroup($group_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--785a539b:106d9d6c253:-7fc3 begin
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
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fc8 end

        return (array) $returnValue;
    }

    /**
     * Short description of method getGroups
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getGroups()
    {
        $returnValue = array();

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fc5 begin
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
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7faa end

        return (array) $returnValue;
    }

} /* end of class PMF_PermMedium */

?>