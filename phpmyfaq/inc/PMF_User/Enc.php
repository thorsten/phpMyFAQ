<?php

error_reporting(E_ALL);

/**
 * provides methods for password encryption. 
 *
 * Subclasses (extends) of this class provide the encrypt() method that returns
 * encrypted string. For special encryption methods, just create a new class as
 * extend of this class and has the method encrypt().
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

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
//require_once('PMF/Auth.php');

/* user defined includes */
// section 127-0-0-1-17ec9f7:1062544275a:-7ffd-includes begin
require_once dirname(__FILE__).'/Auth.php';
// section 127-0-0-1-17ec9f7:1062544275a:-7ffd-includes end

/* user defined constants */
// section 127-0-0-1-17ec9f7:1062544275a:-7ffd-constants begin
@define('PMF_USERERROR_NO_ENCTYPE', 'Specified encryption method could not be found. ');
// section 127-0-0-1-17ec9f7:1062544275a:-7ffd-constants end

/**
 * provides methods for password encryption. 
 *
 * Subclasses (extends) of this class provide the encrypt() method that returns
 * encrypted string. For special encryption methods, just create a new class as
 * extend of this class and has the method encrypt().
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_Enc
{
    // --- ATTRIBUTES ---

    /**
     * Encryption methods
     *
     * @access private
     * @var array
     */
    var $_enc_typemap = array('none' => 'Enc', 'crypt' => 'EncCrypt', 'sha' => 'EncSha', 'md5' => 'EncMd5');

    /**
     * Public array that contains error messages.
     *
     * @access public
     * @var array
     */
    var $errors = array();

    // --- OPERATIONS ---

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_Enc()
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc9 begin
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fc9 end
    }

    /**
     * constructor
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __construct()
    {
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fc6 begin
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fc6 end
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
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fc4 begin
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fc4 end
    }

    /**
     * encrypts the string str and returns the result.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return string
     */
    function encrypt($str)
    {
        $returnValue = (string) '';

        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fb7 begin
        return $str;
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fb7 end

        return (string) $returnValue;
    }

    /**
     * returns an encryption object with the specified encryption method. 
     *
     * This method is called statically. The parameter enctype specifies the
     * of encryption method for the encryption object. Supported
     * are 'crypt', 'md5', 'sha' and 'none'.
     *
     * $enc = Enc::selectEnc('md5');
     *
     * $enc is an instance of the class EncMd5. 
     *
     * If the given encryption-type is not supported, selectEnc() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return object
     */
    function selectEnc($enctype)
    {
        $returnValue = null;

        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fb4 begin
        $enc = new PMF_Enc();
        $enctype = strtolower($enctype);
        if (!isset($enc->_enc_typemap[$enctype])) {
        	$enc->errors[] = PMF_USERERROR_NO_ENCTYPE;
        	return $enc;
        }
        if (!file_exists("PMF/".$enc->_enc_typemap[$enctype].".php")) {
        	$enc->errors[] = PMF_USERERROR_NO_ENCTYPE;
        	return $enc;
        }
        require_once dirname(__FILE__)."/".$enc->_enc_typemap[$enctype].".php";
        $newclass = "PMF_".$enc->_enc_typemap[$enctype];
        if (!class_exists($newclass)) {
        	$enc->errors[] = PMF_USERERROR_NO_ENCTYPE;
        	return $enc;
        }
        $enc = new $newclass();
        return $enc;
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fb4 end

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

        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fb0 begin
        if (!is_array($this->errors)) 
        	$this->errors = array((string) $this->errors);
        $message = '';
        foreach ($this->errors as $error) {
        	$message .= $error."\n";
        }
        return $message;
        // section -64--88-1-10--3f7cf10c:106abb97c17:-7fb0 end

        return (string) $returnValue;
    }

} /* end of class PMF_Enc */

?>
