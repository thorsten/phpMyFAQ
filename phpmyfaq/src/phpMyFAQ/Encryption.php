<?php

/**
 * Provides methods for password encryption.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */

namespace phpMyFAQ;

/**
 * Class Encryption
 *
 * @package phpMyFAQ
 */
class Encryption
{
    /**
     * Error constant.
     *
     * @var string
     */
    private const PMF_ERROR_USER_NO_ENCTYPE = 'EncryptionTypes method could not be found.';

    /**
     * Public array that contains error messages.
     *
     * @var string[]
     */
    public array $errors = [];

    /**
     * Salt.
     */
    protected string $salt = '';

    /**
     * Constructor.
     */
    public function __construct(protected ?Configuration $config)
    {
    }

    /**
     * This method is called statically. The parameter encType specifies the
     * of encryption method for the encryption object. Supported
     * are 'crypt', 'hash', 'bcrypt', and 'none'.
     * $enc = EncryptionTypes::selectEnc('hash');
     * $enc is an instance of the class EncryptionTypes\Hash.
     * If the given encryption-type is not supported, selectEnc() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     */
    public static function selectEnc(string $encType, Configuration $config): Encryption
    {
        $enc = new self($config);
        $encType = ucfirst(strtolower($encType));

        $encClass = 'phpMyFAQ\\EncryptionTypes\\' . $encType;
        if (!class_exists($encClass)) {
            $enc->errors[] = self::PMF_ERROR_USER_NO_ENCTYPE;

            return $enc;
        }

        return new $encClass($config);
    }

    /**
     * Encrypts the string str and returns the result.
     *
     * @param string $password String
     */
    public function encrypt(string $password): string
    {
        return $password;
    }

    /**
     * The string returned by error() contains messages for all errors that
     * during object processing. Messages are separated by new lines.
     */
    public function error(): string
    {
        if (!is_array($this->errors)) {
            $this->errors = [(string)$this->errors];
        }
        $message = '';
        foreach ($this->errors as $error) {
            $message .= $error . "\n";
        }

        return $message;
    }

    /**
     * Setter for salt.
     *
     *
     */
    public function setSalt(string $login): Encryption
    {
        $this->salt = $this->config->get('security.salt') . $login;

        return $this;
    }
}
