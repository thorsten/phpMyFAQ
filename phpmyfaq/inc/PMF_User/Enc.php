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

/* user defined includes */

/* user defined constants */
@define('PMF_USERERROR_NO_ENCTYPE', 'Specified encryption method could not be found. ');

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
        return $str;
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
        $enc = new PMF_Enc();
        $enctype = strtolower($enctype);
        if (!isset($enc->_enc_typemap[$enctype])) {
        	$enc->errors[] = PMF_USERERROR_NO_ENCTYPE;
        	return $enc;
        }
        $classfile = dirname(__FILE__)."/".$enc->_enc_typemap[$enctype].".php";
        if (!file_exists($classfile)) {
        	$enc->errors[] = PMF_USERERROR_NO_ENCTYPE;
        	return $enc;
        }
        require_once $classfile;
        $newclass = "PMF_".$enc->_enc_typemap[$enctype];
        if (!class_exists($newclass)) {
        	$enc->errors[] = PMF_USERERROR_NO_ENCTYPE;
        	return $enc;
        }
        $enc = new $newclass();
        return $enc;
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
        if (!is_array($this->errors)) 
        	$this->errors = array((string) $this->errors);
        $message = '';
        foreach ($this->errors as $error) {
        	$message .= $error."\n";
        }
        return $message;
    }

} /* end of class PMF_Enc */

?>
