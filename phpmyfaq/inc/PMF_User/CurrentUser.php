<?php
/**
 * Manages authentication process using php sessions.
 *
 * The CurrentUser class is an extension of the User class. It provides methods
 * manage user authentication using multiple database accesses.
 * There are three ways of making a new current user object, using
 * the login() method, getFromSession() method or manually.
 * login() and getFromSession() may be combined.
 *
 * PHP version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_User
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-28
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/* user defined constants */
define('PMF_SESSION_CURRENT_USER', 'PMF_CURRENT_USER');
define('PMF_SESSION_ID_TIMESTAMP', 'PMF_SESSION_TIMESTAMP');
define('PMF_SESSION_ID_EXPIRES', PMF_AUTH_TIMEOUT);
define('PMF_SESSION_ID_REFRESH', PMF_AUTH_TIMEOUT_WARNING);
define('PMF_LOGIN_BY_SESSION', true);
define('PMF_LOGIN_BY_SESSION_FAILED', 'Could not login user from session. ');
define('PMF_LOGIN_BY_AUTH_FAILED', 'Could not login with login and password. ');

/**
 * PMF_User_CurrentUser
 *
 * @category  phpMyFAQ
 * @package   PMF_User
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-28
 */
class PMF_User_CurrentUser extends PMF_User
{
    /**
     * true if CurrentUser is logged in, otherwise false.
     * Call isLoggedIn() to check.
     *
     * @var bool
     */
    private $logged_in = false;

    /**
     * Session timeout
     *
     * Specifies the timeout for the session in minutes. If the
     * session-ID was not updated for the last
     * $this->session_timeout minutes, the CurrentUser will be
     * logged out automatically.
     *
     * @var integer
     */
    private $session_timeout = PMF_SESSION_ID_EXPIRES;

    /**
     * Session-ID timeout
     *
     * Specifies the timeout for the session-ID in minutes. If the
     * session-ID was not updated for the last
     * $this->session_id_timeout minutes, it will be updated. If
     * set to 0, the session-ID will be updated on every click.
     * The session-ID timeout must not be greater than Session
     * timeout.
     *
     * @access private
     * @var int
     */
    private $session_id_timeout = 1;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_User_CurrentUser
     */
    function __construct(PMF_Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * login()
     *
     * Checks the given login and password in all auth-objects.
     * Returns true on success, otherwise false. Raises errors
     * that can be checked using the error() method. On success,
     * the CurrentUser instance will be stored in the session and
     * labeled as logged in. The name of the successful auth
     * container will be stored in the user table.
     * A new auth object may be added by using addAuth() method.
     * The given password must not be encrypted, since the auth
     * object takes care about the encryption method.
     *
     * @param string $login     Loginname
     * @param string $password  Password
     *
     * @return bool
     */
    public function login($login, $password)
    {
        // ToDo: the option should be in the configuration of the DB
        //   instead of inc/dataldap.php
        global $PMF_LDAP;
        
        $optData = array();
        if ($PMF_LDAP['ldap_use_domain_prefix']) {
            if (($pos = strpos($login, '\\')) !== false) {
                if ($pos != 0) {
                    $optData['domain'] = substr($login, 0, $pos);
                }

                $login = substr($login, $pos+1);
            }
        }
        
        // authenticate user by login and password
        $loginError     = 0;
        $passwordError  = 0;
        $count          = 0;
        
        foreach ($this->authContainer as $name => $auth) {
            $count++;

            // $auth is an invalid Auth object, so continue
            if (!$this->checkAuth($auth)) {
                $count--;
                continue;
            }
            // $login does not exist, so continue
            if (!$auth->checkLogin($login, $optData)) {
                $loginError++;
                continue;
            }
            // $login exists, but $pass is incorrect, so stop!
            if (!$auth->checkPassword($login, $password, $optData)) {
                $passwordError++;
                // Don't stop, as other auth method could work:
                continue;
            }
            
            // but hey, this must be a valid match!
            // load user object
            $this->getUserByLogin($login);
            // user is now logged in
            $this->logged_in = true;
            // update last login info, session-id and save to session
            $this->updateSessionId(true);
            $this->saveToSession();
            $this->saveCrsfTokenToSession();
            
            // remember the auth container for administration
            $update = sprintf("
                UPDATE
                    %sfaquser
                SET
                    auth_source = '%s'
                WHERE
                    user_id = %d",
                SQLPREFIX,
                $this->db->escape($name),
                $this->getUserId());
            $res = $this->db->query($update);
            if (!$res) {
                return false;
                break;
            }
            
            // return true
            return true;
            break;
        }
        
        // raise errors and return false
        if ($loginError == $count) {
            $this->errors[] = parent::ERROR_USER_INCORRECT_LOGIN;
        }
        if ($passwordError > 0) {
            $this->errors[] = parent::ERROR_USER_INCORRECT_PASSWORD;
        }
        return false;
    }

    /**
     * Returns true if CurrentUser is logged in, otherwise false.
     *
     * @return boolean
     */
    public function isLoggedIn()
    {
        return $this->logged_in;
    }

    /**
     * Returns false if the CurrentUser object stored in the
     * session is valid and not timed out. There are two
     * parameters for session timeouts: $this->session_timeout
     * and $this->session_id_timeout.
     *
     * @return boolean
     */
    public function sessionIsTimedOut()
    {
        if ($this->session_timeout <= $this->sessionAge()) {
            return true;
        }
        return false;
    }

    /**
     * Returns false if the session-ID is not timed out.
     *
     * @return boolean
     */
    public function sessionIdIsTimedOut()
    {
        if ($this->session_id_timeout <= $this->sessionAge()) {
            return true;
        }
        return false;
    }

    /**
     * Returns the age of the current session-ID in minutes.
     *
     * @return float
     */
    public function sessionAge()
    {
        if (!isset($_SESSION[PMF_SESSION_ID_TIMESTAMP])) {
            return 0;
        }
        return ($_SERVER['REQUEST_TIME'] - $_SESSION[PMF_SESSION_ID_TIMESTAMP]) / 60;
    }

    /**
     * Returns an associative array with session information stored
     * in the user table. The array has the following keys:
     * session_id, session_timestamp and ip.
     *
     * @return array
     */
    public function getSessionInfo()
    {
    	$select = sprintf("
            SELECT
                session_id,
                session_timestamp,
                ip
            FROM
                %sfaquser
            WHERE
                user_id = %d",
    	   SQLPREFIX,
    	   $this->getUserId());
    	   
        $res = $this->db->query($select);
        if (!$res or $this->db->numRows($res) != 1) {
            return array();
        }
        return $this->db->fetchArray($res);
    }

    /**
     * Updates the session-ID, does not care about time outs.
     * Stores session information in the user table: session_id,
     * session_timestamp and ip.
     * Optionally it should update the 'last login' time.
     * Returns true on success, otherwise false.
     *
     * @param  boolean $updateLastlogin Update the last login time?
     * @return boolean
     */
    public function updateSessionId($updateLastlogin = false)
    {
        // renew the session-ID
        $oldSessionId = session_id();
        if (session_regenerate_id(true)) {
        	$sessionPath = session_save_path();
            if (strpos ($sessionPath, ';') !== false) {
                $sessionPath = substr ($sessionPath, strpos ($sessionPath, ';') + 1);
            }
            $sessionFilename = $sessionPath . '/sess_' . $oldSessionId;
            if (@file_exists($sessionFilename)) {
                @unlink($sessionFilename);
            }
        }
        // store session-ID age
        $_SESSION[PMF_SESSION_ID_TIMESTAMP] = $_SERVER['REQUEST_TIME'];
        // save session information in user table
        $update = sprintf("
            UPDATE
                %sfaquser
            SET
                session_id = '%s',
                session_timestamp = %d,
                %s
                ip = '%s'
            WHERE
                user_id = %d",
            SQLPREFIX,
            session_id(),
            $_SERVER['REQUEST_TIME'],
            $updateLastlogin ?  "last_login = '".date('YmdHis', $_SERVER['REQUEST_TIME'])."'," : '',
            $_SERVER['REMOTE_ADDR'],
            $this->getUserId());
                    
        $res = $this->db->query($update);
        if (!$res) {
            $this->errors[] = $this->db->error();
            return false;
        }

        return true;
    }

    /**
     * Saves the CurrentUser into the session. This method
     * may be called after a successful login.
     *
     * @return void
     */
    public function saveToSession()
    {
        $_SESSION[PMF_SESSION_CURRENT_USER] = $this->getUserId();
    }

    /**
     * Deletes the CurrentUser from the session. The user
     * will be logged out. Return true on success, otherwise false.
     *
     * @return boolean
     */
    public function deleteFromSession()
    {
        // delete CSRF Token
        $this->deleteCsrfTokenFromSession();
        
        // delete CurrentUser object from session
        $_SESSION[PMF_SESSION_CURRENT_USER] = null;
        unset($_SESSION[PMF_SESSION_CURRENT_USER]);
        // log CurrentUser out
        $this->logged_in = false;
        // delete session-ID
        $update = sprintf("
            UPDATE
                %sfaquser
            SET
                session_id = null
            WHERE
                user_id = %d",
                SQLPREFIX,
                $this->getUserId());
                
        $res = $this->db->query($update);
        if (!$res) {
            $this->errors[] = $this->db->error();
            return false;
        }
        return true;
    }

    /**
     * This static method returns a valid CurrentUser object if
     * there is one in the session that is not timed out.
     * If the the optional parameter ip_check is true, the current
     * user must have the same ip which is stored in the user table
     * The session-ID is updated if necessary. The CurrentUser
     * will be removed from the session, if it is timed out. If
     * there is no valid CurrentUser in the session or the session
     * is timed out, null will be returned. If the session data is
     * correct, but there is no user found in the user table, false
     * will be returned. On success, a valid CurrentUser object is
     * returned.
     *
     * @param  PMF_Configuration $config
     *
     * @return PMF_User_CurrentUser
     */
    public static function getFromSession(PMF_Configuration $config)
    {
        // there is no valid user object in session
        if (!isset($_SESSION[PMF_SESSION_CURRENT_USER]) || !isset($_SESSION[PMF_SESSION_ID_TIMESTAMP])) {
            return null;
        }
        // create a new CurrentUser object
        $user = new PMF_User_CurrentUser($config);
        $user->getUserById($_SESSION[PMF_SESSION_CURRENT_USER]);
        // user object is timed out
        if ($user->sessionIsTimedOut()) {
            $user->deleteFromSession();
            return null;
        }
        // session-id not found in user table
        $session_info = $user->getSessionInfo();
        $session_id   = (isset($session_info['session_id']) ? $session_info['session_id'] : '');
        if ($session_id == '' || $session_id != session_id()) {
            return false;
        }
        // check ip
        if ($config->get('security.ipCheck') &&
            $session_info['ip'] != $_SERVER['REMOTE_ADDR']) {
            return false;
        }
        // session-id needs to be updated
        if ($user->sessionIdIsTimedOut()) {
            $user->updateSessionId();
        }
        // user is now logged in
        $user->logged_in = true;
        // save current user to session and return the instance
        $user->saveToSession();
        
        return $user;
    }

    /**
     * Sets the number of minutes when the current user stored in
     * the session gets invalid.
     *
     * @param  float $timeout Timeout
     * @return void
     */
    public function setSessionTimeout($timeout)
    {
        $this->session_timeout = abs($timeout);
    }

    /**
     * Sets the number of minutes when the session-ID needs to be
     * updated. By setting the session-ID timeout to zero, the
     * session-ID will be updated on each click.
     *
     * @param  float $timeout Timeout
     * @return void
     */
    public function setSessionIdTimeout($timeout)
    {
        $this->session_id_timeout = abs($timeout);
    }
    
    /**
     * Returns the CSRF token from session
     *
     * @return string
     */
    public function getCsrfTokenFromSession()
    {
        return $_SESSION['phpmyfaq_csrf_token'];
    }
    
    /**
     * Save CSRF token to session
     *
     * @return void
     */
    protected function saveCrsfTokenToSession()
    {
        if (!isset($_SESSION['phpmyfaq_csrf_token'])) {
            $_SESSION['phpmyfaq_csrf_token'] = $this->createCsrfToken();
        }
    }
    
    /**
     * Deletes CSRF token from session
     *
     * @return void
     */
    protected function deleteCsrfTokenFromSession()
    {
        unset($_SESSION['phpmyfaq_csrf_token']);
    }
    
    /**
     * Creates a CSRF token
     *
     * @return string
     */
    private function createCsrfToken()
    {
        return sha1(microtime() . $this->getLogin());
    }
}
