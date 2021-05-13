<?php

/**
 * Interface for managing user authentication.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello <alberto@unex.es>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     *
     * @param string $login
     * @param string $password
     * @param string $domain
     * @return mixed
     */
    public function create(string $login, string $password, string $domain = '');

    /**
     * Changes the password for the account specified by login.
     * Returns true on success, otherwise false.
     * Error messages are added to the array errors.
     *
     * @param string $login Login name
     * @param string $password Password
     * @return bool
     */
    public function update(string $login, string $password): bool;

    /**
     * Deletes the user account specified by login.
     * Returns true on success, otherwise false.
     * Error messages are added to the array errors.
     *
     * @param string $login Login name
     * @return bool
     */
    public function delete(string $login): bool;

    /**
     * Checks the password for the given user account.
     * Returns true if the given password for the user account specified by
     * is correct, otherwise false.
     * Error messages are added to the array errors.
     * This function is only called when local authentication has failed, so
     * we are about to create user account.
     *
     * @param string $login Login name
     * @param string $password Password
     * @param array<string>  $optionalData Optional data
     * @return bool
     */
    public function checkCredentials(string $login, string $password, array $optionalData = []): bool;

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param string $login Login name
     * @param array<string>  $optionalData Optional data
     * @return int
     */
    public function isValidLogin(string $login, array $optionalData = []): int;
}
