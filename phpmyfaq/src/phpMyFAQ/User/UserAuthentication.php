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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-11
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Auth\AuthException;
use phpMyFAQ\Auth\AuthLdap;
use phpMyFAQ\Auth\AuthSso;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;
use SensitiveParameter;

class UserAuthentication
{
    private bool $rememberMe = false;

    private bool $twoFactorAuth = false;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
    ) {
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
        return $this->twoFactorAuth;
    }

    public function setTwoFactorAuth(bool $twoFactorAuth): void
    {
        $this->twoFactorAuth = $twoFactorAuth;
    }

    /**
     * Authenticates a user with a given username and password against
     * LDAP, SSO, or local database.
     *
     * @throws UserException
     */
    public function authenticate(string $username, #[SensitiveParameter] string $password): CurrentUser
    {
        if ($this->isRememberMe()) {
            $this->currentUser->enableRememberMe();
        }

        $this->authenticateLdap();
        $this->authenticateSso();

        try {
            if ($this->currentUser->login($username, $password)) {
                if ($this->currentUser->getUserData('twofactor_enabled')) {
                    $this->setTwoFactorAuth(true);
                    $this->currentUser->setLoggedIn(false);
                } elseif ($this->currentUser->getStatus() !== 'blocked') {
                    $this->currentUser->setLoggedIn(true);
                } else {
                    $this->currentUser->setLoggedIn(false);
                    throw new UserException(
                        (Translation::get(key: 'ad_auth_fail') ?? 'Authentication failed') . ' (' . $username . ')',
                    );
                }
            } else {
                throw new UserException(Translation::get(key: 'ad_auth_fail') ?? 'Authentication failed');
            }
        } catch (AuthException $e) {
            throw new UserException($e->getMessage());
        }

        return $this->currentUser;
    }

    private function authenticateLdap(): void
    {
        if ($this->configuration->isLdapActive() && function_exists('ldap_connect')) {
            try {
                $authLdap = new AuthLdap($this->configuration);
                $this->currentUser->addAuth($authLdap, 'ldap');
            } catch (Exception $exception) {
                // LDAP initialization failed - log error and continue with local auth fallback
                $this->configuration
                    ->getLogger()
                    ->error('LDAP authentication initialization failed: ' . $exception->getMessage());
            }
        }
    }

    private function authenticateSso(): void
    {
        if ($this->configuration->get(item: 'security.ssoSupport')) {
            $authSso = new AuthSso($this->configuration);
            $this->currentUser->addAuth($authSso, 'sso');
        }
    }
}
