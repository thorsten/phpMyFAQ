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

/* user defined includes */

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
require_once dirname(__FILE__).'/Auth.php';

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
require_once dirname(__FILE__).'/Perm.php';

/**
 * The userdata class provides methods to manage user information.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once dirname(__FILE__).'/UserData.php';

/* user defined constants */
@define('PMF_USERERROR_NO_DB', 'No database specified. ');
@define('PMF_USERERROR_NO_PERM', 'No permission container specified. ');
@define('PMF_USER_SQLPREFIX', SQLPREFIX.'faq');
@define('PMF_USERERROR_INVALID_STATUS', 'Undefined user status. ');
@define('PMF_USERERROR_NO_USERID', 'No user-ID found. ');
@define('PMF_USERERROR_LOGIN_NOT_UNIQUE', 'Login is not unique. ');
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
     * minimum length of login string (default: 4)
     *
     * @access private
     * @var int
     */
    var $_login_minLength = 4;

    /**
     * regular expression to find invalid login strings
     * (default: /(^[^a-z]{1}|[\W])/i )
     *
     * @access private
     * @var string
     */
    var $_login_invalidRegExp = '/(^[^a-z]{1}|[\W])/i';

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
        if ($this->checkPerm($perm)) {
            $this->perm = $perm;
            return true;
        }
        $this->perm = null;
        return false;
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
        if ($this->checkDb($db)) {
        	$this->_db = $db;
            return true;
        }
        $this->_db = null;
        return false;
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
        if (isset($this->_user_id) and is_int($this->_user_id) and $this->_user_id > 0) {
            return (int) $this->_user_id;
        }
        $this->_user_id = (int) 0;
        $this->errors[] = PMF_USERERROR_NO_USERID;
        return (int) 0;
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
		    ".PMF_USER_SQLPREFIX."user
		  WHERE
			user_id = ".(int) $user_id
        );
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
		    ".PMF_USER_SQLPREFIX."user
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
        $this->_user_id = (int) $this->_db->nextID(PMF_USER_SQLPREFIX.'user', 'user_id');
        // create user entry
        $this->_db->query("
          INSERT INTO
            ".PMF_USER_SQLPREFIX."user
          (user_id, login)
            VALUES
          (".$this->getUserId().", '".$this->_db->escape_string($login)."')
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
        	if (!$auth->add($login, $pass)) {
        		$this->errors[] = PMF_USERERROR_CANNOT_CREATE_USER.'in PMF_Auth '.$name;
        	} else {
        		$success = true;
        	}
        }
        if (!$success)
            return false;
        return $this->getUserByLogin($login, false);
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
        if (!$this->checkDb($this->_db)) {
            return false;
        }
        // delete user rights
        $this->perm->refuseAllUserRights($this->_user_id);
        // delete user account
		$res = $this->_db->query("
		  DELETE FROM
		    ".PMF_USER_SQLPREFIX."user
		  WHERE
		    user_id = ".$this->_user_id
        );
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
		$auth_count = 0;
		$delete = array();
		foreach ($this->_auth_container as $name => $auth) {
			$auth_count++;
			// auth link is not writable
			if ($auth->read_only()) {
				$read_only++;
				continue;
			}
			// try to delete authentication entry
			$delete[] = $auth->delete($this->_login);
		}
		// there was no writable authentication object
		if ($read_only == $auth_count) {
			$this->errors[] = PMF_USERERROR_NO_AUTH_WRITABLE;
		}
		// deletion unsuccessful
		if (!in_array(true, $delete))
		    return false;
		return true;
    }

    /**
     * changes the user's password. If $pass is omitted, a new
     * password is generated using the createPassword() method.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function changePassword($pass = '')
    {
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
        // default constructor
        // database access
        /*if ($db !== null) {
            if (!$this->addDb($db))
                return false;
        }
        // permission object
        if ($perm !== null) {
            if (!$this->addPerm($perm))
                return false;
        }
        // authentication objects
        if (count($auth) > 0) {
        	foreach ($auth as $name => $auth_object) {
        		if (!$this->addAuth($auth_object, $name)) {
        		    return false;
					break;
        		}
        	}
        }*/
        // phpMyFAQ constructor
        // database access
        if ($db !== null) {
            // set given $db
            if (!$this->addDb($db))
                return false;
        } else {
            // or set global $db
            global $db;
            if (isset($db)) {
                if (!$this->addDb($db))
                    return false;
            } else {
                // no $db, no fun
                return false;
            }
        }
        // permission object
        if ($perm !== null) {
            // set given $perm
            if (!$this->addPerm($perm))
                return false;
        } else {
            // or make a new $perm object
            // check config for permission level
            if (!isset($PMF_CONF['permLevel'])) {
                $configFilePath = defined('PMF_ROOT_DIR') ? PMF_ROOT_DIR : dirname(dirname(__FILE__));
                if (file_exists($configFilePath.'/inc/config.php')){
                    include($configFilePath.'/inc/config.php');
                }
            }
            $permLevel = isset($PMF_CONF['permLevel']) ? $PMF_CONF['permLevel'] : 'basic';
            $perm = PMF_Perm::selectPerm($permLevel);
            $perm->addDb($this->_db);
            if (!$this->addPerm($perm))
                return false;
        }
        // authentication objects
        // always make a 'local' $auth object
        $this->_auth_container = array();
        $authLocal = PMF_Auth::selectAuth('db');
        $authLocal->selectEncType('md5');
        $authLocal->read_only(false);
        $authLocal->connect($this->_db, PMF_USER_SQLPREFIX.'userlogin', 'login', 'pass');
        if (!$this->addAuth($authLocal, 'local'))
            return false;
        // additionally, set given $auth objects
        if (count($auth) > 0) {
        	foreach ($auth as $name => $auth_object) {
        		if (!$this->addAuth($auth_object, $name)) {
        		    return false;
					break;
        		}
        	}
        } else {
        }
        // user data object
        $this->userdata = new PMF_UserData($this->_db);
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
        if (isset($this->_status) and strlen($this->_status) > 0) {
        	return $this->_status;
        }
        return false;
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
        // is status allowed?
        $status = strtolower($status);
        if (!in_array($status, array_keys($this->_allowed_status))) {
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
		    ".PMF_USER_SQLPREFIX."user
		  SET
		    account_status = '".$status."'
		  WHERE
		    user_id = ".$user_id
        );
		// return bool
		if ($res)
		    return true;
		return false;
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
        $methods = array('query', 'num_rows', 'fetch_assoc', 'error');
        foreach ($methods as $method) {
        	if (!method_exists($db, $method)) {
				$this->errors[] = PMF_USERERROR_NO_DB;
        		return false;
        		break;
        	}
        }
        return true;
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
        $message = '';
        foreach ($this->errors as $error) {
        	$message .= $error."\n";
        }
        $this->errors = array();
        return $message;
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
    }

    /**
     * returns true if login is a valid login string.
     *
     * $this->_login_minLength defines the minimum length the
     * login string. If login has more characters than allowed,
     * false is returned.
     * $this->_login_invalidRegExp is a regular expression.
     * If login matches this false is returned.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function isValidLogin($login)
    {
        $login = (string) $login;
        if (strlen($login) < $this->_login_minLength or preg_match($this->_login_invalidRegExp, $login)) {
        	$this->errors[] = PMF_USERERROR_LOGIN_INVALID;
            return false;
        }
        return true;
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
        if ($this->checkAuth($auth)) {
        	$this->_auth_container[$name] = $auth;
            return true;
        }
        return false;
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
        $methods = array('checkPassword');
        foreach ($methods as $method) {
        	if (!method_exists($auth, strtolower($method))) {
				$this->errors[] = PMF_USERERROR_NO_AUTH;
        		return false;
        		break;
        	}
        }
        return true;
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
        if (is_a($perm, 'pmf_perm'))
            return true;
        $this->errors[] = PMF_USERERROR_NO_PERM;
        return false;
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
        return $this->_login;
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
		srand((double)microtime()*1000000);
  		return (string) uniqid(rand());
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
        // get user-data entry
        if (!$this->userdata)
		    $this->userdata = new PMF_UserData($this->_db);
		return $this->userdata->get($field);
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
        // set user-data entry
        if (!$this->userdata)
		    $this->userdata = new PMF_UserData($this->_db);
		$this->userdata->load($this->getUserId());
		return $this->userdata->set(array_keys($data), array_values($data));
    }

    /**
     * Returns an array with the user-IDs of all users found in
     * the database.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getAllUsers()
    {
        $res = $this->_db->query("
            SELECT
                user_id
            FROM
                ".PMF_USER_SQLPREFIX."user
            WHERE
                1
        ");
        if (!$res)
            return array();
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
            $result[] = $row['user_id'];
        }
        return $result;
    }

    /**
     * sets the minimum login string length
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return void
     */
    function setLoginMinLength($loginMinLength)
    {
        if (is_int($loginMinLength))
            $this->_login_minLength = $loginMinLength;
    }

    /**
     * sets the regular expression to check invalid login strings
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return void
     */
    function setLoginInvalidRegExp($loginInvalidRegExp)
    {
        if (is_string($loginInvalidRegExp))
            $this->_login_invalidRegExp = $loginInvalidRegExp;
    }

} /* end of class PMF_User */

?>
