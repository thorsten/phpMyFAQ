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
 * manages user authentication. 
 *
 * Subclasses of Auth implement authentication functionality with different
 * types. The class AuthLdap for expamle provides authentication functionality
 * LDAP-database access, AuthMysql with MySQL-database access.
 *
 * Authentication functionality includes creation of a new login-and-password
 * deletion of an existing login-and-password combination and validation of
 * given by a user. These functions are provided by the database-specific
 * see documentation of the database-specific authentication classes AuthMysql,
 * or AuthLdap for further details.
 *
 * Passwords are usually encrypted before stored in a database. For
 * and security, a password encryption method may be chosen. See documentation
 * Enc class for further details.
 *
 * Instead of calling the database-specific subclasses directly, the static
 * selectDb(dbtype) may be called which returns a valid database-specific
 * object. See documentation of the static method selectDb for further details.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-30
 * @version 0.1
 */
require_once('PMF/Auth.php');

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
@define('PMF_USERERROR_LOGIN_NOT_UNIQUE', 'Login is not unique. ');
@define('PMF_LOGIN_MINLENGTH', 4);
@define('PMF_LOGIN_INVALID_REGEXP', '/(^[^a-z]{1}|[\W])/i');
@define('PMF_USERERROR_LOGIN_INVALID', 'The chosen login is invalid. A valid login has at least four characters. Only letters, numbers and underscore _ are allowed. The first letter must be a letter. ');
@define('PMF_UNDEFINED_PARAMETER', 'Following parameter must to be defined: ');
@define('PMF_USERERROR_ADD', 'Account could not be created. ');
@define('PMF_USERERROR_CHANGE', 'Account could not be updated. ');
@define('PMF_USERERROR_DELETE', 'Account could not be deleted. ');
@define('PMF_USERERROR_INCORRECT_PASSWORD', 'Specified password is not correct. '); 
@define('PMF_USER_NOT_FOUND', 'User account could not be found. ');
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
     * Public array that contains error messages.
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
     * @access private
     * @var array
     */
    var $_allowed_status = array('active', 'blocked', 'protected');

    /**
     * Short description of attribute auth_container
     *
     * @access private
     * @var array
     */
    var $_auth_container = array();

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
        if ($this->checkDb($db)) {
        	$this->_db = $db;
            return true;
        }
        $this->_db = null;
        return false;
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
        if (isset($this->_user_id) and is_int($this->_user_id) and $this->_user_id > 0) {
            return (int) $this->_user_id;
        }
        $this->_user_id = (int) 0;
        $this->errors[] = PMF_USERERROR_NO_USERID;
        return (int) 0;
        // section -64--88-1-5-a522a6:106564ad215:-7ffb end

        return (int) $returnValue;
    }

    /**
     * Short description of method getUserById
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function getUserById($user_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:1065f4960e0:-7fc1 begin
        if (!$this->_db) {
        	$this->errors[] = PMF_USERERROR_NO_DB;
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
        $this->_login   = (string) $user['login'];
		$this->_status  = (string) $user['status'];
		$this->_user_id = (int)    $user['id'];
		return true;
        // section -64--88-1-5-15e2075:1065f4960e0:-7fc1 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getUserByLogin
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function getUserByLogin($login)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:1065f4960e0:-7fbe begin
        if (!$this->_db) {
        	$this->errors[] = PMF_USERERROR_NO_DB;
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
        $this->_login   = (string) $user['login'];
		$this->_status  = (string) $user['status'];
		$this->_user_id = (int)    $user['id'];
		return true;
        // section -64--88-1-5-15e2075:1065f4960e0:-7fbe end

        return (bool) $returnValue;
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
        if (!$this->_db) {
        	$this->errors[] = PMF_USERERROR_NO_DB;
		    return false;
        }
        // is $login valid?
        $login = (string) $login;
        if (!$this->isLoginValid($login)) 
            return false;
        // does $login already exist?
        $user = new PMF_User($this->_db);
        if ($user->getUserByLogin($login)) {
        	$this->errors[] = PMF_USERERROR_LOGIN_NOT_UNIQUE;
            return false;
        }
        // 
        $this->_db->query("
          INSERT INTO
            ".SQLPREFIX."user
          SET
            
        ");
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
     * @param object
     * @param object
     * @param mixed
     * @return object
     */
    function PMF_User($db = null, $perm = null, $auth = array())
    {
        $returnValue = null;

        // section -64--88-1-5--735fceb5:106657b6b8d:-7fdb begin
        return $this->__construct($db, $perm, $auth);
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
        if (!in_array($status, $this->_allowed_status)) {
            $this->errors[] = PMF_USERERROR_INVALID_STATUS;
            return false;
        }
        // update status
        $this->_status = $status;
        $user_id = $this->getUserId();
        if (!$user_id) {
			$this->errors[] = PMF_USERERROR_NO_USERID;
            return false;
        }
        if (!$this->_db) {
        	$this->errors[] = PMF_USERERROR_NO_DB;
		    return false;
        }
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
     * Short description of method checkDb
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return bool
     */
    function checkDb($db)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd4 begin
        $methods = array('query', 'num_rows', 'fetch_assoc', 'error');
        foreach ($methods as $method) {
        	if (!method_exists($db, $method)) {
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
     * Returns a string with error messages. 
     *
     * The string returned by error() contains messages for all errors that
     * during object procesing. Messages are separated by new lines.
     *
     * Error messages are stored in the public array errors.
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

    /**
     * Short description of method __construct
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param object
     * @param mixed
     * @return void
     */
    function __construct($db = null, $perm = null, $auth = array())
    {
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fce begin
        if (!$this->addDb($db))
            return false;
        return $this; 
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fce end
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
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fcb begin
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fcb end
    }

    /**
     * Short description of method isValidLogin
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function isValidLogin($login)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--602a52f4:106a644a5e8:-7fc8 begin
        $login = (string) $login;
        if (strlen($login) < PMF_LOGIN_MINLENGTH or preg_match(PMF_LOGIN_INVALID_REGEXP, $login)) {
        	$this->errors[] = PMF_USERERROR_LOGIN_INVALID;
            return false;
        }
        return true;
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fc8 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method addAuth
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return void
     */
    function addAuth($auth)
    {
        // section -64--88-1-10-5a491889:106a7b76a96:-7fda begin
        if ($this->checkAuth($auth)) {
        	$this->_auth_container[] = $auth;
            return true;
        }
        return false;
        // section -64--88-1-10-5a491889:106a7b76a96:-7fda end
    }

    /**
     * Short description of method checkAuth
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return bool
     */
    function checkAuth($auth)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10-59fce530:106a800a699:-7fda begin
        $methods = array('login');
        foreach ($methods as $method) {
        	if (!method_exists($auth, $method)) {
				$this->errors[] = PMF_USERERROR_NOauth;
        		return false;
        		break;
        	}
        }
        return true;
        // section -64--88-1-10-59fce530:106a800a699:-7fda end

        return (bool) $returnValue;
    }

    /**
     * Short description of method checkPerm
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return bool
     */
    function checkPerm($perm)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10-59fce530:106a800a699:-7fd7 begin
        // section -64--88-1-10-59fce530:106a800a699:-7fd7 end

        return (bool) $returnValue;
    }

} /* end of class PMF_User */

?>