<?php

error_reporting(E_ALL);

/**
 * This container class manages user authentication. 
 *
 * Subclasses of Auth implement the authentication functionality with different
 * types. The class AuthLdap for expamle provides authentication functionality
 * LDAP-database access, AuthMysql with MySQL-database access.
 *
 * Authentication functionality includes creation of a new login-and-password
 * deletion of an existing login-and-password combination and validation of
 * given by a user.
 *
 * Passwords are usually encrypted before stored in a database. For
 * and security, a password encryption method may be chosen. See documentation
 * Enc class for further details.
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
 * This class provides methods for password encryption.
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
// section 127-0-0-1-17ec9f7:105b52d5117:-7fef-constants end

/**
 * This container class manages user authentication. 
 *
 * Subclasses of Auth implement the authentication functionality with different
 * types. The class AuthLdap for expamle provides authentication functionality
 * LDAP-database access, AuthMysql with MySQL-database access.
 *
 * Authentication functionality includes creation of a new login-and-password
 * deletion of an existing login-and-password combination and validation of
 * given by a user.
 *
 * Passwords are usually encrypted before stored in a database. For
 * and security, a password encryption method may be chosen. See documentation
 * Enc class for further details.
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
     * Short description of attribute enc_container
     *
     * @access public
     * @var string
     */
    var $enc_container = '';

    /**
     * Short description of attribute enc_typemap
     *
     * @access public
     * @var array
     */
    var $enc_typemap = array('crypt' => 'EncCrypt', 'sha' => 'EncSha', 'md5' => 'EncMd5');

    // --- OPERATIONS ---

    /**
     * Short description of method PMF_Auth
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return void
     */
    function PMF_Auth($enctype)
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd0 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd0 end
    }

    /**
     * Short description of method __construct
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return void
     */
    function __construct($enctype)
    {
        // section -64--88-1-10-36edbf7a:106a832a030:-7fcf begin
        // section -64--88-1-10-36edbf7a:106a832a030:-7fcf end
    }

    /**
     * Short description of method __destruct
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
     * Short description of method add
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return bool
     */
    function add($login, $pass)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10-36edbf7a:106a832a030:-7fcb begin
        // section -64--88-1-10-36edbf7a:106a832a030:-7fcb end

        return (bool) $returnValue;
    }

    /**
     * Short description of method changePassword
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return bool
     */
    function changePassword($pass)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10-36edbf7a:106a832a030:-7fc7 begin
        // section -64--88-1-10-36edbf7a:106a832a030:-7fc7 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method delete
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function delete()
    {
        $returnValue = (bool) false;

        // section -64--88-1-10-36edbf7a:106a832a030:-7fc4 begin
        // section -64--88-1-10-36edbf7a:106a832a030:-7fc4 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method check
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return bool
     */
    function check($login, $pass)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10-36edbf7a:106a832a030:-7fc2 begin
        // section -64--88-1-10-36edbf7a:106a832a030:-7fc2 end

        return (bool) $returnValue;
    }

} /* end of class PMF_Auth */

?>