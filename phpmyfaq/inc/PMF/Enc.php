<?php

/**
 * Provides methods for password encryption.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Enc.
 *
 * @category  phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */
class PMF_Enc
{
    /**
     * Error constant.
     *
     * @const
     */
    const PMF_ERROR_USER_NO_ENCTYPE = 'Encryption method could not be found.';

    /**
     * Public array that contains error messages.
     *
     * @var array
     */
    public $errors = [];

    /**
     * Configuration.
     *
     * @var PMF_Configuration
     */
    protected $_config = null;

    /**
     * Salt.
     *
     * @var string
     */
    protected $salt = '';

    /**
     * Encrypts the string str and returns the result.
     *
     * @param string $str String
     *
     * @return string
     */
    public function encrypt($str)
    {
        return $str;
    }

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Enc
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * This method is called statically. The parameter enctype specifies the
     * of encryption method for the encryption object. Supported
     * are 'crypt', 'md5', 'sha1' and 'none'.
     *
     * $enc = Enc::selectEnc('md5');
     *
     * $enc is an instance of the class PMF_Enc_Md5.
     *
     * If the given encryption-type is not supported, selectEnc() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     *
     * @param string            $encType
     * @param PMF_Configuration $config
     *
     * @return PMF_Enc
     */
    public static function selectEnc($encType, PMF_Configuration $config)
    {
        $enc = new self($config);
        $encType = ucfirst(strtolower($encType));

        if (!isset($encType)) {
            $enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;

            return $enc;
        }

        $encClass = 'PMF_Enc_'.$encType;

        if (!class_exists($encClass)) {
            $enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;

            return $enc;
        }

        return new $encClass($config);
    }

    /**
     * The string returned by error() contains messages for all errors that
     * during object processing. Messages are separated by new lines.
     *
     * @return string
     */
    public function error()
    {
        if (!is_array($this->errors)) {
            $this->errors = array((string) $this->errors);
        }
        $message = '';
        foreach ($this->errors as $error) {
            $message .= $error."\n";
        }

        return $message;
    }

    /**
     * Setter for salt.
     *
     * @param string $login
     *
     * @return PMF_Enc
     */
    public function setSalt($login)
    {
        $this->salt = $this->_config->get('security.salt').$login;

        return $this;
    }
}
