<?php

/**
 * Manages the user authentication in phpMyFAQ
 *
 * Subclasses of Auth implement authentication functionality with different types. The class AuthLdap for example
 * provides database access. Authentication functionality includes creation of a new login-and-password, deletion
 * of an existing login-and-password combination, and validation of given by a user.
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
 * @copyright 2005-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-30
 */

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;

/**
 * Class Auth
 *
 * Manages user authentication in phpMyFAQ. Concrete adapters (e.g., AuthDatabase, AuthLdap, AuthHttp)
 * implement the actual operations like creating, deleting, and validating login/password pairs.
 * Passwords are encrypted via an Encryption implementation configured at runtime.
 */
class Auth
{
    private const string PMF_ERROR_USER_NO_AUTH_TYPE = 'Specified authentication access class could not be found.';

    /**
     * Public array that contains error messages.
     *
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * Container that stores the encryption object.
     */
    protected ?Encryption $encContainer = null;

    /**
     * Read-only flag.
     */
    private bool $readOnly = false;

    /**
     * Constructor.
     */
    public function __construct(protected ?Configuration $configuration)
    {
    }

    /**
     * Instantiates an Encryption implementation based on the given type.
     */
    public function getEncryptionContainer(string $encType): Encryption
    {
        $this->encContainer = Encryption::getInstance($encType, $this->configuration);
        return $this->encContainer;
    }

    /**
     * Returns a string with all collected error messages, each separated by a new line.
     */
    public function getErrors(): string
    {
        $message = '';

        foreach ($this->errors as $error) {
            $message .= $error . PHP_EOL;
        }

        // If no encryption container was set yet, don't trigger a fatal error
        return $message . ($this->encContainer ? $this->encContainer->error() : '');
    }

    /**
     * Adds an error message to the list of errors.
     */
    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * Clears all collected Auth errors (does not touch encryption errors).
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Returns an authentication object for the specified method.
     *
     * @throws Exception If the auth class cannot be found
     */
    public function selectAuth(string $method): Auth
    {
        $method = ucfirst(strtolower($method));
        $authClass = '\\phpMyFAQ\\Auth\\Auth' . $method;

        if (!class_exists($authClass)) {
            $this->errors[] = self::PMF_ERROR_USER_NO_AUTH_TYPE;
            throw new Exception(self::PMF_ERROR_USER_NO_AUTH_TYPE);
        }

        return new $authClass($this->configuration);
    }

    /**
     * Sets or unsets read-only mode and returns the previous state.
     */
    public function setReadOnly(bool $readOnly = false): bool
    {
        $oldReadOnly = $this->readOnly;
        $this->readOnly = $readOnly;

        return $oldReadOnly;
    }

    /**
     * Returns the current read-only state.
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Encrypts a string using the configured encryption container.
     *
     * @throws Exception If no encryption container was configured
     */
    public function encrypt(string $string): string
    {
        if ($this->encContainer === null) {
            throw new Exception('No encryption container configured. Call getEncryptionContainer() first.');
        }

        return $this->encContainer->encrypt($string);
    }
}
