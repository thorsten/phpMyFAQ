<?php

error_reporting(E_ALL);

/**
 * manages authentication process using php sessions.
 *
 * The CurrentUser class is an extension of the User class. It provides methods
 * manage user authentication using multiple database accesses.
 *
 * @author Lars Tiedemann, <php@larstiedemann.de>
 * @package PMF
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
// section -64--88-1-12--f895d8c:106777dbaf0:-7fdd-includes begin
require_once dirname(__FILE__).'/User.php';
// section -64--88-1-12--f895d8c:106777dbaf0:-7fdd-includes end

/* user defined constants */
// section -64--88-1-12--f895d8c:106777dbaf0:-7fdd-constants begin
@define('PMF_SESSION_CURRENT_USER', 'PMF_CURRENT_USER');
@define('PMF_SESSION_ID_TIMESTAMP', 'PMF_SESSION_TIMESTAMP');
@define('PMF_SESSION_ID_EXPIRES', 30);
@define('PMF_SESSION_ID_REFRESH', 10);
@define('PMF_LOGIN_BY_SESSION', true);
@define('PMF_LOGIN_BY_SESSION_FAILED', 'Could not login user from session. ');
@define('PMF_LOGIN_BY_AUTH_FAILED', 'Could not login with login and password. ');
@define('PMF_USERERROR_INCORRECT_PASSWORD', 'Specified password is not correct. ');
// section -64--88-1-12--f895d8c:106777dbaf0:-7fdd-constants end

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
class PMF_CurrentUser
    extends PMF_User
{
    // --- ATTRIBUTES ---

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
    var $_session_timeout = 60;

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

    // --- OPERATIONS ---

    /**
     * Short description of method PMF_CurrentUser
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_CurrentUser()
    {
        // section -64--88-1-12--f895d8c:106777dbaf0:-7fd8 begin
        $this->PMF_User();
        // section -64--88-1-12--f895d8c:106777dbaf0:-7fd8 end
    }

    /**
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
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return bool
     */
    function login($login, $pass)
    {
        $returnValue = null;

        // section -64--88-1-12--f895d8c:106777dbaf0:-7fd6 begin
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
				print_r($auth);
				break;
			}
			// but hey, this must be a valid match!
			// make sure that the session is started
			session_start();
			// load user object
			$this->getUserByLogin($login);
			// user is now logged in
			$this->_logged_in = true;
			// update session-id and save to session
			$this->updateSessionId();
			$this->saveToSession();
			// remember the auth container for administration
			$res = $this->_db->query("
                UPDATE
                    ".PMF_USER_SQLPREFIX."user
                SET
                    auth_source = '".$name."'
                WHERE
                    user_id = '".$this->getUserId()."'
			");
			if (!$res) {
                return false;
                break;
            }
			// return true
			return true;
			break;				
		}
		// raise errors and return false
		if ($login_error == $count)
			$this->errors[] = PMF_USERERROR_INCORRECT_LOGIN;
		if ($pass_error > 0)
			$this->errors[] = PMF_USERERROR_INCORRECT_PASSWORD;
		return false;
        // section -64--88-1-12--f895d8c:106777dbaf0:-7fd6 end

        return $returnValue;
    }

    /**
     * Returns true if CurrentUser is logged in, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function isLoggedIn()
    {
        return $this->_logged_in;
    }

    /**
     * Returns false if the CurrentUser object stored in the
     * session is valid and not timed out. There are two
     * parameters for session timeouts: $this->_session_timeout
     * and $this->_session_id_timeout.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function sessionIsTimedOut()
    {
        if ($this->_session_timeout <= $this->sessionAge())
            return true;
        return false;
    }

    /**
     * Returns false if the session-ID is not timed out.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function sessionIdIsTimedOut()
    {
        if ($this->_session_id_timeout <= $this->sessionAge())
            return true;
        return false;
    }

    /**
     * Returns the age of the current session-ID in minutes.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return float
     */
    function sessionAge()
    {
        if (!isset($_SESSION[PMF_SESSION_ID_TIMESTAMP]))
            return (float) 0;
        return (time() - $_SESSION[PMF_SESSION_ID_TIMESTAMP]) / 60;
    }

    /**
     * Returns the session-ID that is stored in the user table.
     * This session-ID may be used to identify users. But be
     * careful with this, as the stored session-Id and the
     * session-ID passed by url (or stored in a cookie) may not be
     * equal.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return string
     */
    function getSessionId()
    {
        $res = $this->_db->query("
            SELECT
                session_id
            FROM
                ".PMF_USER_SQLPREFIX."user
            WHERE
                user_id = '".$this->getUserId()."'
        ");
        if (!$res or $this->_db->num_rows($res) != 1)
            return '';
        $row = $this->_db->fetch_assoc($res);
        return $row['session_id'];
    }

    /**
     * Updates the session-ID, does not care about time outs.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function updateSessionId()
    {
        // renew the session-ID
        session_regenerate_id();
        // store session-ID age
        $_SESSION[PMF_SESSION_ID_TIMESTAMP] = time();
        // save session-ID in user table
        $res = $this->_db->query("
            UPDATE
                ".PMF_USER_SQLPREFIX."user
            SET
                session_id = '".session_id()."'
            WHERE
                user_id = '".$this->getUserId()."'
        ");
        if (!$res) {
            $this->errors[] = $this->_db->error();
            return false;
        }
        return true;
    }

    /**
     * Saves the CurrentUser into the session. This method
     * may be called after a successful login.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function saveToSession()
    {
        // save CurrentUser in session
        $_SESSION[PMF_SESSION_CURRENT_USER] = $this->getUserId();
    }

    /**
     * Deletes the CurrentUser from the session. The user
     * will be logged out. Return true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function deleteFromSession()
    {
        // delete CurrentUser object from session
        unset ($_SESSION[PMF_SESSION_CURRENT_USER]);
        // log CurrentUser out
        $this->_logged_in = false;
        // delete session-ID
        $res = $this->_db->query("
            UPDATE
                ".PMF_USER_SQLPREFIX."user
            SET
                session_id = ''
            WHERE
                user_id = '".$this->getUserId()."'
        ");
        if (!$res) {
            $this->errors[] = $this->_db->error();
            return false;
        }
        return true;
    }

    /**
     * This static method returns a valid CurrentUser object if
     * there is one in the session that is not timed out. The
     * session-ID is updated if neccessary. The CurrentUser
     * will be removed from the session, if it is timed out. If
     * there is no valid CurrentUser in the session or the
     * CurrentUser has timed out, null will be returned.
     * If there is no user in the user table with the same
     * session-ID, null will be returned.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return object
     */
    function getFromSession()
    {
		// make sure that the session is started
		session_start();
        // there is no valid user object in session
        if (!isset($_SESSION[PMF_SESSION_CURRENT_USER]))
			return null;
        // create a new CurrentUser object
        $user = new PMF_CurrentUser();
        $user->getUserById($_SESSION[PMF_SESSION_CURRENT_USER]);
        // session-id not found in user table
        $session_id = $user->getSessionId();
        if ($session_id == '' or $session_id != session_id())
            return null;
        // user object is timed out
        if ($user->sessionIsTimedOut())
            return null;
        // session-id needs to be updated
        if ($user->sessionIdIsTimedOut())
            $user->updateSessionId();
        // user is now logged in
        $user->_logged_in = true;
		// save current user to session and return the instance
		$user->saveToSession();
		return $user;
    }

    /**
     * Sets the number of minutes when the current user stored in
     * the session gets invalid.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param float
     * @return void
     */
    function setSessionTimeout($timeout)
    {
        $this->_session_timeout = abs($timeout);
    }

    /**
     * Sets the number of minutes when the session-ID needs to be
     * updated. By setting the session-ID timeout to zero, the
     * session-ID will be updated on each click.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param float
     * @return void
     */
    function setSessionIdTimeout($timeout)
    {
        $this->_session_id_timeout = abs($timeout);
    }

} /* end of class PMF_CurrentUser */

?>
