<?php
/**
 * Creates a new user object.
 *
 * A user are recognized by the session-id using getUserBySessionId(), by his
 * using getUserById() or by his nickname (login) using getUserByLogin(). New
 * are created using createNewUser().
 *
 * PHP Version 5.2
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
 * 
 * @category  phpMyFAQ
 * @package   PMF_User
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Sarah Hermann <sayh@gmx.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-17
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

if (!defined('PMF_ENCRYPTION_TYPE')) {
    define('PMF_ENCRYPTION_TYPE', 'md5'); // Fallback to md5()
}

/**
 * PMF_User
 *
 * @category  phpMyFAQ
 * @package   PMF_User
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Sarah Hermann <sayh@gmx.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-17
 */
class PMF_User
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
    const ERROR_USER_NO_LOGIN_DATA = 'A username and password must be provided. ';

    const STATUS_USER_PROTECTED = 'User account is protected. ';
    const STATUS_USER_BLOCKED = 'User account is blocked. ';
    const STATUS_USER_ACTIVE = 'User account is active. ';

    // --- ATTRIBUTES ---

    /**
     * Permission container
     *
     * @var PMF_Perm
     */
    public $perm = null;

    /**
     * User-data storage container
     *
     * @var PMF_User_UserData
     */
    public $userdata = null;

    /**
     * Encrypted password string
     *
     * @var string
     */
    public $encrypted_password = '';

    /**
     * Default Authentication properties
     *
     * @var array
     */
    public $auth_data = array(
        'authSource' => array(
            'name' => 'db',
            'type' => 'local'
        ),
        'encType'    => PMF_ENCRYPTION_TYPE,
        'readOnly'   => false
    );

    /**
     * Public array that contains error messages.
     *
     * @var array
     */
    public $errors = array();

    /**
     * database object
     *
     * @var PMF_DB_Driver
     */
    protected $db = null;
    
    /**
     * authentication container
     *
     * @var array
     */
    protected $authContainer = array();
    
    /**
     * login string
     *
     * @var string
     */
    private $login = '';

    /**
     * minimum length of login string (default: 4)
     *
     * @var int
     */
    private $login_minLength = 4;

    /**
     * regular expression to find invalid login strings
     * (default: /^[a-z0-9][\w\.\-@]+/i )
     *
     * @var string
     */
    private $_validRegExp = '/^[a-z0-9][\w\.\-@]+/i';
    
    /**
     * user ID
     *
     * @var integer
     */
    private $user_id = -1;
    
    /**
     * Status of user
     *
     * @var string
     */
    private $status = '';

    /**
     * array of allowed values for status
     *
     * @var array
     */
    private $allowed_status = array(
        'active'    => self::STATUS_USER_ACTIVE,
        'blocked'   => self::STATUS_USER_BLOCKED,
        'protected' => self::STATUS_USER_PROTECTED);

    /**
     * Constructor
     *
     * @param  PMF_Perm $perm Permission object
     * @param  array         $auth Authorization array
     * @return void
     */
    public function __construct(PMF_Perm $perm = null, Array $auth = array())
    {
        $this->db = PMF_Db::getInstance();

        if ($perm !== null) {
            if (!$this->addPerm($perm)) {
                return false;
            }
        } else {
            $permLevel = PMF_Configuration::getInstance()->get('security.permLevel');
            $perm      = PMF_Perm::selectPerm($permLevel);
            if (!$this->addPerm($perm)) {
                return false;
            }
        }
        
        // authentication objects
        // always make a 'local' $auth object (see: $auth_data)
        $this->authContainer = array();
        $authLocal = PMF_Auth::selectAuth($this->auth_data['authSource']['name']);
        $authLocal->selectEncType($this->auth_data['encType']);
        $authLocal->setReadOnly($this->auth_data['readOnly']);
        if (!$this->addAuth($authLocal, $this->auth_data['authSource']['type'])) {
            return false;
        }
        
        // additionally, set given $auth objects
        if (count($auth) > 0) {
            foreach ($auth as $name => $auth_object) {
                if (!$this->addAuth($auth_object, $name)) {
                    break;
                }
            }
        }
        // user data object
        $this->userdata = new PMF_User_UserData();
    }


    // --- OPERATIONS ---

    /**
     * adds a permission object to the user.
     *
     * @param  PMF_Perm $perm Permission object
     * @return boolean
     */
    public function addPerm(PMF_Perm $perm)
    {
        if ($this->checkPerm($perm)) {
            $this->perm = $perm;
            return true;
        }
        $this->perm = null;
        return false;
    }

    /**
     * Returns the User ID of the user.
     *
     * @return integer
     */
    public function getUserId()
    {
        if (isset($this->user_id) && is_int($this->user_id)) {
            return (int)$this->user_id;
        }
        $this->user_id  = -1;
        $this->errors[] = self::ERROR_USER_NO_USERID;
        
        return -1;
    }

    /**
     * Loads basic user information from the database selecting the user with
     * specified user-ID.
     *
     * @param  integer $user_id User ID
     * @return bool
     */
    public function getUserById($user_id)
    {
        $select = sprintf("
            SELECT
                user_id,
                login,
                account_status
            FROM
                %sfaquser
            WHERE
                user_id = %d",
             SQLPREFIX,
             (int) $user_id);
             
        $res = $this->db->query($select);
        if ($this->db->numRows($res) != 1) {
            $this->errors[] = self::ERROR_USER_NO_USERID . 'error(): ' . $this->db->error();
            return false;
        }
        $user          = $this->db->fetchArray($res);
        $this->user_id = (int)   $user['user_id'];
        $this->login   = (string)$user['login'];
        $this->status  = (string)$user['account_status'];
        
        // get encrypted password
        // TODO: Add a getAuthSource method to the User class for discovering what was the source of the (current) user authentication.
        // TODO: Add a getEncPassword method to the Auth* classes for the (local and remote) Auth Sources.
        if ('db' == $this->auth_data['authSource']['name']) {
            $select = sprintf("
                SELECT
                    pass
                FROM
                    %sfaquserlogin
                WHERE
                    login = '%s'",
                SQLPREFIX,
                $this->login);
                
            $res = $this->db->query($select);
            if ($this->db->numRows($res) != 1) {
                $this->errors[] = self::ERROR_USER_NO_USERLOGINDATA . 'error(): ' . $this->db->error();
                return false;
            }
            $loginData                = $this->db->fetchArray($res);
            $this->encrypted_password = (string) $loginData['pass'];
        }
        // get user-data
        if (!$this->userdata instanceof PMF_User_UserData) {
            $this->userdata = new PMF_User_UserData();
        }
        $this->userdata->load($this->getUserId());
        return true;
    }

    /**
     * loads basic user information from the database selecting the user with
     * specified login.
     *
     * @param  string $login       Login name
     * @param  bool   $raise_error Raise error?
     * @return bool
     */
    public function getUserByLogin($login, $raise_error = true)
    {
        $select = sprintf("
            SELECT
                user_id,
                login,
                account_status
            FROM
                %sfaquser
            WHERE
                login = '%s'",
            SQLPREFIX,
            $this->db->escape($login));
        
        $res = $this->db->query($select);
        if ($this->db->numRows($res) !== 1) {
            if ($raise_error) {

                $this->errors[] = self::ERROR_USER_INCORRECT_LOGIN;
            }
            return false;
        }
        $user = $this->db->fetchArray($res);
        $this->user_id = (int)    $user['user_id'];
        $this->login   = (string) $user['login'];
        $this->status  = (string) $user['account_status'];
        // get user-data
        if (!$this->userdata instanceof PMF_User_UserData) {
            $this->userdata = new PMF_User_UserData();
        }
        $this->userdata->load($this->getUserId());
        return true;
    }

    /**
     * search users by login
     *
     * @param  string $search Login name
     * @return array
     */
    public function searchUsers($search)
    {
        $select = sprintf("
            SELECT
                login, 
                user_id, 
                account_status
            FROM
                %sfaquser
            WHERE 
                login LIKE '%s'",
            SQLPREFIX,
            $this->db->escape($search.'%'));

        $res = $this->db->query($select);
        if (!$res) {
            return array();
        }

        $result = array();
        while ($row = $this->db->fetchArray($res)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * creates a new user and stores basic data in the database.
     *
     * @param  string  $login   Login name
     * @param  string  $pass    Password
     * @param  integer $user_id User ID
     * @return mixed
     */
    public function createUser($login, $pass = '', $user_id = 0)
    {
        foreach ($this->authContainer as $auth) {
            if (!$this->checkAuth($auth)) {
                return false;
            }
        }
        
        // is $login valid?
        $login = (string) $login;
        if (!$this->isValidLogin($login)) {
            return false;
        }
        
        // does $login already exist?
        if ($this->getUserByLogin($login, false)) {
            $this->errors[] = self::ERROR_USER_LOGIN_NOT_UNIQUE;
            return false;
        }
        
        // set user-ID
        if (0 == $user_id) {
            $this->user_id = (int) $this->db->nextId(SQLPREFIX.'faquser', 'user_id');
        } else {
            $this->user_id = $user_id;
        }
        
        // create user entry
        $insert = sprintf("
            INSERT INTO
                %sfaquser
            (user_id, login, session_timestamp, member_since)
                VALUES
            (%d, '%s', %d, '%s')",
            SQLPREFIX,
            $this->getUserId(),
            $this->db->escape($login),
            $_SERVER['REQUEST_TIME'],
            date('YmdHis', $_SERVER['REQUEST_TIME']));

        $this->db->query($insert);
        if (!$this->userdata instanceof PMF_User_UserData) {
            $this->userdata = new PMF_User_UserData($this->db);
        }
        $data = $this->userdata->add($this->getUserId());
        if (!$data) {
            $this->errors[] = self::ERROR_USER_CANNOT_CREATE_USERDATA;
            return false;
        }
        
        // create authentication entry
        if ($pass == '') {
            $pass = $this->createPassword();
        }
        $success = false;
        foreach ($this->authContainer as $name => $auth) {
            if ($auth->setReadOnly()) {
                continue;
            }
            if (!$auth->add($login, $pass)) {
                $this->errors[] = self::ERROR_USER_CANNOT_CREATE_USER.'in PMF_Auth '.$name;
            } else {
                $success = true;
            }
        }
        if (!$success) {
            return false;
        }

        $this->perm->autoJoin($this->user_id);
        return $this->getUserByLogin($login, false);
    }

    /**
     * deletes the user from the database.
     *
     * @return boolean
     */
    public function deleteUser()
    {
        if (!isset($this->user_id) || $this->user_id == 0) {
            $this->errors[] = self::ERROR_USER_NO_USERID;
            return false;
        }
        
        if (!isset($this->login) || strlen($this->login) == 0) {
            $this->errors[] = self::ERROR_USER_LOGIN_INVALID;
            return false;
        }
        
        if (isset($this->allowed_status[$this->status]) && $this->allowed_status[$this->status] == self::STATUS_USER_PROTECTED) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USER . self::STATUS_USER_PROTECTED;
            return false;
        }
        
        $this->perm->refuseAllUserRights($this->user_id);
        
        $delete = sprintf("
            DELETE FROM
                %sfaquser
            WHERE
                user_id = %d",
            SQLPREFIX,
            $this->user_id);
            
        $res = $this->db->query($delete);
        if (!$res) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USER . 'error(): ' . $this->db->error();
            return false;
        }
        
        if (!$this->userdata instanceof PMF_User_UserData) {
            $this->userdata = new PMF_User_UserData($this->db);
        }
        $data = $this->userdata->delete($this->getUserId());
        if (!$data) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USERDATA;
            return false;
        }
        
        $read_only  = 0;
        $auth_count = 0;
        $delete     = array();
        foreach ($this->authContainer as $auth) {
            $auth_count++;
            if ($auth->setReadOnly()) {
                $read_only++;
                continue;
            }
            $delete[] = $auth->delete($this->login);
        }
        
        if ($read_only == $auth_count) {
            $this->errors[] = self::ERROR_USER_NO_AUTH_WRITABLE;
        }
        if (!in_array(true, $delete)) {
            return false;
        }
        return true;
    }

    /**
     * changes the user's password. If $pass is omitted, a new
     * password is generated using the createPassword() method.
     *
     * @param  string $pass Password
     * @return boolean
     */
    public function changePassword($pass = '')
    {
        foreach ($this->authContainer as $auth) {
            if (!$this->checkAuth($auth)) {
                return false;
            }
        }
        
        $login = $this->getLogin();
        if ($pass == '') {
            $pass = $this->createPassword();
        }
        
        $success = false;
        foreach ($this->authContainer as $auth) {
            if ($auth->setReadOnly()) {
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
     * @return string
     */
    public function getStatus()
    {
        if (isset($this->status) && strlen($this->status) > 0) {
            return $this->status;
        }
        return false;
    }

    /**
     * sets the user's status and updates the database entry.
     *
     * @param  string $status Status
     * @return boolean
     */
    public function setStatus($status)
    {
        // is status allowed?
        $status = strtolower($status);
        if (!in_array($status, array_keys($this->allowed_status))) {
            $this->errors[] = self::ERROR_USER_INVALID_STATUS;
            return false;
        }
        
        // update status
        $this->status = $status;
        $update       = sprintf("
            UPDATE
                %sfaquser
            SET
                account_status = '%s'
            WHERE
                user_id = %d",
            SQLPREFIX,
            $this->db->escape($status),
            $this->user_id);
        
        $res = $this->db->query($update);
        
        if ($res) {
            return true;
        }
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
     * @return string
     */
    public function error()
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
     * $this->login_minLength defines the minimum length the
     * login string. If login has more characters than allowed,
     * false is returned.
     * $this->login_invalidRegExp is a regular expression.
     * If login matches this false is returned.
     *
     * @param  string $login Login name
     * @return boolean
     */
    public function isValidLogin($login)
    {
        $login = (string) $login;

        if (strlen($login) < $this->login_minLength || !preg_match($this->_validRegExp, $login)) {
            $this->errors[] = self::ERROR_USER_LOGIN_INVALID;
            return false;
        }
        return true;
    }

    /**
     * adds a new authentication object to the user object.
     *
     * @param  PMF_Auth $auth PMF_Auth object
     * @param  string   $name Auth name
     * @return boolean
     */
    public function addAuth($auth, $name)
    {
        if ($this->checkAuth($auth)) {
            $this->authContainer[$name] = $auth;
            return true;
        }
        return false;
    }

    /**
     * returns true if auth is a valid authentication object.
     *
     * @param  PMF_Auth $auth PMF_Auth object
     * @return bool
     */
    protected function checkAuth($auth)
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
     * Returns the data aof the auth container
     * @return array
     */
    public function getAuthContainer()
    {
        return $this->authContainer;
    }

    /**
     * returns true if perm is a valid permission object.
     *
     * @param  PMF_Perm $perm PMF_Perm object
     * @return bool
     */
    private function checkPerm($perm)
    {
        if ($perm instanceof PMF_Perm) {
            return true;
        }
        $this->errors[] = ERROR_USER_NO_PERM;
        return false;
    }

    /**
     * returns the user's login.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * returns a new password.
     *
     * @return string
     */
    private function createPassword()
    {
        srand((double)microtime() * 1000000);
        return (string) uniqid(rand());
    }

    /**
     * Returns the data of the current user
     *
     * @param  string $field Field
     * @return array
     */
    public function getUserData($field = '*')
    {
        if (!($this->userdata instanceof PMF_User_UserData)) {
            $this->userdata = new PMF_User_UserData();
        }
        return $this->userdata->get($field);
    }

    /**
     * Adds user data
     *
     * @param  array $data Array with user data
     * @return bool
     */
    public function setUserData(Array $data)
    {
        if (!($this->userdata instanceof PMF_User_UserData)) {
            $this->userdata = new PMF_User_UserData();
        }
        $this->userdata->load($this->getUserId());
        return $this->userdata->set(array_keys($data), array_values($data));
    }

    /**
     * Returns an array with the user-IDs of all users found in
     * the database. By default, the Anonymous User will not be returned.
     *
     * @param  boolean $withoutAnonymous Without anonymous?
     * @return array
     */
    public function getAllUsers($withoutAnonymous = true)
    {
        $select = sprintf("
            SELECT
                user_id
            FROM
                %sfaquser
            %s
            ORDER BY user_id ASC",
            SQLPREFIX,
            ($withoutAnonymous ? 'WHERE user_id <> -1' : ''));

        $res = $this->db->query($select);
        if (!$res) {
            return array();
        }

        $result = array();
        while ($row = $this->db->fetchArray($res)) {
            $result[] = $row['user_id'];
        }

        return $result;
    }

    /**
     * Returns an array of all users found in the database. By default, the 
     * anonymous User will not be returned. The returned array contains the
     * user ID as key, the values are login name, account status, authentication
     * source and the user creation date.
     *
     * @param  boolean $withoutAnonymous Without anonymous?
     * @return array
     */
    public function getAllUserData($withoutAnonymous = true)
    {
        $select = sprintf("
            SELECT
                user_id, login, account_status, auth_source, member_since
            FROM
                %sfaquser
            %s
            ORDER BY
               login ASC",
            SQLPREFIX,
            ($withoutAnonymous ? 'WHERE user_id <> -1' : ''));

        $res = $this->db->query($select);
        if (!$res) {
            return array();
        }

        $result = array();
        while ($row = $this->db->fetchArray($res)) {
            $result[$row['user_id']] = $row;
        }

        return $result;
    }
    
    /**
     * Get all users in <option> tags
     *
     * @param  integer $user_id User ID
     * @return string
     */
    public function getAllUserOptions($id = 1)
    {
        $options  = '';
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
     * @param  integer $loginMinLength Minimum length of login name
     * @return void
     */
    public function setLoginMinLength($loginMinLength)
    {
        if (is_int($loginMinLength)) {
            $this->login_minLength = $loginMinLength;
        }
    }
}
