<?php

error_reporting(E_ALL);

/**
 * Creates a new user object.
 *
 * A user are recognized by the session-id using getUserBySessionId(), by his
 * using getUserById() or by his nickname (login) using getUserByLogin(). New
 * are created using createNewUser().
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

/**
 * The userdata class provides methods to manage user information.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once('PMF/UserData.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:105b52d5117:-7ff0-includes begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7ff0-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:105b52d5117:-7ff0-constants begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7ff0-constants end

/**
 * Creates a new user object.
 *
 * A user are recognized by the session-id using getUserBySessionId(), by his
 * using getUserById() or by his nickname (login) using getUserByLogin(). New
 * are created using createNewUser().
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-17
 * @version 0.1
 */
class PMF_User
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute perm
     *
     * @access public
     * @var object
     */
    var $perm = null;

    /**
     * Short description of attribute userdata
     *
     * @access public
     * @var object
     */
    var $userdata = null;

    /**
     * Short description of attribute db
     *
     * @access private
     * @var object
     */
    var $_db = null;

    /**
     * Short description of attribute login
     *
     * @access private
     * @var string
     */
    var $_login = '';

    /**
     * Short description of attribute user_id
     *
     * @access private
     * @var int
     */
    var $_user_id = 0;

    // --- OPERATIONS ---

    /**
     * Short description of method addPerm
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return void
     */
    function addPerm($perm)
    {
        // section -64--88-1-5-15e2075:10637248df4:-7fcd begin
        // section -64--88-1-5-15e2075:10637248df4:-7fcd end
    }

    /**
     * Short description of method addDb
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return void
     */
    function addDb($db)
    {
        // section -64--88-1-5-a522a6:106564ad215:-7ffd begin
        // section -64--88-1-5-a522a6:106564ad215:-7ffd end
    }

    /**
     * Short description of method getUserId
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return int
     */
    function getUserId()
    {
        $returnValue = (int) 0;

        // section -64--88-1-5-a522a6:106564ad215:-7ffb begin
        // section -64--88-1-5-a522a6:106564ad215:-7ffb end

        return (int) $returnValue;
    }

    /**
     * Short description of method getUserById
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return mixed
     */
    function getUserById($user_id)
    {
        $returnValue = null;

        // section -64--88-1-5-15e2075:1065f4960e0:-7fc1 begin
        // section -64--88-1-5-15e2075:1065f4960e0:-7fc1 end

        return $returnValue;
    }

    /**
     * Short description of method getUserByLogin
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return mixed
     */
    function getUserByLogin($login)
    {
        $returnValue = null;

        // section -64--88-1-5-15e2075:1065f4960e0:-7fbe begin
        $this->_login = $login;
        // section -64--88-1-5-15e2075:1065f4960e0:-7fbe end

        return $returnValue;
    }

    /**
     * Short description of method createUser
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return mixed
     */
    function createUser($login)
    {
        $returnValue = null;

        // section -64--88-1-5-5e0b50c5:10665348267:-7fdd begin
        // section -64--88-1-5-5e0b50c5:10665348267:-7fdd end

        return $returnValue;
    }

    /**
     * Short description of method delUser
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function delUser()
    {
        // section -64--88-1-5-5e0b50c5:10665348267:-7fda begin
        // section -64--88-1-5-5e0b50c5:10665348267:-7fda end
    }

    /**
     * Short description of method setPassword
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return void
     */
    function setPassword($pass)
    {
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd8 begin
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd8 end
    }

    /**
     * Short description of method PMF_User
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return object
     */
    function PMF_User()
    {
        $returnValue = null;

        // section -64--88-1-5--735fceb5:106657b6b8d:-7fdb begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fdb end

        return $returnValue;
    }

} /* end of class PMF_User */

?>/* lost code following: 
    // section -64--88-1-5-a522a6:106564ad215:-7fd2 begin
    // section -64--88-1-5-a522a6:106564ad215:-7fd2 end
*/