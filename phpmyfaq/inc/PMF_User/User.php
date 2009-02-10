<?php
/**
 * Creates a new user object.
 *
 * A user are recognized by the session-id using getUserBySessionId(), by his
 * using getUserById() or by his nickname (login) using getUserByLogin(). New
 * are created using createNewUser().
 *
 * @package     phpMyFAQ
 * @author      Lars Tiedemann <php@larstiedemann.de>
 * @since       2005-09-17
 * @copyright   (c) 2005-2009 phpMyFAQ Team
 * @version     SVN: $Id$
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */
class PMF_User_User
{
    const ERROR_UNDEFINED_PARAMETER = 'Following parameter must to be defined: ';
    const ERROR_USER_ADD = 'Account could not be created. ';
    const ERROR_USER_CANNOT_CREATE_USER = 'User account could not be created. ';
    const ERROR_USER_CANNOT_CREATE_USERDATA = 'Entry for user data could not be created. ';
    const ERROR_USER_CANNOT_DELETE_USER = 'User account could not be deleted. ';
    const ERROR_USER_CANNOT_DELETE_USERDATA = 'Entry for user data could not be deleted. ';
    const ERROR_USER_CANNOT_UPDATE_USERDATA = 'Entry for user data could not be updated. ';
    const ERROR_USER_CHANGE = 'Account could not be updated. ';
    const ERROR_USER_DELETE = 'Account could not be deleted. ';
    const ERROR_USER_INCORRECT_LOGIN = 'Specified login could not be found. ';
    const ERROR_USER_INCORRECT_PASSWORD = 'Specified password is not correct.';
    const ERROR_USER_INVALID_STATUS = 'Undefined user status.';
    const ERROR_USER_LOGIN_NOT_UNIQUE = 'Specified login name already exists. ';
    const ERROR_USER_LOGIN_INVALID = 'The chosen login is invalid. A valid login has at least four characters. Only letters, numbers and underscore _ are allowed. The first letter must be a letter. ';
    const ERROR_USER_NO_AUTH = 'No authentication method specified. ';
    const ERROR_USER_NO_DB = 'No database specified.';
    const ERROR_USER_NO_PERM = 'No permission container specified.';
    const ERROR_USER_NO_USERID = 'No user-ID found. ';
    const ERROR_USER_NO_USERLOGINDATA = 'No user login data found. ';
    const ERROR_USER_NOT_FOUND = 'User account could not be found. ';
    const ERROR_USER_NOWRITABLE = 'No authentication object is writable. ';

    const STATUS_USER_PROTECTED = 'User account is protected. ';
    const STATUS_USER_BLOCKED = 'User account is blocked. ';
    const STATUS_USER_ACTIVE = 'User account is active. ';

    // --- ATTRIBUTES ---

    /**
     * Permission container
     *
     * @var object
     */
    public $perm = null;

    /**
     * User-data storage container
     *
     * @var object
     */
    public $userdata = null;

    /**
     * database object
     *
     * @var object
     */
    protected $_db = null;

    /**
     * login string
     *
     * @access private
     * @var string
     */
    private $_login = '';

    /**
     * minimum length of login string (default: 4)
     *
     * @access private
     * @var int
     */
    private $_login_minLength = 4;

    /**
     * regular expression to find invalid login strings
     * (default: /(^[^a-z]{1}|[\W])/i )
     *
     * @access private
     * @var string
     */
    private $_login_invalidRegExp = '/(^[^a-z]{1}|[\W])/i';

    /**
     * Encrypted password string
     *
     * @access private
     * @var string
     */
    public $_encrypted_password = '';

    /**
     * Default Authentication properties
     *
     * @access private
     * @var array
     */
    public $_auth_data = array('authSource' => array('name' => 'db', 'type' => 'local'),
                               'encType'    => 'md5',
                               'readOnly'   => false);

    /**
     * user ID
     *
     * @access private
     * @var int
     */
    private $_user_id = 0;

    /**
     * Public array that contains error messages.
     *
     * @access public
     * @var array
     */
    public $errors = array();

    /**
     * status of user.
     *
     * @access private
     * @var string
     */
    private $_status = '';

    /**
     * array of allowed values for status
     *
     * @access private
     * @var array
     */
    private $_allowed_status = array(
        'active'    => self::STATUS_USER_ACTIVE,
        'blocked'   => self::STATUS_USER_BLOCKED,
        'protected' => self::STATUS_USER_PROTECTED);

    /**
     * authentication container
     *
     * @access private
     * @var array
     */
    protected $_auth_container = array();

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
    function __construct($db = null, $perm = null, $auth = array())
    {
        if ($db !== null) {
            if (!$this->addDb($db)) {
                return false;
            }
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
            global $PMF_CONF;
            $permLevel = isset($PMF_CONF['main.permLevel']) && ('' != $PMF_CONF['main.permLevel']) ? $PMF_CONF['main.permLevel'] : 'basic';
            $perm = PMF_User_Perm::selectPerm($permLevel);
            $perm->addDb($this->_db);
            if (!$this->addPerm($perm))
                return false;
        }
        // authentication objects
        // always make a 'local' $auth object (see: $_auth_data)
        $this->_auth_container = array();
        $authLocal = PMF_User_Auth::selectAuth($this->_auth_data['authSource']['name']);
        $authLocal->selectEncType($this->_auth_data['encType']);
        $authLocal->read_only($this->_auth_data['readOnly']);
        $authLocal->connect($this->_db, SQLPREFIX.'faquserlogin', 'login', 'pass');
        if (!$this->addAuth($authLocal, $this->_auth_data['authSource']['type'])) {
            return false;
        }
        // additionally, set given $auth objects
        if (count($auth) > 0) {
            foreach ($auth as $name => $auth_object) {
                if (!$this->addAuth($auth_object, $name)) {
                    break;
                }
            }
        } else {
        }
        // user data object
        $this->userdata = new PMF_User_UserData($this->_db);
    }


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
        $this->_db = $db;
        return true;
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
        if (isset($this->_user_id) && is_int($this->_user_id)) {
            return (int)$this->_user_id;
        }
        $this->_user_id = 0;
        $this->errors[] = self::ERROR_USER_NO_USERID;
        return 0;
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
        // TODO: Do we really need that check? :-)
        if (!$this->_db instanceof PMF_IDB_Driver) {
            return false;
	}

        // get user
        $query = sprintf(
                    "SELECT
                        user_id,
                        login,
                        account_status
                    FROM
                        %sfaquser
                    WHERE
                        user_id = %d",
                    SQLPREFIX,
                    (int) $user_id
                    );
        $res = $this->_db->query($query);
        if ($this->_db->num_rows($res) != 1) {
            $this->errors[] = self::ERROR_USER_NO_USERID . 'error(): ' . $this->_db->error();
            return false;
        }
        $user = $this->_db->fetch_assoc($res);
        $this->_user_id = (int)    $user['user_id'];
        $this->_login   = (string) $user['login'];
        $this->_status  = (string) $user['account_status'];
        // get encrypted password
        // TODO: Add a getAuthSource method to the User class for discovering what was the source of the (current) user authentication.
        // TODO: Add a getEncPassword method to the Auth* classes for the (local and remote) Auth Sources.
        if ('db' == $this->_auth_data['authSource']['name']) {
            $query = sprintf(
                        "SELECT
                            pass
                        FROM
                            %sfaquserlogin
                        WHERE
                            login = '%s'",
                        SQLPREFIX,
                        $this->_login
                        );
            $res = $this->_db->query($query);
            if ($this->_db->num_rows($res) != 1) {
                $this->errors[] = self::ERROR_USER_NO_USERLOGINDATA . 'error(): ' . $this->_db->error();
                return false;
            }
            $loginData = $this->_db->fetch_assoc($res);
            $this->_encrypted_password = (string) $loginData['pass'];
        }
        // get user-data
        if (!$this->userdata)
            $this->userdata = new PMF_User_UserData($this->_db);
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
        if (!is_object($this->_db)) {
            return false;
        }
        // get user
        $res = $this->_db->query("
          SELECT
            user_id,
            login,
            account_status
          FROM
            ".SQLPREFIX."faquser
          WHERE
            login = '".$this->_db->escape_string($login)."'
        ");
        if ($this->_db->num_rows($res) != 1) {
            if ($raise_error)
                $this->errors[] = self::ERROR_USER_INCORRECT_LOGIN;
            return false;
        }
        $user = $this->_db->fetch_assoc($res);
        $this->_user_id = (int)    $user['user_id'];
        $this->_login   = (string) $user['login'];
        $this->_status  = (string) $user['account_status'];
        // get user-data
        if (!$this->userdata)
            $this->userdata = new PMF_User_UserData($this->_db);
        $this->userdata->load($this->getUserId());
        return true;
    }

    /**
     * search users by login
     *
     * @access public
     * @author Sarah Hermann <sayh@gmx.de>
     * @param  string
     * @return array
     */
    function searchUsers($search)
    {
        $query = sprintf("
                    SELECT
                        login, user_id, account_status
                    FROM
                        %sfaquser
                    WHERE login LIKE '%s'",
                    SQLPREFIX,
                    $this->_db->escape_string($search.'%')
                    );

        $res = $this->_db->query($query);
        if (!$res) {
            return array();
        }

        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * creates a new user and stores basic data in the database.
     *
     * @access  public
     * @author  Lars Tiedemann, <php@larstiedemann.de>
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @param   string
     * @param   string
     * @param   integer
     * @return  mixed
     */
    function createUser($login, $pass = '', $user_id = 0)
    {
        if (!$this->_db instanceof PMF_IDB_Driver) {
            return false;
        }

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
            $this->errors[] = self::ERROR_USER_LOGIN_NOT_UNIQUE;
            return false;
        }
        // set user-ID
        if (0 == $user_id) {
            $this->_user_id = (int) $this->_db->nextID(SQLPREFIX.'faquser', 'user_id');
        } else {
            $this->_user_id = $user_id;
        }
        // create user entry
        $now = $_SERVER['REQUEST_TIME'];
        $query = sprintf(
                    "INSERT INTO
                        %sfaquser
                        (user_id, login, session_timestamp, member_since)
                    VALUES
                        (%d, '%s', %d, '%s')",
                    SQLPREFIX,
                    $this->getUserId(),
                    $this->_db->escape_string($login),
                    $now,
                    date('YmdHis', $now)
                    );

        $this->_db->query($query);
        // create user-data entry
        if (!$this->userdata)
            $this->userdata = new PMF_User_UserData($this->_db);
        $data = $this->userdata->add($this->getUserId());
        if (!$data) {
            $this->errors[] = self::ERROR_USER_CANNOT_CREATE_USERDATA;
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
                $this->errors[] = self::ERROR_USER_CANNOT_CREATE_USER.'in PMF_User_Auth '.$name;
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
            $this->errors[] = self::ERROR_USER_NO_USERID;
            return false;
        }
        // check login
        if (!isset($this->_login) or strlen($this->_login) == 0) {
            $this->errors[] = self::ERROR_USER_LOGIN_INVALID;
            return false;
        }
        // user-account is protected
        if (isset($this->_allowed_status[$this->_status]) and $this->_allowed_status[$this->_status] == self::STATUS_USER_PROTECTED) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USER . self::STATUS_USER_PROTECTED;
            return false;
        }
        // check db
        if (!$this->_db instanceof PMF_IDB_Driver) {
            return false;
        }
        // delete user rights
        $this->perm->refuseAllUserRights($this->_user_id);
        // delete user account
        $res = $this->_db->query("
          DELETE FROM
            ".SQLPREFIX."faquser
          WHERE
            user_id = ".$this->_user_id
        );
        if (!$res) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USER . 'error(): ' . $this->_db->error();
            return false;
        }
        // delete user-data entry
        if (!$this->userdata)
            $this->userdata = new PMF_User_UserData($this->_db);
        $data = $this->userdata->delete($this->getUserId());
        if (!$data) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USERDATA;
            return false;
        }
        // delete authentication entry
        $read_only = 0;
        $auth_count = 0;
        $delete = array();
        foreach ($this->_auth_container as $auth) {
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
            $this->errors[] = self::ERROR_USER_NO_AUTH_WRITABLE;
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
        if (!$this->_db instanceof PMF_IDB_Driver) {
            return false;
        }

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
        foreach ($this->_auth_container as $auth) {
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
            $this->errors[] = self::ERROR_USER_INVALID_STATUS;
            return false;
        }
        // check user-ID
        $user_id = $this->getUserId();
        if (!$user_id) {
            $this->errors[] = self::ERROR_USER_NO_USERID;
            return false;
        }
        // check db
        if (!$this->_db) {
            $this->errors[] = self::ERROR_USER_NO_DB;
            return false;
        }
        // update status
        $this->_status = $status;
        $res = $this->_db->query("
          UPDATE
            ".SQLPREFIX."faquser
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
            $this->errors[] = self::ERROR_USER_LOGIN_INVALID;
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
                $this->errors[] = self::ERROR_USER_NO_AUTH;
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
        if ($perm instanceof PMF_User_Perm) {
            return true;
        }
        $this->errors[] = ERROR_USER_NO_PERM;
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
        {
            $this->userdata = new PMF_User_UserData($this->_db);
        }
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
            $this->userdata = new PMF_User_UserData($this->_db);
        $this->userdata->load($this->getUserId());
        return $this->userdata->set(array_keys($data), array_values($data));
    }

    /**
     * Returns an array with the user-IDs of all users found in
     * the database. By default, the Anonymous User will not be returned.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getAllUsers($withoutAnonymous = true)
    {
        $query = sprintf("
                    SELECT
                        user_id
                    FROM
                        %sfaquser
                    %s
                    ORDER BY
                        login ASC",
                    SQLPREFIX,
                    ($withoutAnonymous ? 'WHERE user_id <> -1' : '')
                    );

        $res = $this->_db->query($query);
        if (!$res) {
            return array();
        }

        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
            $result[] = $row['user_id'];
        }

        return $result;
    }

    /**
     * Get all users in <option> tags
     *
     * @param   integer $user_id
     * @return  string
     * @access  public
     * @since   2006-08-15
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllUserOptions($id = 1)
    {
        $options = '';
        $allUsers = $this->getAllUsers();
        foreach ($allUsers as $user_id) {
            if (-1 != $user_id) {
                $this->getUserById($user_id);
                $options .= sprintf('<option value="%d"%s>%s (%s)</option>',
                    $user_id,
                    (($user_id == $id) ? ' selected="selected"' : ''),
                    $this->getLogin(),
                    $this->getUserData('display_name'));
            }
        }
        return $options;
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

}
