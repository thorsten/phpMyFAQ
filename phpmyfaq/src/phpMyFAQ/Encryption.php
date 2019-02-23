<?php

namespace phpMyFAQ;

/**
 * Provides methods for password encryption.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * EncryptionTypes.
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-18
 */
class Encryption
{
    /**
     * Error constant.
     *
     * @const
     */
    const PMF_ERROR_USER_NO_ENCTYPE = 'EncryptionTypes method could not be found.';

    /**
     * Public array that contains error messages.
     *
     * @var array
     */
    public $errors = [];

    /**
     * Configuration.
     *
     * @var Configuration
     */
    protected $_config = null;

    /**
     * Salt.
     *
     * @var string
     */
    protected $salt = '';

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Encrypts the string str and returns the result.
     *
     * @param string $str String
     *
     * @return string
     */
    public function encrypt($str): string
    {
        return $str;
    }

    /**
     * This method is called statically. The parameter encType specifies the
     * of encryption method for the encryption object. Supported
     * are 'crypt', 'md5', 'sha' 'bcrypt', and 'none'.
     *
     * $enc = EncryptionTypes::selectEnc('md5');
     *
     * $enc is an instance of the class Enc_Md5.
     *
     * If the given encryption-type is not supported, selectEnc() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     *
     * @param string            $encType
     * @param Configuration $config
     *
     * @return Encryption
     */
    public static function selectEnc($encType, Configuration $config)
    {
        $enc = new self($config);
        $encType = ucfirst(strtolower($encType));

        if (!isset($encType)) {
            $enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;

            return $enc;
        }

        $encClass = 'phpMyFAQ\\EncryptionTypes\\'.$encType;

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
    public function error(): string
    {
        if (!is_array($this->errors)) {
            $this->errors = array((string)$this->errors);
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
     * @return Encryption
     */
    public function setSalt($login)
    {
        $this->salt = $this->_config->get('security.salt').$login;

        return $this;
    }
}
