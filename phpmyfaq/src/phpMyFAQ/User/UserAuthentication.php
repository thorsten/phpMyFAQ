<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Auth\AuthLdap;
use phpMyFAQ\Auth\AuthSso;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;

class UserAuthentication
{
    private bool $rememberMe = false;

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

    /**
     * Authenticates a user with a given username and password against
     * LDAP, SSO or local database.
     *
     * @throws Exception
     */
    public function authenticate(string $username, string $password): array
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
            if ($this->user->getUserData('twofactor_enabled') == 1 && (is_null($token)) === true) {
                $this->user->setTwoFactorAuthentication(true);
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

        return [ $this->user, $this->user->isLoggedIn() ];
    }
}
