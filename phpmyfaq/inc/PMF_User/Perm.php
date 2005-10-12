<?php

error_reporting(E_ALL);

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
 * @package PMF
 * @since 2005-09-17
 * @version 0.1
 */

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

/**
 * Creates a new user object.
 *
 * A user are recognized by the session-id using getUserBySessionId(), by his
 * using getUserById() or by his nickname (login) using getUserByLogin(). New
 * are created using createNewUser().
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-17
 * @version 0.1
 */
require_once('PMF/User.php');

/* user defined includes */
// section -64--88-1-5-15e2075:10637248df4:-7fd0-includes begin
// section -64--88-1-5-15e2075:10637248df4:-7fd0-includes end

/* user defined constants */
// section -64--88-1-5-15e2075:10637248df4:-7fd0-constants begin
// section -64--88-1-5-15e2075:10637248df4:-7fd0-constants end

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
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-17
 * @version 0.1
 */
class PMF_Perm
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute db
     *
     * @access private
     * @var object
     */
    var $_db = null;

    /**
     * Short description of attribute user_id
     *
     * @access private
     * @var int
     */
    var $_user_id = 0;

    /**
     * Short description of attribute perm_typemap
     *
     * @access private
     * @var array
     */
    var $_perm_typemap = array('basic' => 'PermBasic', 'medium' => 'PermMedium', 'large' => 'PermLarge');

    // --- OPERATIONS ---

    /**
     * Short description of method PMF_Perm
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return void
     */
    function PMF_Perm($perm_level)
    {
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd1 begin
        return $this->selectPerm($perm_level);
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd1 end
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
        // section -64--88-1-10-16f8da9f:106d096e725:-7fdd begin
        // section -64--88-1-10-16f8da9f:106d096e725:-7fdd end
    }

    /**
     * Short description of method selectPerm
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return object
     */
    function selectPerm($perm_level)
    {
        $returnValue = null;

        // section -64--88-1-10--77ac1b05:106d99eac38:-7fab begin
        // verify selected database
        $perm = new PMF_Perm();
        $perm_level = strtolower($perm_level);
        if (!isset($perm->_auth_typemap[$perm_level])) {
            return $perm;
        }
        if (!file_exists("PMF/".$perm->_perm_typemap[$perm_level].".php")) {
        	return $perm;
        }
        require_once("PMF/".$perm->_auth_typemap[$perm_level].".php");
        // instantiate 
        $permclass = "PMF_".$auth->_auth_typemap[$perm_level];
		$perm = new $permclass($db, $user_id);
        return $perm;
        // section -64--88-1-10--77ac1b05:106d99eac38:-7fab end

        return $returnValue;
    }

    /**
     * Short description of method bool_to_int
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param bool
     * @return int
     */
    function bool_to_int($val)
    {
        $returnValue = (int) 0;

        // section -64--88-1-10--785a539b:106d9d6c253:-7fc0 begin
        if ($val === true) 
            return (int) 1;
        return (int) 0;
        // section -64--88-1-10--785a539b:106d9d6c253:-7fc0 end

        return (int) $returnValue;
    }

    /**
     * Short description of method int_to_bool
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function int_to_bool($val)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--785a539b:106d9d6c253:-7fbd begin
        if ($val == 1) 
            return true;
        return false;
        // section -64--88-1-10--785a539b:106d9d6c253:-7fbd end

        return (bool) $returnValue;
    }

    /**
     * Short description of method setPerm
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param int
     * @param string
     * @param int
     * @return void
     */
    function setPerm($db, $user_id = 0, $context = '', $context_id = 0)
    {
        // section 127-0-0-1--6945df47:106df4af666:-7fdd begin
        if (!PMF_User::checkDb($db))
            return false;
        $this->_db = $db;
        $this->_user_id = $user_id;
        return;
        // section 127-0-0-1--6945df47:106df4af666:-7fdd end
    }

    /**
     * Short description of method resetPerm
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function resetPerm()
    {
        // section 127-0-0-1--6945df47:106df4af666:-7fd9 begin
        $this->_db = null;
        $this->_user_id = 0;
        // section 127-0-0-1--6945df47:106df4af666:-7fd9 end
    }

} /* end of class PMF_Perm */

?>