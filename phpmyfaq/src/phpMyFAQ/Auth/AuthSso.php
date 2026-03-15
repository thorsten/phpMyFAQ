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
 * @copyright 2011-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-06-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use Closure;
use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\User;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Sso
 *
 * @package phpMyFAQ\Auth
 */
class AuthSso extends Auth implements AuthDriverInterface
{
    private readonly Request $request;
    private readonly ?Closure $ldapFactory;
    private readonly ?Closure $userFactory;

    /**
     * @inheritDoc
     */
    public function __construct(
        Configuration $configuration,
        ?Request $request = null,
        ?Closure $ldapFactory = null,
        ?Closure $userFactory = null,
    ) {
        parent::__construct($configuration);

        $this->request = $request ?? Request::createFromGlobals();
        $this->ldapFactory = $ldapFactory;
        $this->userFactory = $userFactory;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, #[SensitiveParameter] string $password, string $domain = ''): bool
    {
        if ($this->configuration->isLdapActive()) {
            // LDAP/AD + SSO
            $authLdap = $this->createLdapAuth();
            return $authLdap->create($login, '', $domain);
        }

        // SSO without LDAP/AD
        $user = $this->createUser();
        $result = $user->createUser($login, '', $domain);
        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_SSO->value);
        // Set user information
        $user->setUserData(['display_name' => $login]);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update(string $login, #[SensitiveParameter] string $password): bool
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
     * @throws AuthException
     * @throws Exception
     */
    public function checkCredentials(
        string $login,
        #[SensitiveParameter]
        string $password,
        ?array $optionalData = null,
    ): bool {
        if ($this->request->server->get('REMOTE_USER') === null) {
            throw new AuthException('Remote User not set!');
        }

        // Check if "DOMAIN\user", "user@DOMAIN" or only "user"
        $remote = explode('\\', (string) $this->request->server->get('REMOTE_USER'));
        $user = $this->request->server->get('REMOTE_USER');
        if (count($remote) > 1) {
            $user = $remote[1];
        }

        if (count($remote) <= 1) {
            $remote = explode('@', (string) $this->request->server->get('REMOTE_USER'));
            $user = is_array($remote) && count($remote) > 1 ? $remote[0] : $this->request->server->get('REMOTE_USER');
        }

        return $user === $login;
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin(string $login, ?array $optionalData = null): int
    {
        return $this->request->server->get('PHP_AUTH_USER') !== null ? 1 : 0;
    }

    private function createLdapAuth(): AuthLdap
    {
        if ($this->ldapFactory instanceof Closure) {
            return ($this->ldapFactory)();
        }

        return new AuthLdap($this->configuration);
    }

    private function createUser(): User
    {
        if ($this->userFactory instanceof Closure) {
            return ($this->userFactory)();
        }

        return new User($this->configuration);
    }
}
