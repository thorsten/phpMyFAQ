<?php

/**
 * Manages user authentication with Apache's SSO authentication, e.g. mod_sspi
 * or mod_auth_kerb.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-06-22
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\User;

/**
 * Class Sso
 *
 * @package phpMyFAQ\Auth
 */
class AuthSso extends Auth implements AuthDriverInterface
{
    /**
     * Always returns true because of SSO.
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
     * Always returns true because of SSO.
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
     * Checks if the username of the remote user is equal to the login name.
     *
     * @param string $login        Loginname
     * @param string $pass         Password
     * @param array  $optionalData Optional data
     *
     * @return bool
     */
    public function checkPassword($login, $pass, array $optionalData = null): bool
    {
        if (!isset($_SERVER['REMOTE_USER'])) {
            return false;
        } else {
            // Check if "DOMAIN\user", "user@DOMAIN" or only "user"
            $remoteUser = explode('\\', $_SERVER['REMOTE_USER']);
            if (is_array($remoteUser) && count($remoteUser) > 1) {
                $user = $remoteUser[1];
            } else {
                $remoteUser = explode('@', $_SERVER['REMOTE_USER']);
                if (is_array($remoteUser) && count($remoteUser) > 1) {
                    $user = $remoteUser[0];
                } else {
                    $user = $_SERVER['REMOTE_USER'];
                }
            }
            if ($user === $login) {
                $this->add($login, $pass);

                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Always returns true because of SSO.
     *
     * @param  string $login
     * @param  string $pass
     * @param  string $domain
     * @return bool
     * @throws
     */
    public function add($login, $pass, $domain = ''): bool
    {
        if ($this->config->get('ldap.ldapSupport')) {
            // LDAP/AD + SSO
            $authLdap = new AuthLdap($this->config);
            $result = $authLdap->add($login, null, $domain);

            return $result;
        } else {
            // SSO without LDAP/AD
            $user = new User($this->config);
            $result = $user->createUser($login, null, $domain);

            if ($result) {
                $user->setStatus('active');
            }

            // Set user information
            $user->setUserData(
                array(
                    'display_name' => $login,
                )
            );

            return $result;
        }
    }

    /**
     * Returns 1, if $_SERVER['REMOTE_USER'] is set.
     *
     * @param string $login        Loginname
     * @param array  $optionalData Optional data
     *
     * @return int
     */
    public function checkLogin($login, array $optionalData = null): int
    {
        return isset($_SERVER['REMOTE_USER']) ? 1 : 0;
    }
}
