<?php

error_reporting(E_ALL);

/**
 * The basic permission class provides user rights.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-17
 * @version 0.1
 */

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

/**
 * This class manages user permissions and group memberships.
 *
 * There are three possible extensions of this class: basic, medium and large
 * by the classes PMF_PermBasic, PMF_PermMedium and PMF_PermLarge. The classes
 * to allow for scalability. This means that PMF_PermMedium is an extend of
 * and PMF_PermLarge is an extend of PMF_PermMedium. The PMF_Perm class itself
 * not provide any methods, but a single property: the database object
 * Using this database connection, the permission-object may perform database
 * The permission object is added to a user using the user's addPerm() method.
 * a single permission-object is allowed for each user. The permission-object is
 * in the user's $perm variable. Permission methods are performed using the
 * variable (e.g. $user->perm->method() ).
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-17
 * @version 0.1
 */
require_once('PMF/Perm.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fe2-includes begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fe2-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fe2-constants begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fe2-constants end

/**
 * The basic permission class provides user rights.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-17
 * @version 0.1
 */
class PMF_PermBasic
    extends PMF_Perm
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute user_rights
     *
     * @access private
     * @var array
     */
    var $_user_rights = array();

    // --- OPERATIONS ---

    /**
     * Short description of method checkUserRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function checkUserRight($name)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:10637248df4:-7ffe begin
        if (!$this->_initialized)
        	return false;
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
                ".SQLPREFIX."user_right,
                ".SQLPREFIX."user
            WHERE
                ".SQLPREFIX."right.right_id = '".$right_id."' AND
                ".SQLPREFIX."right.right_id = ".SQLPREFIX."user_right.right_id AND
                ".SQLPREFIX."user.user_id   = '".$this->_user_id."' AND
                ".SQLPREFIX."user.user_id   = ".SQLPREFIX."user_right.user_id
        ");
        // return result
        if ($this->_db->num_rows($res) == 1) 
            return true;
        return false;
        // section -64--88-1-5-15e2075:10637248df4:-7ffe end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getUserRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getUserRights()
    {
        $returnValue = array();

        // section -64--88-1-5-15e2075:10637248df4:-7fa5 begin
        if (!$this->_initialized)
        	return false;
        // get user rights
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."right.right_id AS right_id
            FROM
                ".SQLPREFIX."right,
                ".SQLPREFIX."user_right,
                ".SQLPREFIX."user
            WHERE
                ".SQLPREFIX."right.right_id = ".SQLPREFIX."user_right.right_id AND
                ".SQLPREFIX."user.user_id   = '".$this->_user_id."' AND
                ".SQLPREFIX."user.user_id   = ".SQLPREFIX."user_right.user_id
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['right_id'];
        }
        return $result;
        // section -64--88-1-5-15e2075:10637248df4:-7fa5 end

        return (array) $returnValue;
    }

    /**
     * Short description of method PMF_PermBasic
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_PermBasic()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd7 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd7 end
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
        // section -64--88-1-10-16f8da9f:106d096e725:-7fdb begin
        // section -64--88-1-10-16f8da9f:106d096e725:-7fdb end
    }

    /**
     * Short description of method grantUserRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return void
     */
    function grantUserRight($right_id)
    {
        // section -64--88-1-10--2ab496b6:106d484ef91:-7fdc begin
        if (!$this->_initialized)
        	return false;
        $res = $this->_db->query("
            INSERT INTO
                ".SQLPREFIX."user_right
            SET
                user_id  = '".$this->_user_id."',
                right_id = '".$right_id."'
        ");
        if (!$res) 
            return false;
        return true;
        // section -64--88-1-10--2ab496b6:106d484ef91:-7fdc end
    }

    /**
     * Short description of method refuseUserRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return void
     */
    function refuseUserRight($right_id)
    {
        // section -64--88-1-10--2ab496b6:106d484ef91:-7fd7 begin
        if (!$this->_initialized)
        	return false;
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."user_right
            WHERE
                user_id  = '".$this->_user_id."' AND
                right_id = '".$right_id."'
        ");
        // section -64--88-1-10--2ab496b6:106d484ef91:-7fd7 end
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

        // section -64--88-1-10--785a539b:106d9d6c253:-7fd8 begin
        return $this->checkUserRight($name);
        // section -64--88-1-10--785a539b:106d9d6c253:-7fd8 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getRightData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getRightData($right_id)
    {
        $returnValue = array();

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fbd begin
        if (!$this->_initialized)
        	return false;
        // get right data
        $res = $this->_db->query("
            SELECT 
                right_id,
                name,
                description,
                for_users,
                for_groups
            FROM
                ".SQLPREFIX."right
            WHERE
                right_id = '".$right_id."'
        ");
        if ($this->_db->num_rows($res) != 1) 
            return false;
        // process right data
        $right_data = $this->_db->fetch_assoc($res);
        $right_data['for_users'] = $this->int_to_bool($right_data['for_users']);
        $right_data['for_groups'] = $this->int_to_bool($right_data['for_groups']);
        return $right_data;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fbd end

        return (array) $returnValue;
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

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fb4 begin
        return $this->getUserRights();
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7fb4 end

        return (array) $returnValue;
    }

    /**
     * Short description of method addRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @param array
     * @return int
     */
    function addRight($right_data, $context_data = array())
    {
        $returnValue = (int) 0;

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f9c begin
        if (!$this->_initialized)
        	return false;
        // check if right already exists
        if ($this->getRightId($right_data['name']) > 0)
            return false;
        // get next id
		$next_id = $this->_db->nextID(SQLPREFIX."right", "right_id");
        // check right data input
        $right_data = $this->checkRightData($right_data);
        // insert right
        $res = $this->_db->query("
            INSERT INTO
                ".SQLPREFIX."right
            SET
                right_id    = '".$next_id."',
                name        = '".$right_data['name']."',
                description = '".$right_data['description']."',
                for_users   = '".$this->bool_to_int($right_data['for_users'])."',
                for_groups  = '".$this->bool_to_int($right_data['for_groups'])."'
        ");
        if (!$res) 
            return 0;
        // insert context data
        if (count($context_data) > 0) {
        	$res = $this->_db->query("
        		INSERT INTO
        			".SQLPREFIX."rightcontext
        		SET
        			right_id   = '".$next_id."',
        			context    = '".$context_data['context']."',
        			context_id = '".$context_data['context_id']."'
        	");
        	if (!$res)
        		return 0;
        }
        return $next_id;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f9c end

        return (int) $returnValue;
    }

    /**
     * Short description of method changeRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param array
     * @param array
     * @return bool
     */
    function changeRight($right_id, $right_data, $context_data = array())
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f99 begin
        if (!$this->_initialized)
        	return false;
        // check input
        $right_data = $this->checkRightData($right_data);
        // update right
        $res = $this->_db->query("
            UPDATE
                ".SQLPREFIX."right
            SET
                name        = '".$right_data['name']."',
                description = '".$right_data['description']."',
                for_users   = '".$this->bool_to_int($right_data['for_users'])."',
                for_groups  = '".$this->bool_to_int($right_data['for_groups'])."'
            WHERE
                right_id = '".$right_id."'
        ");
        if (!$res) 
            return false;
        // change right context
        if (count($context_data) > 0) {
        	$res = $this->_db->query("
        		UPDATE
        			".SQLPREFIX."rightcontext
        		SET
        			context    = '".$context_data['context']."',
        			context_id = '".$context_data['context_id']."'
        		WHERE
        			right_id = '".$right_id."'
        	");
        	if (!$res)
        		return false;
        }
        return true;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f99 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method deleteRight
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function deleteRight($right_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f95 begin
        if (!$this->_initialized)
        	return false;
        // delete right
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."right
            WHERE
                right_id = '".$right_id."'
        ");
        if (!$res) 
            return false;
        // delete user-right links
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."user_right
            WHERE
                right_id = '".$right_id."'
        ");
        if (!$res) 
            return false;
        // delete group-right links
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."group_right
            WHERE
                right_id = '".$right_id."'
        ");
        if (!$res) 
            return false;
        // delete right context
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."rightcontext
            WHERE
                right_id = '".$right_id."'
        ");
        if (!$res) 
            return false;
        return true;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f95 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getRightId
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return int
     */
    function getRightId($name)
    {
        $returnValue = (int) 0;

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f92 begin
        if (!$this->_initialized)
        	return false;
        // get right id
        $res = $this->_db->query("
            SELECT
                right_id
            FROM
                ".SQLPREFIX."right
            WHERE 
                name = '".$name."'
        ");
        // return result
        if ($this->_db->num_rows($res) != 1)
            return 0;
        $row = $this->_db->fetch_assoc($res);
        return $row['right_id'];
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f92 end

        return (int) $returnValue;
    }

    /**
     * Short description of method getAllRights
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getAllRights()
    {
        $returnValue = array();

        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f8f begin
        if (!$this->_initialized)
        	return false;
        $res = $this->_db->query("
            SELECT
                right_id
            FROM
                ".SQLPREFIX."right
            WHERE
                1
        ");
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
        	$result[] = $row['right_id'];
        }
        return $result;
        // section -64--88-1-10--61674be4:106dbb8e5aa:-7f8f end

        return (array) $returnValue;
    }

    /**
     * Short description of method checkRightData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @return array
     */
    function checkRightData($right_data)
    {
        $returnValue = array();

        // section 127-0-0-1--6945df47:106df4af666:-7fd2 begin
        if (!isset($right_data['name']) or !is_string($right_data['description']))
            $right_data['name'] = '';
        if (!isset($right_data['description']) or !is_string($right_data['description']))
            $right_data['description'] = '';
        if (!isset($right_data['for_users'])) 
            $right_data['for_users'] = true;
        if (!isset($right_data['for_groups']))
            $right_data['for_groups'] = true;
        $right_data['for_users'] = $this->bool_to_int($right_data['for_users']);
        $right_data['for_groups'] = $this->bool_to_int($right_data['for_groups']);
        return $right_data;
        // section 127-0-0-1--6945df47:106df4af666:-7fd2 end

        return (array) $returnValue;
    }

} /* end of class PMF_PermBasic */

?>