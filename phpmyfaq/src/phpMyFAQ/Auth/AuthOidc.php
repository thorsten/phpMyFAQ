<?php

/**
 * Manages user authentication with a generic OpenID Connect provider.
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
 * @since     2026-10-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use Closure;
use InvalidArgumentException;
use phpMyFAQ\Auth;
use phpMyFAQ\Auth\Oidc\OidcProviderConfig;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Core\Json;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User;
use SensitiveParameter;

final class AuthOidc extends Auth implements AuthDriverInterface
{
    public const string DEFAULT_GROUPS_CLAIM = 'groups';

    /**
     * @param array<string, mixed> $claims
     * @param string $issuer The validated issuer of the ID token; account links are bound to it.
     * @throws InvalidArgumentException when the issuer is empty
     */
    public function __construct(
        Configuration $configuration,
        private readonly OidcProviderConfig $providerConfig,
        private readonly array $claims,
        private readonly string $issuer,
        private readonly string $resolvedLogin,
        /** @var (Closure(): User)|null */
        private readonly ?Closure $userFactory = null,
        /** @var (Closure(): MediumPermission)|null */
        private readonly ?Closure $mediumPermissionFactory = null,
    ) {
        if (trim($issuer) === '') {
            throw new InvalidArgumentException(
                'AuthOidc requires the validated OpenID Connect issuer; pass the discovery document issuer.',
            );
        }

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
                    'OpenID Connect user creation failed for "%s": %s',
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
                'oidc_sub' => $this->getSubject(),
                'oidc_iss' => $this->issuer,
            ]);
        } catch (\Exception $exception) {
            $this->configuration
                ->getLogger()
                ->error(sprintf(
                    'OpenID Connect user data persistence failed for "%s": %s',
                    $this->redactIdentifier($login),
                    $exception->getMessage(),
                ));
            return false;
        }

        if (!$saved) {
            $this->configuration
                ->getLogger()
                ->error(sprintf(
                    'OpenID Connect user data persistence returned false for "%s"',
                    $this->redactIdentifier($login),
                ));
            return false;
        }

        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_OIDC->value);

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
            if (!$this->isLinkedToSubject($existingUser)) {
                $this->configuration
                    ->getLogger()
                    ->warning(sprintf(
                        'OpenID Connect login rejected for "%s": the local account is not linked to the subject.',
                        $this->redactIdentifier($login),
                    ));
                return false;
            }

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

    /**
     * An existing local account may only be used by the identity whose issuer and subject it
     * stores: a subject is only unique per issuer. Accounts without a stored subject are never
     * claimed by username or email, and accounts without a stored issuer are refused.
     */
    private function isLinkedToSubject(User $user): bool
    {
        $subject = $this->getSubject();
        $linkedSubject = trim((string) $user->getUserData('oidc_sub'));
        if ($subject === '' || $linkedSubject === '' || !hash_equals($linkedSubject, $subject)) {
            return false;
        }

        $linkedIssuer = trim((string) $user->getUserData('oidc_iss'));
        if ($linkedIssuer === '') {
            $this->configuration
                ->getLogger()
                ->warning(sprintf(
                    'OpenID Connect login rejected for user #%d: the account link predates issuer binding '
                    . 'and must be re-established.',
                    $user->getUserId(),
                ));
            return false;
        }

        if (!hash_equals($linkedIssuer, $this->issuer)) {
            $this->configuration
                ->getLogger()
                ->warning(sprintf(
                    'OpenID Connect login rejected for user #%d: issuer mismatch, the account is linked to another issuer.',
                    $user->getUserId(),
                ));
            return false;
        }

        return true;
    }

    private function findUser(string $login): ?User
    {
        $user = $this->createUser();
        return $user->getUserByLogin($login, false) ? $user : null;
    }

    private function shouldAssignGroups(): bool
    {
        return (
            $this->toBool($this->configuration->get(item: 'oidc.groupAutoAssign'))
            && $this->configuration->get(item: 'security.permLevel') === 'medium'
        );
    }

    private function shouldSynchronizeGroupsOnLogin(): bool
    {
        return (
            $this->toBool($this->configuration->get(item: 'oidc.groupSyncOnLogin'))
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

        foreach ($this->extractGroupNames() as $groupName) {
            if (!array_key_exists($groupName, $groupMapping)) {
                continue;
            }
            $faqGroupName = $groupMapping[$groupName];
            $groupId = $mediumPermission->findOrCreateGroupByName($faqGroupName);
            if ($groupId <= 0) {
                continue;
            }

            $desiredGroupIds[] = $groupId;
            if (in_array($groupId, $currentGroupIds, strict: true)) {
                continue;
            }

            $mediumPermission->addToGroup($userId, $groupId);
            $this->configuration
                ->getLogger()
                ->info(sprintf('Added OpenID Connect user #%d to group %s', $userId, $faqGroupName));
        }

        if (!$this->shouldSynchronizeGroupsOnLogin()) {
            return;
        }

        foreach (array_values(array_unique($groupMapping)) as $faqGroupName) {
            $groupId = $mediumPermission->getGroupId($faqGroupName);
            if ($groupId <= 0) {
                continue;
            }

            if (
                !in_array($groupId, $currentGroupIds, strict: true)
                || in_array($groupId, $desiredGroupIds, strict: true)
            ) {
                continue;
            }

            $mediumPermission->removeFromGroup($userId, $groupId);
            $this->configuration
                ->getLogger()
                ->info(sprintf('Removed OpenID Connect user #%d from group %s', $userId, $faqGroupName));
        }
    }

    /**
     * Reads the configured groups claim. Anything but a list of non-empty strings is
     * treated as "no groups" so an unexpected claim shape never blocks a login.
     *
     * @return array<string>
     */
    private function extractGroupNames(): array
    {
        $claimName = trim((string) $this->configuration->get(item: 'oidc.groupsClaim'));
        if ($claimName === '') {
            $claimName = self::DEFAULT_GROUPS_CLAIM;
        }

        /* @mago-expect analysis:mixed-assignment - claims values are mixed; validated with is_array() below */
        $groups = $this->claims[$claimName] ?? [];
        if (!is_array($groups)) {
            return [];
        }

        $groupNames = [];
        /* @mago-expect analysis:mixed-assignment - group entries are mixed; validated with is_string() below */
        foreach ($groups as $group) {
            if (!is_string($group) || $group === '') {
                continue;
            }

            $groupNames[] = $group;
        }

        return array_values(array_unique($groupNames));
    }

    /**
     * @return array<string, string>
     */
    private function getGroupMapping(): array
    {
        $groupMapping = $this->configuration->get(item: 'oidc.groupMapping');
        if (!is_string($groupMapping) || trim($groupMapping) === '') {
            return [];
        }

        $decoded = Json::decodeAssoc($groupMapping);
        if ($decoded === null) {
            $this->configuration
                ->getLogger()
                ->warning('oidc.groupMapping is not a valid JSON object; no groups are managed.');
            return [];
        }

        $mapping = [];
        /* @mago-expect analysis:mixed-assignment - decoded JSON values are mixed; validated with is_string() below */
        foreach ($decoded as $providerGroup => $faqGroup) {
            if (!is_string($faqGroup)) {
                continue;
            }

            $mapping[(string) $providerGroup] = $faqGroup;
        }

        return $mapping;
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function redactIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', string: $identifier, limit: 2);
            return $local[0] . '***@' . $domain;
        }

        if (mb_strlen($identifier) <= 3) {
            return str_repeat('*', mb_strlen($identifier));
        }

        return mb_substr($identifier, start: 0, length: 3) . '…';
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
