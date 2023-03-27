<?php

/**
 * Manages user authentication with Apache's SSO authentication, e.g. mod_sspi or mod_auth_kerb.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-06-22
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\User;

/**
 * Class Sso
 *
 * @package phpMyFAQ\Auth
 */
class AuthSso extends Auth implements AuthDriverInterface
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, string $password, string $domain = ''): bool
    {
        if ($this->config->isLdapActive()) {
            // LDAP/AD + SSO
            $authLdap = new AuthLdap($this->config);
            return $authLdap->create($login, '', $domain);
        } else {
            // SSO without LDAP/AD
            $user = new User($this->config);
            $result = $user->createUser($login, '', $domain);

            if ($result) {
                $user->setStatus('active');
            }

            // Set user information
            $user->setUserData([ 'display_name' => $login ]);

            return $result;
        }
    }

    /**
     * @inheritDoc
     */
    public function update(string $login, string $password): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $login): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function checkCredentials(string $login, string $password, array $optionalData = null): bool
    {
        if (!isset($_SERVER['REMOTE_USER'])) {
            return false;
        } else {
            // Check if "DOMAIN\user", "user@DOMAIN" or only "user"
            $remoteUser = explode('\\', (string) $_SERVER['REMOTE_USER']);
            if (is_array($remoteUser) && count($remoteUser) > 1) {
                $user = $remoteUser[1];
            } else {
                $remoteUser = explode('@', (string) $_SERVER['REMOTE_USER']);
                if (is_array($remoteUser) && count($remoteUser) > 1) {
                    $user = $remoteUser[0];
                } else {
                    $user = $_SERVER['REMOTE_USER'];
                }
            }
            if ($user === $login) {
                $this->create($login, $password);

                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin(string $login, array $optionalData = null): int
    {
        return isset($_SERVER['REMOTE_USER']) ? 1 : 0;
    }
}
