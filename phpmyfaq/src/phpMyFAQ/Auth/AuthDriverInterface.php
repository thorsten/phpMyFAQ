<?php

/**
 * Interface for managing user authentication.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello <alberto@unex.es>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-01
 */

namespace phpMyFAQ\Auth;

/**
 * Interface Driver
 *
 * @package phpMyFAQ\Auth
 */
interface AuthDriverInterface
{
    /**
     * Adds a new user account to the authentication table. The domain
     * is only used in LDAP/AD environments. Returns true on success,
     * otherwise false.
     */
    public function create(string $login, string $password, string $domain = ''): mixed;

    /**
     * Changes the password for the account specified by login.
     * Returns true to success, otherwise false.
     * Error messages are added to the array errors.
     *
     * @param string $login Login name
     * @param string $password Password
     */
    public function update(string $login, string $password): bool;

    /**
     * Deletes the user account specified by login.
     * Returns true to success, otherwise false.
     * Error messages are added to the array errors.
     *
     * @param string $login Login name
     */
    public function delete(string $login): bool;

    /**
     * Checks the password for the given user account.
     * Returns true if the given password for the user account specified by
     * is correct, otherwise false.
     * Error messages are added to the array errors.
     * This function is only called when local authentication has failed, so
     * we are about to create a user account.
     *
     * @param string $login Login name
     * @param string $password Password
     * @param array<string>  $optionalData Optional data
     */
    public function checkCredentials(string $login, string $password, array $optionalData = []): bool;

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param string $login Login name
     * @param array<string>  $optionalData Optional data
     */
    public function isValidLogin(string $login, array $optionalData = []): int;
}
