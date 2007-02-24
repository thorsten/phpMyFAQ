<?php
/**
 * $Id: CurrentUser.php,v 1.23 2007-02-24 07:21:43 thorstenr Exp $
 *
 * manages authentication process using php sessions.
 *
 * The CurrentUser class is an extension of the User class. It provides methods
 * manage user authentication using multiple database accesses.
 *
 * @author Lars Tiedemann, <php@larstiedemann.de>
 * @package PMF
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

/* user defined includes */
require_once dirname(__FILE__).'/User.php';

/* user defined constants */
@define('PMF_SESSION_CURRENT_USER', 'PMF_CURRENT_USER');
@define('PMF_SESSION_ID_TIMESTAMP', 'PMF_SESSION_TIMESTAMP');
@define('PMF_SESSION_ID_EXPIRES', PMF_AUTH_TIMEOUT);
@define('PMF_SESSION_ID_REFRESH', PMF_AUTH_TIMEOUT_WARNING);
@define('PMF_LOGIN_BY_SESSION', true);
@define('PMF_LOGIN_BY_SESSION_FAILED', 'Could not login user from session. ');
@define('PMF_LOGIN_BY_AUTH_FAILED', 'Could not login with login and password. ');
@define('PMF_USERERROR_INCORRECT_PASSWORD', 'Specified password is not correct. ');

/**
* manages authentication process using php sessions.
*
* The CurrentUser class is an extension of the User class. It provides methods
* manage user authentication using multiple database accesses.
* There are three ways of making a new current user object, using
* the login() method, getFromSession() method or manually.
* login() and getFromSession() may be combined.
*
* @access public
* @author Lars Tiedemann, <php@larstiedemann.de>
* @package PMF
*/
class PMF_CurrentUser extends PMF_User
{
    /**
    * TRUE if CurrentUser is logged in, otherwise false.
    * Call isLoggedIn() to check.
    *
    * @access private
    * @var bool
    */
    var $_logged_in = false;

    /**
    * Session timeout
    *
    * Specifies the timeout for the session in minutes. If the
    * session-ID was not updated for the last
    * $this->_session_timeout minutes, the CurrentUser will be
    * logged out automatically.
    *
    * @access private
    * @var int
    */
    var $_session_timeout = PMF_SESSION_ID_EXPIRES;

    /**
    * Session-ID timeout
    *
    * Specifies the timeout for the session-ID in minutes. If the
    * session-ID was not updated for the last
    * $this->_session_id_timeout minutes, it will be updated. If
    * set to 0, the session-ID will be updated on every click.
    * The session-ID timeout must not be greater than Session
    * timeout.
    *
    * @access private
    * @var int
    */
    var $_session_id_timeout = 1;

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_CurrentUser()
    {
        $this->PMF_User();
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
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    string
    * @param    string
    * @return   bool
    */
    function login($login, $pass)
    {
        // authenticate user by login and password
        $login_error = 0;
        $pass_error  = 0;
        $count = 0;
        foreach ($this->_auth_container as $name => $auth) {
            $count++;

            // $auth is an invalid Auth object, so continue
            if (!$this->checkAuth($auth)) {
                $count--;
                continue;
            }
            // $login does not exist, so continue
            if (!$auth->checkLogin($login)) {
                $login_error++;
                continue;
            }
            // $login exists, but $pass is incorrect, so stop!
            if (!$auth->checkPassword($login, $pass)) {
                $pass_error++;
                break;
            }
            // but hey, this must be a valid match!
            // load user object
            $this->getUserByLogin($login);
            // user is now logged in
            $this->_logged_in = true;
            // update last login info, session-id and save to session
            $this->updateSessionId(true);
            $this->saveToSession();
            // remember the auth container for administration
            $res = $this->_db->query("
                UPDATE
                    ".PMF_USER_SQLPREFIX."user
                SET
                    auth_source = '".$name."'
                WHERE
                    user_id = ".$this->getUserId()
            );
            if (!$res) {
                return false;
                break;
            }
            // Save encrypted password just for "Change Password" convenience
            $_authLocal = PMF_Auth::selectAuth($this->_auth_data['authSource']['name']);
            $_authLocal->selectEncType($this->_auth_data['encType']);
            $_authLocal->read_only($this->_auth_data['readOnly']);
            $this->_encrypted_password = $_authLocal->encrypt($pass);
            // return true
            return true;
            break;
        }
        // raise errors and return false
        if ($login_error == $count) {
            $this->errors[] = PMF_USERERROR_INCORRECT_LOGIN;
        }
        if ($pass_error > 0) {
            $this->errors[] = PMF_USERERROR_INCORRECT_PASSWORD;
        }
        return false;
    }

    /**
    * isLoggedIn()
    *
    * Returns true if CurrentUser is logged in, otherwise false.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @return   bool
    */
    function isLoggedIn()
    {
        return $this->_logged_in;
    }

    /**
    * sessionIsTimedOut()
    *
    * Returns false if the CurrentUser object stored in the
    * session is valid and not timed out. There are two
    * parameters for session timeouts: $this->_session_timeout
    * and $this->_session_id_timeout.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @return   bool
    */
    function sessionIsTimedOut()
    {
        if ($this->_session_timeout <= $this->sessionAge()) {
            return true;
        }
        return false;
    }

    /**
    * sessionIdIsTimedOut()
    *
    * Returns false if the session-ID is not timed out.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @return   bool
    */
    function sessionIdIsTimedOut()
    {
        if ($this->_session_id_timeout <= $this->sessionAge()) {
            return true;
        }
        return false;
    }

    /**
    * sessionAge()
    *
    * Returns the age of the current session-ID in minutes.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @return   float
    */
    function sessionAge()
    {
        if (!isset($_SESSION[PMF_SESSION_ID_TIMESTAMP])) {
            return 0;
        }
        return (time() - $_SESSION[PMF_SESSION_ID_TIMESTAMP]) / 60;
    }

    /**
    * getSessionInfo()
    *
    * Returns an associative array with session information stored
    * in the user table. The array has the following keys:
    * session_id, session_timestamp and ip.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @return   array
    */
    function getSessionInfo()
    {
        $res = $this->_db->query("
            SELECT
                session_id,
                session_timestamp,
                ip
            FROM
                ".PMF_USER_SQLPREFIX."user
            WHERE
                user_id = ".$this->getUserId()
        );
        if (!$res or $this->_db->num_rows($res) != 1) {
            return array();
        }
        return $this->_db->fetch_assoc($res);
    }

    /**
    * updateSessionId()
    *
    * Updates the session-ID, does not care about time outs.
    * Stores session information in the user table: session_id,
    * session_timestamp and ip.
    * Optionally it should update the 'last login' time.
    * Returns true on success, otherwise false.
    *
    * @param    boolean
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
    * @return   bool
    */
    function updateSessionId($updateLastlogin = false)
    {
        // renew the session-ID
        if (function_exists('session_regenerate_id')) {
            $oldSessionId = session_id();
            if (session_regenerate_id(true)) {
                // Since PHP 5.1.0 the old associated session file could be delete passing the true boolean
                if (version_compare(phpversion(),'5.1.0', '<')) {
                    $sessionPath = realpath(session_save_path());
                    $sessionFilename = $sessionPath.'/sess_'.$oldSessionId;
                    if (@file_exists($sessionFilename)) {
                        @unlink($sessionFilename);
                    }
                }
            }
        } else {
            // TODO: implement.
        }
        // store session-ID age
        $now = time();
        $_SESSION[PMF_SESSION_ID_TIMESTAMP] = $now;
        // save session information in user table
        $query = sprintf(
                    "UPDATE
                        %suser
                    SET
                        session_id          = '%s',
                        session_timestamp   = %d,
                        %s
                        ip                  = '%s'
                    WHERE
                        user_id = %d",
                    PMF_USER_SQLPREFIX,
                    session_id(),
                    $now,
                    $updateLastlogin ?  "last_login = '".date('YmdHis', $now)."'," : '',
                    $_SERVER['REMOTE_ADDR'],
                    $this->getUserId()
                    );
        $res = $this->_db->query($query);
        if (!$res) {
            $this->errors[] = $this->_db->error();
            return false;
        }

        return true;
    }

    /**
    * saveToSession()
    *
    * Saves the CurrentUser into the session. This method
    * may be called after a successful login.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @return   void
    */
    function saveToSession()
    {
        // save CurrentUser in session
        $_SESSION[PMF_SESSION_CURRENT_USER] = $this->getUserId();
    }

    /**
    * deleteFromSession()
    *
    * Deletes the CurrentUser from the session. The user
    * will be logged out. Return true on success, otherwise false.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @return   bool
    */
    function deleteFromSession()
    {
        // delete CurrentUser object from session
        $_SESSION[PMF_SESSION_CURRENT_USER] = null;
        unset($_SESSION[PMF_SESSION_CURRENT_USER]);
        // log CurrentUser out
        $this->_logged_in = false;
        // delete session-ID
        $res = $this->_db->query("
            UPDATE
                ".PMF_USER_SQLPREFIX."user
            SET
                session_id = null
            WHERE
                user_id = ".$this->getUserId()
        );
        if (!$res) {
            $this->errors[] = $this->_db->error();
            return false;
        }
        return true;
    }

    /**
    * getFromSession()
    *
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
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    bool
    * @return   mixed
    */
    function getFromSession($ip_check = false)
    {
        // there is no valid user object in session
        if (!isset($_SESSION[PMF_SESSION_CURRENT_USER]) || !isset($_SESSION[PMF_SESSION_ID_TIMESTAMP]))
            return null;
        // create a new CurrentUser object
        $user = new PMF_CurrentUser();
        $user->getUserById($_SESSION[PMF_SESSION_CURRENT_USER]);
        // user object is timed out
        if ($user->sessionIsTimedOut()) {
            $user->deleteFromSession();
            return null;
        }
        // session-id not found in user table
        $session_info = $user->getSessionInfo();
        $session_id = (isset($session_info['session_id']) ? $session_info['session_id'] : '');
        if ($session_id == '' || $session_id != session_id()) {
            return false;
        }
        // check ip
        if ($ip_check and $session_info['ip'] != $_SERVER['REMOTE_ADDR']) {
            return false;
        }
        // session-id needs to be updated
        if ($user->sessionIdIsTimedOut()) {
            $user->updateSessionId();
        }
        // user is now logged in
        $user->_logged_in = true;
        // save current user to session and return the instance
        $user->saveToSession();
        return $user;
    }

    /**
    * setSessionTimeout()
    *
    * Sets the number of minutes when the current user stored in
    * the session gets invalid.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    float
    * @return   void
    */
    function setSessionTimeout($timeout)
    {
        $this->_session_timeout = abs($timeout);
    }

    /**
    * setSessionIdTimeout()
    *
    * Sets the number of minutes when the session-ID needs to be
    * updated. By setting the session-ID timeout to zero, the
    * session-ID will be updated on each click.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    float
    * @return   void
    */
    function setSessionIdTimeout($timeout)
    {
        $this->_session_id_timeout = abs($timeout);
    }

} // end of class PMF_CurrentUser
