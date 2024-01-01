<?php

/**
 * Class for User Authentication handling.
 *
 * This class handles all operations around creating, saving and getting the secret
 * for a CurrentUser for two-factor-authentication. It also validates given tokens in
 * comparison to a given secret and returns a QR-code for transmitting a secret to
 * the authenticator-app.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-11
 */

namespace phpMyFAQ\User;

use phpMyFAQ\Auth\AuthLdap;
use phpMyFAQ\Auth\AuthSso;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;

class UserAuthentication
{
    private bool $rememberMe = false;

    private bool $twoFactorAuthentication = false;

    public function __construct(private readonly Configuration $configuration, private readonly CurrentUser $user)
    {
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function setRememberMe(bool $rememberMe): void
    {
        $this->rememberMe = $rememberMe;
    }

    public function hasTwoFactorAuthentication(): bool
    {
        return $this->twoFactorAuthentication;
    }

    public function setTwoFactorAuthentication(bool $twoFactorAuthentication): void
    {
        $this->twoFactorAuthentication = $twoFactorAuthentication;
    }
    /**
     * Authenticates a user with a given username and password against
     * LDAP, SSO or local database.
     *
     * @throws Exception
     */
    public function authenticate(string $username, string $password): CurrentUser
    {
        if ($this->isRememberMe()) {
            $this->user->enableRememberMe();
        }

        // LDAP
        if ($this->configuration->isLdapActive() && function_exists('ldap_connect')) {
            try {
                $authLdap = new AuthLdap($this->configuration);
                $this->user->addAuth($authLdap, 'ldap');
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }
        }

        // SSO
        if ($this->configuration->get('security.ssoSupport')) {
            $authSso = new AuthSso($this->configuration);
            $this->user->addAuth($authSso, 'sso');
        }

        // Local
        if ($this->user->login($username, $password)) {
            if ($this->user->getUserData('twofactor_enabled') == 1) {
                $this->setTwoFactorAuthentication(true);
                $this->user->setLoggedIn(false);
            } else {
                if ($this->user->getStatus() !== 'blocked') {
                    $this->user->setLoggedIn(true);
                } else {
                    $this->user->setLoggedIn(false);
                    throw new Exception(Translation::get('ad_auth_fail') . ' (' . $username . ')');
                }
            }
        } else {
            throw new Exception(Translation::get('ad_auth_fail'));
        }

        return $this->user;
    }
}
