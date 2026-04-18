<?php

/**
 * Manages user authentication with Keycloak via OIDC.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-04-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use Closure;
use phpMyFAQ\Auth;
use phpMyFAQ\Auth\Oidc\OidcProviderConfig;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\User;
use SensitiveParameter;

class AuthKeycloak extends Auth implements AuthDriverInterface
{
    /** @param array<string, mixed> $claims */
    public function __construct(
        Configuration $configuration,
        private readonly OidcProviderConfig $providerConfig,
        private readonly array $claims,
        private readonly string $resolvedLogin,
        private readonly ?Closure $userFactory = null,
    ) {
        parent::__construct($configuration);
    }

    /**
     * @throws Exception
     */
    public function create(string $login, #[SensitiveParameter] string $password, string $domain = ''): bool
    {
        $result = false;
        $user = $this->createUser();

        try {
            $result = $user->createUser($login, '', $domain);
        } catch (\Exception $exception) {
            $this->configuration->getLogger()->info($exception->getMessage());
        }

        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_KEYCLOAK->value);
        $user->setUserData([
            'display_name' => $this->getDisplayName(),
            'email' => $this->getEmail(),
        ]);

        return $result;
    }

    public function update(string $login, #[SensitiveParameter] string $password): bool
    {
        return true;
    }

    public function delete(string $login): bool
    {
        return true;
    }

    public function checkCredentials(
        string $login,
        #[SensitiveParameter]
        string $password,
        ?array $optionalData = null,
    ): bool {
        if ($login !== $this->resolvedLogin) {
            return false;
        }

        if ($this->userExists($login)) {
            return true;
        }

        if (!$this->providerConfig->autoProvision) {
            return false;
        }

        return $this->create($login, '');
    }

    public function isValidLogin(string $login, ?array $optionalData = null): int
    {
        return $login === $this->resolvedLogin ? 1 : 0;
    }

    private function getDisplayName(): string
    {
        $name = trim((string) ($this->claims['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $preferredUsername = trim((string) ($this->claims['preferred_username'] ?? ''));
        if ($preferredUsername !== '') {
            return $preferredUsername;
        }

        return $this->resolvedLogin;
    }

    private function getEmail(): string
    {
        return trim((string) ($this->claims['email'] ?? ''));
    }

    private function userExists(string $login): bool
    {
        $user = $this->createUser();
        return $user->getUserByLogin($login, false);
    }

    private function createUser(): User
    {
        if ($this->userFactory instanceof Closure) {
            return ($this->userFactory)();
        }

        return new User($this->configuration);
    }
}
