<?php

/**
 * Manages the user authentication in phpMyFAQ
 *
 * Subclasses of Auth implement authentication functionality with different types. The class AuthLdap for example
 * provides with database access. Authentication functionality includes creation of a new login-and-password, deletion
 * of an existing login-and-password combination and validation of given by a user.
 * These functions are provided by the database-specific see documentation of the database-specific authentication
 * classes AuthDatabase, or AuthLdap for further details. Passwords are usually encrypted before stored in a database.
 * For security, a password encryption method may be chosen. See documentation of Encryption class for further details.
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
 * @since     2005-09-30
 */

namespace phpMyFAQ;

/**
 * Class Auth
 *
 * @package phpMyFAQ
 */
class Auth
{
    /**
     * @var string
     */
    private const PMF_ERROR_USER_NO_AUTH_TYPE = 'Specified authentication access class could not be found.';

    /**
     * public array that contains error messages.
     *
     * @var array<string>
     */
    public array $errors = [];

    /**
     * p container that stores the encryption object.
     */
    protected ?Encryption $encContainer = null;

    /**
     * Short description of attribute read_only.
     */
    private bool $readOnly = false;

    /**
     * Constructor.
     */
    public function __construct(protected ?Configuration $config)
    {
    }

    /**
     * This method instantiates a new EncryptionTypes object by calling the static
     * method.
     *
     * @param string $encType encryption type
     */
    public function selectEncType(string $encType): Encryption
    {
        $this->encContainer = Encryption::selectEnc($encType, $this->config);
        return $this->encContainer;
    }

    /**
     * The string returned by error() contains messages for all errors that
     * during object processing. Messages are separated by new lines.
     * Error messages are stored in the public array errors.
     */
    public function error(): string
    {
        $message = '';

        if (!is_array($this->errors)) {
            $this->errors = [(string)$this->errors];
        }
        foreach ($this->errors as $error) {
            $message .= $error . "\n";
        }

        return $message . $this->encContainer->error();
    }

    /**
     * Returns an authentication object with the specified database access.
     * This method is called statically. The parameter database specifies the
     * of database access for the authentication object.
     * If the given database-type is not supported, selectAuth() will return an
     * object without database access and with an error message. See the
     * of the error() method for further details.
     *
     * @param string $method Authentication access methods
     */
    public function selectAuth(string $method): Auth
    {
        // verify selected database
        $method = ucfirst(strtolower($method));

        $authClass = '\phpMyFAQ\\Auth\\Auth' . $method;

        if (!class_exists($authClass)) {
            $this->errors[] = self::PMF_ERROR_USER_NO_AUTH_TYPE;

            return $this;
        }

        return new $authClass($this->config);
    }

    /**
     * @param bool $readOnly boolean flag
     */
    public function setReadOnly(bool $readOnly = null): bool
    {
        if ($readOnly === null) {
            return $this->readOnly;
        }

        $oldReadOnly = $this->readOnly;
        $this->readOnly = $readOnly;

        return $oldReadOnly;
    }

    /**
     * @param string $string string
     */
    public function encrypt(string $string): string
    {
        return $this->encContainer->encrypt($string);
    }
}
