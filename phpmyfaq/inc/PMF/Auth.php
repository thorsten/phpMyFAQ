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
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-30
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Auth.
 *
 * @category  phpMyFAQ
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-30
 */
class PMF_Auth
{
    /**
     * Error constants.
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
    public $errors = [];

    /**
     * Short description of attribute read_only.
     *
     * @var bool
     */
    private $readOnly = false;

    /**
     * Configuration.
     *
     * @var PMF_Configuration
     */
    protected $_config = null;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Auth
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
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
        $this->encContainer = PMF_Enc::selectEnc($enctype, $this->_config);

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
    public function selectAuth($method)
    {
        // verify selected database
        $method = ucfirst(strtolower($method));

        if (!isset($method)) {
            $this->errors[] = self::PMF_ERROR_USER_NO_AUTHTYPE;

            return $this;
        }

        $authClass = 'PMF_Auth_'.$method;
        if (!class_exists($authClass)) {
            $this->errors[] = self::PMF_ERROR_USER_NO_AUTHTYPE;

            return $this;
        }

        return new $authClass($this->_config);
    }

    /**
     * Short description of method read_only.
     *
     * @param bool $readOnly boolean flag
     *
     * @return bool
     */
    public function setReadOnly($readOnly = null)
    {
        if ($readOnly === null) {
            return $this->readOnly;
        }

        $oldreadOnly = $this->readOnly;
        $this->readOnly = (bool) $readOnly;

        return $oldreadOnly;
    }

    /**
     * Short description of method encrypt.
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
     * @deprecated
     *
     * @return bool
     */
    public function checkEncryptedPassword($encryptedPassword, $clearPassword)
    {
        $encTypes = array('crypt', 'md5', 'sha');

        foreach ($encTypes as $encType) {
            if ($encryptedPassword === PMF_Enc::selectEnc($encType, $this->_config)->encrypt($clearPassword)) {
                return true;
            }
        }

        return false;
    }
}
