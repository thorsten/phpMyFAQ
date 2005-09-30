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
require_once('PMF/User.php');

/* user defined includes */
// section -64--88-1-12--f895d8c:106777dbaf0:-7fdd-includes begin
// section -64--88-1-12--f895d8c:106777dbaf0:-7fdd-includes end

/* user defined constants */
// section -64--88-1-12--f895d8c:106777dbaf0:-7fdd-constants begin
@define('PMF_SESSION_CURRENT_USER', 'PMF_CURRENT_USER');
@define('PMF_SESSION_ID_TIMESTAMP', 'PMF_SESSION_TIMESTAMP');
@define('PMF_SESSION_ID_EXPIRES', 30);
@define('PMF_SESSION_ID_REFRESH', 10);
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
    function login($login = "", $pass = "")
    {
        $returnValue = null;

        // section -64--88-1-12--f895d8c:106777dbaf0:-7fd6 begin
        
	    $newCurrentUser = new PMF_CurrentUser();
		// get CurrentUser from Session
        if ($login == "" and $pass == "") {
        	// there is no user in Session
        	if (!isset($_SESSION[PMF_SESSION_CURRENT_USER])) {
        		return false;
        	}
        	// there is something in Session
        	else {
	            // user in session is a valid object
				if (get_class($_SESSION[PMF_SESSION_CURRENT_USER]) == get_class($newCurrentUser)) {
					return $_SESSION[PMF_SESSION_CURRENT_USER];
				}
				// not a valid object
				else {
					// create an empty object
					$_SESSION[PMF_SESSION_CURRENT_USER] = $newCurrentUser;
					return $_SESSION[PMF_SESSION_CURRENT_USER];
				}
        	}
		}
		// authenticate user by login and password
		else {
			if ($login == "lars" and $pass == "iquochi") {
				$currentUser = new PMF_CurrentUser();
				$currentUser->getUserByLogin($login);
				$_SESSION[PMF_SESSION_CURRENT_USER] = $currentUser;
				return $_SESSION[PMF_SESSION_CURRENT_USER];
			}
			else {
				return false;
			}
		}				  	
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
        // section -64--88-1-10-63632404:1069d6db002:-7fdb end

        return (string) $returnValue;
    }

} /* end of class PMF_CurrentUser */

?>/* lost code following: 
    // section -64--88-1-12--f895d8c:106777dbaf0:-7fd4 begin
    // section -64--88-1-12--f895d8c:106777dbaf0:-7fd4 end
*/