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
use phpMyFAQ\Http\RateLimiter;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Request;

class UserAuthentication
{
    /**
     * Failed login attempts one client IP may make within the failure window
     * before further attempts are rejected, across all accounts.
     */
    public const int MAX_FAILED_LOGINS_PER_IP = 15;

    /**
     * Sliding window for the per-IP failed login budget, in seconds.
     */
    public const int FAILED_LOGIN_WINDOW = 300;

    private bool $rememberMe = false;

    private bool $twoFactorAuth = false;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
        private readonly ?RateLimiter $rateLimiter = null,
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
        if ($this->hasExhaustedFailedLoginBudget()) {
            // Reject before any password check runs: this client IP produced too
            // many failed logins recently, across all accounts.
            throw new UserException(User::ERROR_USER_TOO_MANY_FAILED_LOGINS);
        }

        if ($this->isRememberMe()) {
            $this->currentUser->enableRememberMe();
        }

        $this->authenticateLdap();
        $this->authenticateSso();

        try {
            if (!$this->currentUser->login($username, $password)) {
                $this->recordFailedLogin();
                throw new UserException(Translation::get(key: 'ad_auth_fail') ?? 'Authentication failed');
            }

            if ($this->currentUser->getUserData('twofactor_enabled')) {
                $this->setTwoFactorAuth(true);
                $this->currentUser->setLoggedIn(false);
                return $this->currentUser;
            }

            if ($this->currentUser->getStatus() !== 'blocked') {
                $this->currentUser->setLoggedIn(true);
                return $this->currentUser;
            }

            $this->currentUser->setLoggedIn(false);
            throw new UserException(
                (Translation::get(key: 'ad_auth_fail') ?? 'Authentication failed') . ' (' . $username . ')',
            );
        } catch (AuthException $authException) {
            $this->recordFailedLogin();
            throw new UserException($authException->getMessage());
        } catch (UserException $userException) {
            $this->recordFailedLogin();
            throw $userException;
        }

        return $this->currentUser;
    }

    /**
     * The per-IP failure budget stops password spraying from a single client
     * across many accounts, which the per-account lockout cannot see.
     */
    private function hasExhaustedFailedLoginBudget(): bool
    {
        $failedLoginKey = $this->failedLoginKey();
        if (!$this->rateLimiter instanceof RateLimiter || $failedLoginKey === null) {
            return false;
        }

        return !$this->rateLimiter->peek($failedLoginKey, self::MAX_FAILED_LOGINS_PER_IP, self::FAILED_LOGIN_WINDOW);
    }

    private function recordFailedLogin(): void
    {
        $failedLoginKey = $this->failedLoginKey();
        if ($failedLoginKey === null) {
            return;
        }

        $this->rateLimiter?->check($failedLoginKey, self::MAX_FAILED_LOGINS_PER_IP, self::FAILED_LOGIN_WINDOW);
    }

    /**
     * Null when no client IP is available (CLI scripts, test runs): a per-IP
     * budget without an IP would lump unrelated clients together.
     */
    private function failedLoginKey(): ?string
    {
        $clientIp = Request::createFromGlobals()->getClientIp();

        return $clientIp === null ? null : 'login-failures-' . $clientIp;
    }

    private function authenticateLdap(): void
    {
        $ldapEnabled = filter_var($this->configuration->get('ldap.ldapSupport'), FILTER_VALIDATE_BOOLEAN);
        if (!$ldapEnabled || !function_exists('ldap_connect')) {
            return;
        }

        if ($this->configuration->getLdapServer() === [] || $this->configuration->getLdapConfig() === []) {
            return;
        }

        try {
            $authLdap = new AuthLdap($this->configuration);
            $this->currentUser->addAuth($authLdap, 'ldap');
        } catch (\Throwable $exception) {
            // LDAP initialization failed (e.g. server unreachable) - log and continue with local auth
            $this->configuration
                ->getLogger()
                ->error('LDAP authentication initialization failed: ' . $exception->getMessage());
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
