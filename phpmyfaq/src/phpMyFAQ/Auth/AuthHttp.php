<?php

/**
 * Manages user authentication with Apache's HTTP authentication.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello <alberto@unex.es>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-01
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\User;

/**
 * Class AuthHttp
 *
 * @package phpMyFAQ\Auth
 */
class AuthHttp extends Auth implements AuthDriverInterface
{
    /**
     * Adds a new user account to the authentication table.
     * Returns true on success, otherwise false.
     *
     * @param  string $login
     * @param  string $pass
     * @param  string $domain
     * @return bool
     */
    public function add($login, $pass, $domain = ''): bool
    {
        $user = new User($this->config);
        $result = $user->createUser($login, null);

        $user->setStatus('active');
        $user->setUserData(['display_name' => $login]);

        return $result;
    }

    /**
     * Changes the password for the account specified by login.
     * Returns true as it's not possible via HTTP Auth.
     *
     * @param string $login Loginname
     * @param string $pass  Password
     *
     * @return bool
     */
    public function changePassword($login, $pass): bool
    {
        return true;
    }

    /**
     * Deletes the user account specified by login.
     * Returns true as it's not possible via HTTP Auth.
     *
     * @param string $login Loginname
     *
     * @return bool
     */
    public function delete($login): bool
    {
        return true;
    }

    /**
     * Checks the password for the given user account.
     *
     * Returns true if the given password for the user account specified by
     * is correct, otherwise false.
     * Error messages are added to the array errors.
     *
     * This function is only called when local authentication has failed, so
     * we are about to create user account.
     *
     * @param string $login        Loginname
     * @param string $pass         Password
     * @param array  $optionalData Optional data
     *
     * @return bool
     */
    public function checkPassword($login, $pass, array $optionalData = null): bool
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_PW']) {
            return false;
        } else {
            if ($_SERVER['PHP_AUTH_USER'] == $login && $_SERVER['PHP_AUTH_PW'] == $pass) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Returns 1 or 0 for true or false.
     *
     * @param string $login        Loginname
     * @param array  $optionalData Optional data
     *
     * @return int
     */
    public function checkLogin($login, array $optionalData = null): int
    {
        return isset($_SERVER['PHP_AUTH_USER']) ? 1 : 0;
    }
}
