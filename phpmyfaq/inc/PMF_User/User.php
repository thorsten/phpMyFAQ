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
@define('PMF_USER_NOT_FOUND', 'User account could not be found. ');
@define('PMF_USERERROR_NO_AUTH', 'No authentication method specified. ');
@define('PMF_USERERROR_INCORRECT_LOGIN', 'Specified login could not be found. ');
@define('PMF_USERERROR_CANNOT_CREATE_USER', 'User account could not be created. ');
@define('PMF_USERERROR_CANNOT_DELETE_USER', 'User account could not be deleted. ');
@define('PMF_USERSTATUS_PROTECTED', 'User account is protected. ');
@define('PMF_USERSTATUS_BLOCKED', 'User account is blocked. ');
@define('PMF_USERSTATUS_ACTIVE', 'User account is active. ');
@define('PMF_USERERROR_NO_AUTH_WRITABLE', 'No authentication object is writable. ');
@define('PMF_USERERROR_CANNOT_CREATE_USERDATA', 'Entry for user data could not be created. ');
@define('PMF_USERERROR_CANNOT_DELETE_USERDATA', 'Entry for user data could not be deleted. ');
@define('PMF_USERERROR_CANNOT_UPDATE_USERDATA', 'Entry for user data could not be updated. ');
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
     * Permission container
     *
     * @access public
     * @var object
     */
    var $perm = null;

    /**
     * User-data storage container
     *
     * @access public
     * @var object
     */
    var $userdata = null;

    /**
     * database object
     *
     * @access private
     * @var object
     */
    var $_db = null;

    /**
     * login string
     *
     * @access private
     * @var string
     */
    var $_login = '';

    /**
     * user ID
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
     * status of user.
     *
     * @access private
     * @var string
     */
    var $_status = '';

    /**
     * array of allowed values for status
     *
     * @access private
     * @var array
     */
    var $_allowed_status = array('active' => PMF_USERSTATUS_ACTIVE, 'blocked' => PMF_USERSTATUS_BLOCKED, 'protected' => PMF_USERSTATUS_PROTECTED);

    /**
     * authentication container
     *
     * @access private
     * @var array
     */
    var $_auth_container = array();

    // --- OPERATIONS ---

    /**
     * adds a permission object to the user.
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
     * adds a database object to the user.
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
     * returns the user-ID of the user.
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
     * loads basic user information from the database selecting the user with
     * specified user-ID.
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
        // check db
        if (!$this->checkDb($this->_db))
		    return false;
		// get user
        $res = $this->_db->query("
		  SELECT 
		    user_id,
		    login,
		    account_status
		  FROM
		    ".SQLPREFIX."user
		  WHERE 
			user_id = '".(int) $user_id."'		    
		");
		if ($this->_db->num_rows($res) != 1) {
			$this->errors[] = PMF_USERERROR_NO_USERID . 'error(): ' . $this->_db->error();
			return false;
		}
		$user = $this->_db->fetch_assoc($res);
		$this->_user_id = (int)    $user['user_id'];
        $this->_login   = (string) $user['login'];
		$this->_status  = (string) $user['account_status'];
		// get user-data
        if (!$this->userdata)
		    $this->userdata = new PMF_UserData($this->_db);
		$this->userdata->load($this->getUserId());
		return true;
        // section -64--88-1-5-15e2075:1065f4960e0:-7fc1 end

        return (bool) $returnValue;
    }

    /**
     * loads basic user information from the database selecting the user with
     * specified login.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param bool
     * @return bool
     */
    function getUserByLogin($login, $raise_error = true)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:1065f4960e0:-7fbe begin
        // check db
        if (!$this->checkDb($this->_db))
		    return false;
		// get user
        $res = $this->_db->query("
		  SELECT 
		    user_id,
		    login,
		    account_status
		  FROM
		    ".SQLPREFIX."user
		  WHERE 
			login = '".$this->_db->escape_string($login)."'		    
		");
		if ($this->_db->num_rows($res) != 1) {
			if ($raise_error)
			    $this->errors[] = PMF_USERERROR_INCORRECT_LOGIN;
			return false;
		}
		$user = $this->_db->fetch_assoc($res);
		$this->_user_id = (int)    $user['user_id'];
        $this->_login   = (string) $user['login'];
		$this->_status  = (string) $user['account_status'];
		// get user-data
        if (!$this->userdata)
		    $this->userdata = new PMF_UserData($this->_db);
		$this->userdata->load($this->getUserId());
		return true;
        // section -64--88-1-5-15e2075:1065f4960e0:-7fbe end

        return (bool) $returnValue;
    }

    /**
     * creates a new user and stores basic data in the database.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return mixed
     */
    function createUser($login, $pass = '')
    {
        $returnValue = null;

        // section -64--88-1-5-5e0b50c5:10665348267:-7fdd begin
        if (!$this->checkDb($this->_db))
		    return false;
        foreach ($this->_auth_container as $name => $auth) {
        	if (!$this->checkAuth($auth)) {
        		return false;
        	}
        }
        // is $login valid?
        $login = (string) $login;
        if (!$this->isValidLogin($login)) 
            return false;
        // does $login already exist?
        if ($this->getUserByLogin($login, false)) {
        	$this->errors[] = PMF_USERERROR_LOGIN_NOT_UNIQUE;
            return false;
        }
        // set user-ID
        $this->_user_id = (int) $this->_db->nextID(SQLPREFIX.'user', 'user_id');
        // create user entry
        $this->_db->query("
          INSERT INTO
            ".SQLPREFIX."user
          SET
            user_id = '".$this->getUserId()."', 
            login   = '".$this->_db->escape_string($login)."'
        ");
        // create user-data entry
        if (!$this->userdata)
		    $this->userdata = new PMF_UserData($this->_db);
		$data = $this->userdata->add($this->getUserId());
		if (!$data) {
			$this->errors[] = PMF_USERERROR_CANNOT_CREATE_USERDATA;
		    return false;
		}
        // create authentication entry
        if ($pass == '') 
        	$pass = $this->createPassword();
        $success = false;
        foreach ($this->_auth_container as $name => $auth) {
        	if ($auth->read_only()) {
        		continue;
        	}
        	if (!$auth->add($login, $auth->encrypt($pass))) {
        		$this->errors[] = PMF_USERERROR_CANNOT_CREATE_USER.'in PMF_Auth '.$name;
        	}
        	else {
        		$success = true;
        	}
        }
        return $success;
        // section -64--88-1-5-5e0b50c5:10665348267:-7fdd end

        return $returnValue;
    }

    /**
     * deletes the user from the database.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return mixed
     */
    function deleteUser()
    {
        $returnValue = null;

        // section -64--88-1-5-5e0b50c5:10665348267:-7fda begin
        // check user-ID
        if (!isset($this->_user_id) or $this->_user_id == 0) {
        	$this->errors[] = PMF_USERERROR_NO_USERID;
        	return false;
        }
        // check login
        if (!isset($this->_login) or strlen($this->_login) == 0) {
        	$this->errors[] = PMF_USERERROR_LOGIN_INVALID;
        	return false;
        }
        // user-account is protected
        if (isset($this->_allowed_status[$this->_status]) and $this->_allowed_status[$this->_status] == PMF_USERSTATUS_PROTECTED) {
			$this->errors[] = PMF_USERERROR_CANNOT_DELETE_USER . PMF_USERSTATUS_PROTECTED;
			return false;
        }
        // check db
        if (!$this->checkDb($this->_db)) 
            return false;
        // delete user account
		$res = $this->_db->query("
		  DELETE FROM
		    ".SQLPREFIX."user
		  WHERE
		    user_id = '".$this->_user_id."'
		");
		if (!$res) {
			$this->errors[] = PMF_CANNOT_DELETE_USER . 'error(): ' . $this->_db->error();
			return false;
		}
        // delete user-data entry
        if (!$this->userdata)
		    $this->userdata = new PMF_UserData($this->_db);
		$data = $this->userdata->delete($this->getUserId());
		if (!$data) {
			$this->errors[] = PMF_USERERROR_CANNOT_DELETE_USERDATA;
		    return false;
		}
		// delete authentication entry
		$read_only = 0;
		$no_account_mirror = 0;
		$auth_count = 0;
		$delete = array();
		foreach ($this->_auth_container as $name => $auth) {
			$auth_count++;
			// auth link is not writable
			if ($auth->read_only()) {
				$read_only++;
				continue;
			}
			// auth link is not a mirror
			if (!$auth->account_mirror()) {
				$no_account_mirror++;
				continue;
			}
			// try to delete authentication entry
			$delete[] = $auth->delete($this->_login);
		}
		// there was no writable authentication object
		if ($read_only + $no_account_mirror == $auth_count) {
			$this->errors[] = PMF_USERERROR_NO_AUTH_WRITABLE;
		}
		// deletion unsuccessful
		if (!in_array(true, $delete)) 
		    return false;
		// deletion always successful
		$deletion_success = count(array_keys($delete, true));
		if ($deletion_success == $auth_count)
			return true;
		// return how many times deletion was successful
		return $deletion_success;
        // section -64--88-1-5-5e0b50c5:10665348267:-7fda end

        return $returnValue;
    }

    /**
     * changes the user's password.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function changePassword($pass)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-5e0b50c5:10665348267:-7fd8 begin
        if (!$this->checkDb($this->_db))
		    return false;
        foreach ($this->_auth_container as $name => $auth) {
        	if (!$this->checkAuth($auth)) {
        		return false;
        	}
        }
        // update authentication entry
        $login = $this->getLogin();
        if ($pass == '') 
        	$pass = $this->createPassword();
        $success = false;
        foreach ($this->_auth_container as $name => $auth) {
        	if ($auth->read_only()) {
        		continue;
        	}
        	if (!$auth->changePassword($login, $pass)) {
        		continue;
        	}
        	else {
        		$success = true;
        	}
        }
        return $success;
        // section -64--88-1-5-5e0b50c5:10665348267:-7fd8 end

        return (bool) $returnValue;
    }

    /**
     * constructor.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param object
     * @param mixed
     * @return void
     */
    function PMF_User($db = null, $perm = null, $auth = array())
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fdb begin
        if ($db !== null) {
            if (!$this->addDb($db))
                return false;
        }
        if ($perm !== null) { 
            if (!$this->addPerm($perm))
                return false;
        }
        if (count($auth) > 0) {
        	foreach ($auth as $name => $auth_object) {
        		if (!$this->addAuth($auth_object, $name)) {
        		    return false;
					break;
        		}
        	}
        }
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fdb end
    }

    /**
     * returns the user's status.
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
     * sets the user's status and updates the database entry.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function setStatus($status)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd7 begin
        // is status allowed?
        $status = strtolower($status);
        if (!in_array($status, $this->_allowed_status)) {
            $this->errors[] = PMF_USERERROR_INVALID_STATUS;
            return false;
        }
        // check user-ID
        $user_id = $this->getUserId();
        if (!$user_id) {
			$this->errors[] = PMF_USERERROR_NO_USERID;
            return false;
        }
        // check db
        if (!$this->_db) {
        	$this->errors[] = PMF_USERERROR_NO_DB;
		    return false;
        }
        // update status
        $this->_status = $status;
        $res = $this->_db->query("
		  UPDATE 
		    ".SQLPREFIX."user 
		  SET 
		    account_status = '".$status."' 
		  WHERE 
		    user_id = '".$user_id."'
		");
		// return bool
		if ($res)
		    return true;
		return false;
        // section -64--88-1-10--602a52f4:106a644a5e8:-7fd7 end

        return (bool) $returnValue;
    }

    /**
     * returns true if db is a valid database object.
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
     * destructor
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
     * returns true if login is a valid login string. 
     *
     * Relevant constants:
     *
     * PMF_LOGIN_MINLENGTH defines the minimum length the login string must
     * If login has more characters than allowed, false is returned.
     *
     * PMF_LOGIN_INVALID_REGEXP is a regular expression. If login matches this
     * false is returned.
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
     * adds a new authentication object to the user object.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param string
     * @return void
     */
    function addAuth($auth, $name)
    {
        // section -64--88-1-10-5a491889:106a7b76a96:-7fda begin
        if ($this->checkAuth($auth)) {
        	$this->_auth_container[$name] = $auth;
            return true;
        }
        return false;
        // section -64--88-1-10-5a491889:106a7b76a96:-7fda end
    }

    /**
     * returns true if auth is a valid authentication object.
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
        $methods = array('checkPassword');
        foreach ($methods as $method) {
        	if (!method_exists($auth, strtolower($method))) {
				$this->errors[] = PMF_USERERROR_NO_AUTH;
        		return false;
        		break;
        	}
        }
        return true;
        // section -64--88-1-10-59fce530:106a800a699:-7fda end

        return (bool) $returnValue;
    }

    /**
     * returns true if perm is a valid permission object.
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

    /**
     * returns the user's login.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return string
     */
    function getLogin()
    {
        $returnValue = (string) '';

        // section -64--88-1-10-eb43fc:106c4f6ca50:-7fd0 begin
        return $this->_login;
        // section -64--88-1-10-eb43fc:106c4f6ca50:-7fd0 end

        return (string) $returnValue;
    }

    /**
     * returns a new password.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return string
     */
    function createPassword()
    {
        $returnValue = (string) '';

        // section -64--88-1-10-eb43fc:106c4f6ca50:-7fca begin
		srand((double)microtime()*1000000);
  		return (string) uniqid(rand());
        // section -64--88-1-10-eb43fc:106c4f6ca50:-7fca end

        return (string) $returnValue;
    }

    /**
     * Short description of method getUserData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param mixed
     * @return mixed
     */
    function getUserData($field = '*')
    {
        $returnValue = null;

        // section -64--88-1-10--7165c41e:106c72278bc:-7fd9 begin
        // get user-data entry
        if (!$this->userdata)
		    $this->userdata = new PMF_UserData($this->_db);
		return $this->userdata->get($field);
        // section -64--88-1-10--7165c41e:106c72278bc:-7fd9 end

        return $returnValue;
    }

    /**
     * Short description of method setUserData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @return bool
     */
    function setUserData($data)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--7165c41e:106c72278bc:-7fd7 begin
        // set user-data entry
        if (!$this->userdata)
		    $this->userdata = new PMF_UserData($this->_db);
		$this->userdata->load($this->getUserId());
		return $this->userdata->set(array_keys($data), array_values($data));
        // section -64--88-1-10--7165c41e:106c72278bc:-7fd7 end

        return (bool) $returnValue;
    }

} /* end of class PMF_User */

?>