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
 *
 * @access public
 * @author Lars Tiedemann, <php@larstiedemann.de>
 * @package PMF
 */
class PMF_CurrentUser
    extends PMF_User
{
    // --- ATTRIBUTES ---

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
        // session not started
        if (session_id() == "")
            session_start();
        // section -64--88-1-12--f895d8c:106777dbaf0:-7fd8 end
    }

    /**
     * Short description of method login
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return object
     */
    function login($login = '', $pass = '')
    {
        $returnValue = null;

        // section -64--88-1-12--f895d8c:106777dbaf0:-7fd6 begin
		// get CurrentUser from Session
        if ($login == "" and $pass == "") {
        	// there is no user in Session
        	if (!isset($_SESSION[PMF_SESSION_CURRENT_USER])) {
        		return false;
        	}
			//$currentUser = new PMF_CurrentUser();
	        // user in session is a valid object
			if (get_class($_SESSION[PMF_SESSION_CURRENT_USER]) == __CLASS__) {
				return true;
			}
			// create a new current user in session
			$_SESSION[PMF_SESSION_CURRENT_USER] = $this;
			return true;
		}
		// authenticate user by login and password
		$login_error = 0;
		$pass_error  = 0;
		foreach ($this->_auth_container as $name => $auth) {
			// $auth is an invalid Auth object, so continue 
			if (!$this->checkAuth($auth)) {
				continue;
			}
			// $login does not exist
			if (!$auth->checkLogin($login)) {
				$login_error++;
				continue;
			}
			// $pass is incorrect
			echo "\$auth->checkPassword(".$login.", ".$pass.") = ".($auth->checkPassword($login, $pass) ? 'true' : 'false')."\n";
			if (!$auth->checkPassword($login, $pass)) {
				$pass_error++;
				continue;
			}
			$this->getUserByLogin($login);
			$_SESSION[PMF_SESSION_CURRENT_USER] = $this;
			return true;
			break;				
		}
		// raise errors
		if ($pass_error == count(array_values($this->_auth_container))) {
			$this->errors[] = PMF_USERERROR_INCORRECT_PASSWORD;
		}
		if ($login_error == count(array_values($this->_auth_container))) {
			$this->errors[] = PMF_USERERROR_INCORRECT_LOGIN;
		}
		return false;				  	
        // section -64--88-1-12--f895d8c:106777dbaf0:-7fd6 end

        return $returnValue;
    }

    /**
     * Short description of method generateSessionId
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return string
     */
    function generateSessionId()
    {
        $returnValue = (string) '';

        // section -64--88-1-10-63632404:1069d6db002:-7fdb begin
		srand((double)microtime()*1000000);
  		return (string) md5(uniqid(rand())); 
        // section -64--88-1-10-63632404:1069d6db002:-7fdb end

        return (string) $returnValue;
    }

} /* end of class PMF_CurrentUser */

?>
