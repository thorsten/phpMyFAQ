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
@define('PMF_USERERROR_NO_DB', 'No database specified. ');
@define('SQLPREFIX', 'faq_');
@define('PMF_USERERROR_INVALID_STATUS', 'Undefined user status. ');
@define('PMF_USERERROR_NO_USERID', 'No user-ID found. ');
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

    /**
     * Short description of attribute errors
     *
     * @access public
     * @var array
     */
    var $errors = array();

    /**
     * Short description of attribute status
     *
     * @access private
     * @var string
     */
    var $_status = '';

    /**
     * Short description of attribute allowed_status
     *
     * @access public
     * @var array
     */
    var $allowed_status = array('active', 'blocked', 'protected');

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
        $this->_db = $db;
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
        if (isset($this->_user_id) and $this->_user_id > 0) {
            return (int) $this->_user_id;
        }
        $this->errors[] = PMF_USERERROR_NO_USERID;
        return 0;
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
        if (!$this->checkDB()) {
		    return false;
        }
        $res = $this->_db->query("
		  SELECT 
		    ".SQLPREFIX."userlogin.login AS login,
		    ".SQLPREFIX."user.user_id AS id,
		    ".SQLPREFIX."user.account_status AS status
		  FROM
		    ".SQLPREFIX."user,
		    ".SQLPREFIX."userlogin
		  WHERE 
		    ".SQLPREFIX."user.login = ".SQLPREFIX."userlogin.login AND
			".SQLPREFIX."user.user_id = '".$user_id."'		    
		");
		if ($this->_db->num_rows($res) != 1) {
			return false;
		}
		$user = $this->_db->fetch_assoc($res);
        $this->_login = $user['login'];
		$this->_status = $user['status'];
		$this->_user_id = $user['id'];
		return true;
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
        if (!$this->checkDB()) {
		    return false;
        }
        $res = $this->_db->query("
		  SELECT 
		    ".SQLPREFIX."userlogin.login AS login,
		    ".SQLPREFIX."user.user_id AS id,
		    ".SQLPREFIX."user.account_status AS status
		  FROM
		    ".SQLPREFIX."user,
		    ".SQLPREFIX."userlogin
		  WHERE 
		    ".SQLPREFIX."user.login = ".SQLPREFIX."userlogin.login AND
			".SQLPREFIX."userlogin.login = '".$login."'		    
		");
		if ($this->_db->num_rows($res) != 1) {
			return false;
		}
		$user = $this->_db->fetch_assoc($res);
        $this->_login = $user['login'];
		$this->_status = $user['status'];
		$this->_user_id = $user['id'];
		return true;
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

    /**
     * Short description of method getStatus
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return string
     */
    function getStatus()
    {
        $returnValue = (string) '';

        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd9 begin
        if (isset($this->_status) and strlen($this->_status) > 0) {
        	return $this->_status;
        }
        return false;
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd9 end

        return (string) $returnValue;
    }

    /**
     * Short description of method setStatus
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return void
     */
    function setStatus($status)
    {
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd7 begin
        // is status allowed?
        $status = strtolower($status);
        if (!in_array($status, $this->allowed_status)) {
            $this->errors[] = PMF_USERERROR_INVALID_STATUS;
            return false;
        }
        // update status
        $this->_status = $status;
        if (!$this->checkDB()) 
            return false;
        $user_id = $this->getUserId();
        if (!$user_id)
            return false;
        return $this->_db->query("
		  UPDATE 
		    ".SQLPREFIX."user 
		  SET 
		    account_status = '".$status."' 
		  WHERE 
		    user_id = '".$user_id."'
		");
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd7 end
    }

    /**
     * Short description of method checkDB
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function checkDB()
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd4 begin
        if (!isset($this->_db)) {
			$this->errors[] = PMF_USERERROR_NO_DB;
            return false;
        }
        $methods = array('query', 'num_rows', 'fetch_assoc', 'error');
        $returnValue = true;
        foreach ($methods as $method) {
        	if (!method_exists($this->_db, $method)) {
				$this->errors[] = PMF_USERERROR_NO_DB;
        		return false;
        		break;
        	}
        }
        return true;
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd4 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method error
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return string
     */
    function error()
    {
        $returnValue = (string) '';

        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd2 begin
        if (!is_array($this->errors)) 
            return false;
        $message = '';
        foreach ($this->errors as $error) {
        	$message .= $error."\n";
        }
        return $message;
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd2 end

        return (string) $returnValue;
    }

} /* end of class PMF_User */

?>