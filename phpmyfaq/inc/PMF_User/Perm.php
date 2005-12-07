<?php

error_reporting(E_ALL);

/**
 * This class manages user permissions and group memberships.
 *
 * There are three possible extensions of this class: basic, medium and large
 * by the classes PMF_PermBasic, PMF_PermMedium and PMF_PermLarge. The classes
 * to allow for scalability. This means that PMF_PermMedium is an extend of
 * and PMF_PermLarge is an extend of PMF_PermMedium.
 *
 * The permission type can be selected by calling $perm = PMF_Perm(perm_type) or
 * static method $perm = PMF_Perm::selectPerm(perm_type) where perm_type is
 * 'medium' or 'large'. Both ways, a PMF_PermBasic, PMF_PermMedium or
 * is returned.
 *
 * Before calling any method, the object $perm needs to be initialised calling
 * user_id, context, context_id). The parameters context and context_id are
 * accepted, but do only matter in PMF_PermLarge. In other words, if you have a
 * or PMF_PermMedium, it does not matter if you pass context and context_id or
 * But in PMF_PermLarge, they do make a significant difference if passed, thus
 * for up- and downwards-compatibility.
 *
 * Perhaps the most important method is $perm->checkRight(right_name). This
 * checks whether the user having the user_id set with $perm->setPerm()
 *
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
//require_once('PMF/User.php');

/* user defined includes */
// section -64--88-1-5-15e2075:10637248df4:-7fd0-includes begin
require_once dirname(__FILE__).'/User.php';
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
 * and PMF_PermLarge is an extend of PMF_PermMedium.
 *
 * The permission type can be selected by calling $perm = PMF_Perm(perm_type) or
 * static method $perm = PMF_Perm::selectPerm(perm_type) where perm_type is
 * 'medium' or 'large'. Both ways, a PMF_PermBasic, PMF_PermMedium or
 * is returned.
 *
 * Before calling any method, the object $perm needs to be initialised calling
 * user_id, context, context_id). The parameters context and context_id are
 * accepted, but do only matter in PMF_PermLarge. In other words, if you have a
 * or PMF_PermMedium, it does not matter if you pass context and context_id or
 * But in PMF_PermLarge, they do make a significant difference if passed, thus
 * for up- and downwards-compatibility.
 *
 * Perhaps the most important method is $perm->checkRight(right_name). This
 * checks whether the user having the user_id set with $perm->setPerm()
 *
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
     * Database object created by PMF_Db
     *
     * @access private
     * @var object
     */
    var $_db = null;

    /**
     * Allowed classnames for subclasses for Perm::selectPerm()
     *
     * @access private
     * @var array
     */
    var $_perm_typemap = array('basic' => 'PermBasic', 'medium' => 'PermMedium', 'large' => 'PermLarge');

    /**
     * Set TRUE if valid database and user-ID are given
     *
     * @access private
     * @var bool
     */
    var $_initialized = false;

    // --- OPERATIONS ---

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_Perm()
    {
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd1 begin
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd1 end
    }

    /**
     * destructor
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
     * Selects a subclass of PMF_Perm. 
     *
     * selectPerm() returns an instance of a subclass of PMF_Perm. perm_level
     * which subclass is returned. Allowed values and corresponding classnames
     * defined in perm_typemap.
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
        if (!isset($perm->_perm_typemap[$perm_level])) {
            return $perm;
        }
        if (!file_exists("PMF/".$perm->_perm_typemap[$perm_level].".php")) {
        	return $perm;
        }
        require_once dirname(__FILE__)."/".$perm->_perm_typemap[$perm_level].".php";
        // instantiate 
        $permclass = "PMF_".$perm->_perm_typemap[$perm_level];
		$perm = new $permclass();
        return $perm;
        // section -64--88-1-10--77ac1b05:106d99eac38:-7fab end

        return $returnValue;
    }

    /**
     * converts a boolean into a corresponding integer: 0 or 1.
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
        if (!$val) 
            return (int) 0;
        return (int) 1;
        // section -64--88-1-10--785a539b:106d9d6c253:-7fc0 end

        return (int) $returnValue;
    }

    /**
     * converts an integer into the corresponding boolean value: true or false.
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
        if (!$val or $val == 0) 
            return false;
        return true;
        // section -64--88-1-10--785a539b:106d9d6c253:-7fbd end

        return (bool) $returnValue;
    }

    /**
     * initalizes the object. 
     *
     * PMF_Perm needs a database access in order to work. db must be a valid
     * object.
     *
     * User specific permissions can only be checked and set, if a valid user-ID
     * given. However, for administration purposes, user_id may be omitted.
     *
     * Context information context and context_id only work with
     * See the documentation of PMF_PermLarge for context description.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param string
     * @param int
     * @return bool
     */
    function addDb($db, $context = '', $context_id = 0)
    {
        $returnValue = (bool) false;

        // section 127-0-0-1--6945df47:106df4af666:-7fdd begin
        if (!PMF_User::checkDb($db))
            return false;
        $this->_db = $db;
        $this->_initialized = true;
        return true;
        // section 127-0-0-1--6945df47:106df4af666:-7fdd end

        return (bool) $returnValue;
    }

} /* end of class PMF_Perm */

?>
