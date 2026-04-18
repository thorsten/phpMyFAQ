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
use phpMyFAQ\Permission\MediumPermission;
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
        private readonly ?Closure $mediumPermissionFactory = null,
    ) {
        parent::__construct($configuration);
    }

    /**
     * @throws Exception
     */
    public function create(string $login, #[SensitiveParameter] string $password, string $domain = ''): bool
    {
        $user = $this->createUser();

        try {
            $result = $user->createUser($login, '', $domain);
        } catch (\Exception $exception) {
            $this->configuration
                ->getLogger()
                ->error(sprintf(
                    'Keycloak user creation failed for "%s": %s',
                    $this->redactIdentifier($login),
                    $exception->getMessage(),
                ));
            return false;
        }

        if (!$result) {
            return false;
        }

        try {
            $saved = $user->setUserData([
                'display_name' => $this->getDisplayName(),
                'email' => $this->getEmail(),
                'keycloak_sub' => $this->getSubject(),
            ]);
        } catch (\Exception $exception) {
            $this->configuration
                ->getLogger()
                ->error(sprintf(
                    'Keycloak user data persistence failed for "%s": %s',
                    $this->redactIdentifier($login),
                    $exception->getMessage(),
                ));
            return false;
        }

        if (!$saved) {
            $this->configuration
                ->getLogger()
                ->error(sprintf(
                    'Keycloak user data persistence returned false for "%s"',
                    $this->redactIdentifier($login),
                ));
            return false;
        }

        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_KEYCLOAK->value);

        if ($this->shouldAssignGroups()) {
            $this->assignUserToGroups($user->getUserId());
        }

        return true;
    }

    public function update(string $login, #[SensitiveParameter] string $password): bool
    {
        return true;
    }

    public function delete(string $login): bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function checkCredentials(
        string $login,
        #[SensitiveParameter]
        string $password,
        ?array $optionalData = null,
    ): bool {
        if ($login !== $this->resolvedLogin) {
            return false;
        }

        $existingUser = $this->findUser($login);
        if ($existingUser instanceof User) {
            if ($this->shouldSynchronizeGroupsOnLogin()) {
                $this->assignUserToGroups($existingUser->getUserId());
            }

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

    private function getSubject(): string
    {
        return trim((string) ($this->claims['sub'] ?? ''));
    }

    private function findUser(string $login): ?User
    {
        $user = $this->createUser();
        return $user->getUserByLogin($login, false) ? $user : null;
    }

    private function shouldAssignGroups(): bool
    {
        return (
            $this->toBool($this->configuration->get(item: 'keycloak.groupAutoAssign'))
            && $this->configuration->get(item: 'security.permLevel') === 'medium'
        );
    }

    private function assignUserToGroups(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $mediumPermission = $this->createMediumPermission();
        $groupMapping = $this->getGroupMapping();
        if ($groupMapping === []) {
            return;
        }

        $currentGroupIds = $mediumPermission->getUserGroups($userId);
        $desiredGroupIds = [];
        $roleNames = $this->extractRoleNames();

        foreach ($roleNames as $roleName) {
            if (!array_key_exists($roleName, $groupMapping)) {
                continue;
            }
            $faqGroupName = $groupMapping[$roleName];
            $groupId = $mediumPermission->findOrCreateGroupByName($faqGroupName);
            if ($groupId <= 0) {
                continue;
            }

            $desiredGroupIds[] = $groupId;
            if (in_array($groupId, $currentGroupIds, true)) {
                continue;
            }

            $mediumPermission->addToGroup($userId, $groupId);
            $this->configuration
                ->getLogger()
                ->info(sprintf('Added Keycloak user #%d to group %s', $userId, $faqGroupName));
        }

        if (!$this->shouldSynchronizeGroupsOnLogin()) {
            return;
        }

        foreach (array_values(array_unique($groupMapping)) as $groupName) {
            $groupId = $mediumPermission->getGroupId($groupName);
            if ($groupId <= 0) {
                continue;
            }

            if (!in_array($groupId, $currentGroupIds, true) || in_array($groupId, $desiredGroupIds, true)) {
                continue;
            }

            $mediumPermission->removeFromGroup($userId, $groupId);
            $this->configuration
                ->getLogger()
                ->info(sprintf('Removed Keycloak user #%d from group %s', $userId, $groupName));
        }
    }

    /**
     * @return array<string>
     */
    private function extractRoleNames(): array
    {
        $roleNames = [];

        $realmRoles = $this->claims['realm_access']['roles'] ?? [];
        if (is_array($realmRoles)) {
            foreach ($realmRoles as $realmRole) {
                if (!is_string($realmRole) || $realmRole === '') {
                    continue;
                }

                $roleNames[] = $realmRole;
            }
        }

        $clientId = trim((string) $this->configuration->get(item: 'keycloak.clientId'));
        if ($clientId !== '') {
            $clientRoles = $this->claims['resource_access'][$clientId]['roles'] ?? [];
            if (is_array($clientRoles)) {
                foreach ($clientRoles as $clientRole) {
                    if (!is_string($clientRole) || $clientRole === '') {
                        continue;
                    }

                    $roleNames[] = $clientRole;
                }
            }
        }

        return array_values(array_unique($roleNames));
    }

    /**
     * @return array<string, string>
     */
    private function getGroupMapping(): array
    {
        $groupMapping = $this->configuration->get(item: 'keycloak.groupMapping');
        if (!is_string($groupMapping) || trim($groupMapping) === '') {
            return [];
        }

        $decoded = json_decode($groupMapping, associative: true);

        return is_array($decoded) ? array_filter($decoded, is_string(...)) : [];
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function shouldSynchronizeGroupsOnLogin(): bool
    {
        return (
            $this->toBool($this->configuration->get(item: 'keycloak.groupSyncOnLogin'))
            && $this->configuration->get(item: 'security.permLevel') === 'medium'
        );
    }

    private function redactIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', $identifier, 2);
            return $local[0] . '***@' . $domain;
        }

        if (mb_strlen($identifier) <= 3) {
            return str_repeat('*', mb_strlen($identifier));
        }

        return mb_substr($identifier, 0, 3) . '…';
    }

    /**
     * @throws Exception
     */
    private function createUser(): User
    {
        if ($this->userFactory instanceof Closure) {
            return ($this->userFactory)();
        }

        return new User($this->configuration);
    }

    private function createMediumPermission(): MediumPermission
    {
        if ($this->mediumPermissionFactory instanceof Closure) {
            return ($this->mediumPermissionFactory)();
        }

        return new MediumPermission($this->configuration);
    }
}
