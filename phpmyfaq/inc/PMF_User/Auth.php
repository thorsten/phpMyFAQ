<?php

error_reporting(E_ALL);

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
 * @package PMF
 * @since 2005-09-30
 * @version 0.1
 */

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

/**
 * provides methods for password encryption. 
 *
 * Subclasses (extends) of this class provide the encrypt() method that returns
 * encrypted string. For special encryption methods, just create a new class as
 * extend of this class and has the method encrypt().
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-18
 * @version 0.1
 */
require_once('PMF/Enc.php');

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
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-includes begin
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-constants begin
@define('PMF_USERERROR_NO_AUTHTYPE', 'Specified authentication access class could not be found. ');
@define('PMF_USERERROR_LOGIN_NOT_UNIQUE', 'Specified login name already exists. ');
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-constants end

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
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-30
 * @version 0.1
 */
class PMF_Auth
{
    // --- ATTRIBUTES ---

    /**
     * private container that stores the encryption object.
     *
     * @access private
     * @var object
     */
    var $_enc_container = null;

    /**
     * public array that contains error messages.
     *
     * @access public
     * @var array
     */
    var $errors = array();

    /**
     * authentication access methods
     *
     * @access private
     * @var array
     */
    var $_auth_typemap = array('mysql' => 'AuthMysql', 'db' => 'AuthDb');

    /**
     * Short description of attribute read_only
     *
     * @access private
     * @var bool
     */
    var $_read_only = false;

    /**
     * Short description of attribute account_mirror
     *
     * @access private
     * @var bool
     */
    var $_account_mirror = true;

    // --- OPERATIONS ---

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_Auth()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd0 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd0 end
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
        // section -64--88-1-10-36edbf7a:106a832a030:-7fcd begin
        // section -64--88-1-10-36edbf7a:106a832a030:-7fcd end
    }

    /**
     * instantiates a new encryption object, stores it in a private container
     * returns it.
     *
     * This method instantiates a new Enc object by calling the static
     * method. The specified encryption method enctype is passed to
     * The result is stored in the private container variable _enc_container and
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return object
     */
    function selectEncType($enctype)
    {
        $returnValue = null;

        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fd8 begin
        $this->_enc_container = PMF_Enc::selectEnc($enctype);
        return $this->_enc_container;
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fd8 end

        return $returnValue;
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

        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fd4 begin
        if (!is_array($this->errors)) 
        	$this->errors = array((string) $this->errors);
        $message = '';
        foreach ($this->errors as $error) {
        	$message .= $error."\n";
        }
        $message .= $this->_enc_container->error();
        return $message;
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fd4 end

        return (string) $returnValue;
    }

    /**
     * returns an authentication object with the specified database access. 
     *
     * This method is called statically. The parameter database specifies the
     * of database access for the authentication object. Supported
     * are 'mysql' and 'pgsql'.
     *
     * If the given datase-type is not supported, selectAuth() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return object
     */
    function selectAuth($database)
    {
        $returnValue = null;

        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fbf begin
        // verify selected database
        $auth = new PMF_Auth();
        $database = strtolower($database);
        if (!isset($auth->_auth_typemap[$database])) {
        	$auth->errors[] = PMF_USERERROR_NO_AUTHTYPE;
            return $auth;
        }
        if (!file_exists("PMF/".$auth->_auth_typemap[$database].".php")) {
        	$auth->errors[] = PMF_USERERROR_NO_AUTHTYPE;
        	return $auth;
        }
        require_once("PMF/".$auth->_auth_typemap[$database].".php");
        // instantiate 
        $authclass = "PMF_".$auth->_auth_typemap[$database];
		$auth = new $authclass();
        return $auth;
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fbf end

        return $returnValue;
    }

    /**
     * Short description of method read_only
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param bool
     * @return bool
     */
    function read_only($read_only = null)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10-56ec5adf:106bcc9244c:-7fdb begin
        if ($read_only === null)
        	return $this->_read_only;
        $old_read_only = $this->_read_only;
        $this->_read_only = (bool) $read_only;
        return $old_read_only;
        // section -64--88-1-10-56ec5adf:106bcc9244c:-7fdb end

        return (bool) $returnValue;
    }

    /**
     * Short description of method account_mirror
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param bool
     * @return bool
     */
    function account_mirror($account_mirror = null)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10-eb43fc:106c4f6ca50:-7fda begin
        if ($account_mirror === null)
        	return $this->_account_mirror;
        $old_account_mirror = $this->_account_mirror;
        $this->_account_mirror = (bool) $account_mirror;
        return $old_account_mirror;
        // section -64--88-1-10-eb43fc:106c4f6ca50:-7fda end

        return (bool) $returnValue;
    }

    /**
     * Short description of method encrypt
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return string
     */
    function encrypt($str)
    {
        $returnValue = (string) '';

        // section -64--88-1-10-eb43fc:106c4f6ca50:-7fcd begin
        return $this->_enc_container->encrypt($str);
        // section -64--88-1-10-eb43fc:106c4f6ca50:-7fcd end

        return (string) $returnValue;
    }

} /* end of class PMF_Auth */

?>