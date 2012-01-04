<?php
/**
 * Manages user authentication.
 *
 * Subclasses of Auth implement authentication functionality with different
 * types. The class AuthLdap for example provides authentication functionality
 * LDAP-database access, AuthDb with database access.
 * Authentication functionality includes creation of a new login-and-password
 * deletion of an existing login-and-password combination and validation of
 * given by a user. These functions are provided by the database-specific
 * see documentation of the database-specific authentication classes AuthMysql,
 * or AuthLdap for further details.
 * Passwords are usually encrypted before stored in a database. For
 * and security, a password encryption method may be chosen. See documentation
 * Enc class for further details.
 * Instead of calling the database-specific subclasses directly, the static
 * selectDb(dbtype) may be called which returns a valid database-specific
 * object. See documentation of the static method selectDb for further details.
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
 * @package   PMF_Auth
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth
 * 
 * @category  phpMyFAQ
 * @package   PMF_Auth
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-30
 */
class PMF_Auth
{
    /**
     * Error constants
     *
     * @var const
     */
    const PMF_ERROR_USER_NO_AUTHTYPE = 'Specified authentication access class could not be found.';

    /**
     * p container that stores the encryption object.
     *
     * @var PMF_Enc
     */
    protected $encContainer = null;

    /**
     * public array that contains error messages.
     *
     * @var array
     */
    public $errors = array();

    /**
     * Short description of attribute read_only
     *
     * @var boolean
     */
    private $read_only = false;

    /**
     * Constructor
     *
     * @param string  $enctype   Type of encoding
     * @param boolean $read_only Readonly?
     * 
     * @return PMF_Auth
     */
    public function __construct($enctype = 'none', $read_only = false)
    {
        $this->selectEncType($enctype);
        $this->setReadOnly($read_only);
    }
    
    /**
     * instantiates a new encryption object, stores it in a private container
     * returns it.
     *
     * This method instantiates a new Enc object by calling the static
     * method. The specified encryption method enctype is passed to
     * The result is stored in the private container variable encContainer and
     *
     * @param string $enctype encryption type
     * 
     * @return PMF_Enc
     */
    public function selectEncType($enctype)
    {
        $this->encContainer = PMF_Enc::selectEnc($enctype);
        
        return $this->encContainer;
    }

    /**
     * The string returned by error() contains messages for all errors that
     * during object processing. Messages are separated by new lines.
     *
     * Error messages are stored in the public array errors.
     *
     * @return string
     */
    public function error()
    {
        $message = '';
        
        if (!is_array($this->errors)) {
            $this->errors = array((string) $this->errors);
        }
        foreach ($this->errors as $error) {
            $message .= $error."\n";
        }
        
        $message .= $this->encContainer->error();
        
        return $message;
    }

    /**
     * Returns an authentication object with the specified database access.
     *
     * This method is called statically. The parameter database specifies the
     * of database access for the authentication object.
     *
     * If the given database-type is not supported, selectAuth() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     *
     * @param string $method Authentication access methods
     * 
     * @return PMF_Auth_Driver
     */
    public static function selectAuth($method)
    {
        // verify selected database
        $auth   = new PMF_Auth();
        $method = ucfirst(strtolower($method));
        
        if (!isset($method)) {
            $auth->errors[] = self::PMF_ERROR_USER_NO_AUTHTYPE;
            return $auth;
        }
        
        $classfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'PMF_Auth' . DIRECTORY_SEPARATOR . $method . '.php';

        if (!file_exists($classfile)) {
            $auth->errors[] = self::PMF_ERROR_USER_NO_AUTHTYPE;
            return $auth;
        }
        
        $authclass = 'PMF_Auth_' . $method;
        $auth      = new $authclass();
        return $auth;
    }

    /**
     * Short description of method read_only
     *
     * @param boolean $read_only boolean flag
     * 
     * @return boolean
     */
    public function setReadOnly($read_only = null)
    {
        if ($read_only === null) {
            return $this->read_only;
        }
        
        $oldread_only    = $this->read_only;
        $this->read_only = (bool) $read_only;
        
        return $oldread_only;
    }

    /**
     * Short description of method encrypt
     *
     * @param string $str string
     * 
     * @return string
     */
    public function encrypt($str)
    {
        return $this->encContainer->encrypt($str);
    }

    /**
     * Checks encryption type for given string. We need this for the MD5
     * migration of passwords from old phpMyFAQ installations to new ones
     * with a salt.
     *
     * @param string $encryptedPassword Encrypted password
     * @param string $clearPassword     Clear Password
     *
     * @return boolean
     */
    public function checkEncryptedPassword($encryptedPassword, $clearPassword)
    {
        $encTypes = array('crypt', 'md5', 'sha');

        foreach ($encTypes as $encType) {
            if ($encryptedPassword === PMF_Enc::selectEnc($encType)->encrypt($clearPassword)) {
                return true;
            }
        }

        return false;
    }
}