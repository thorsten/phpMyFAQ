<?php

/**
 * Manages user authentication with LDAP server.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use Closure;
use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Ldap as LdapCore;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User;
use SensitiveParameter;

/**
 * Class AuthLdap
 *
 * @package phpMyFAQ\Auth
 */
class AuthLdap extends Auth implements AuthDriverInterface
{
    private LdapCore $ldapCore;
    private readonly ?Closure $userFactory;
    private readonly ?Closure $mediumPermissionFactory;

    /** @var array<int, array<string, mixed>> Array of LDAP servers */
    private readonly array $ldapServer;

    /** @var int Active LDAP server */
    private int $activeServer = 0;

    private readonly bool $multipleServers;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function __construct(
        Configuration $configuration,
        ?LdapCore $ldapCore = null,
        ?Closure $userFactory = null,
        ?Closure $mediumPermissionFactory = null,
    ) {
        $this->configuration = $configuration;
        $this->ldapServer = $this->configuration->getLdapServer();
        $this->multipleServers = true === $this->configuration->get(item: 'ldap.ldap_use_multiple_servers');
        $this->ldapCore = $ldapCore ?? new LdapCore($configuration);
        $this->userFactory = $userFactory;
        $this->mediumPermissionFactory = $mediumPermissionFactory;

        parent::__construct($this->configuration);

        if ([] === $this->ldapServer) {
            throw new AuthException('An error occurred while contacting LDAP: No configuration found.');
        }

        $this->connect($this->activeServer);
    }

    /**
     * @inheritDoc
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

        $this->connect($this->activeServer);

        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_LDAP->value);

        // Set user information from LDAP
        $completeName = $this->ldapCore->getCompleteName($login);
        $mail = $this->ldapCore->getMail($login);
        $user->setUserData([
            'display_name' => is_string($completeName) ? $completeName : '',
            'email' => is_string($mail) ? $mail : '',
        ]);

        // Handle group assignments if enabled
        $ldapGroupConfig = $this->configuration->getLdapGroupConfig();
        if (
            true === ($ldapGroupConfig['auto_assign'] ?? false)
            && $this->configuration->get(item: 'security.permLevel') === 'medium'
        ) {
            $this->assignUserToGroups($login, $user->getUserId());
        }

        return $result;
    }

    /**
     * Assigns user to phpMyFAQ groups based on AD group membership
     *
     * @param string $login Username
     * @param int $userId User ID
     */
    private function assignUserToGroups(string $login, int $userId): void
    {
        $ldapGroupConfig = $this->configuration->getLdapGroupConfig();
        $userGroups = $this->ldapCore->getGroupMemberships($login);

        if ($userGroups === false) {
            $this->configuration->getLogger()->warning('Unable to retrieve group memberships for user: ' . $login);
            return;
        }

        $mediumPermission = $this->createMediumPermission();
        $groupMappingRaw = $ldapGroupConfig['group_mapping'] ?? [];
        $groupMapping = is_array($groupMappingRaw) ? $groupMappingRaw : [];

        foreach ($userGroups as $userGroup) {
            $groupName = $this->extractGroupNameFromDn($userGroup);

            // Check if there's a specific mapping for this AD group
            $faqGroupName = $groupName;
            if (array_key_exists($groupName, $groupMapping)) {
                $faqGroupName = (string) $groupMapping[$groupName];
            }

            // Find or create the group
            $groupId = $mediumPermission->findOrCreateGroupByName($faqGroupName);

            if ($groupId > 0) {
                $mediumPermission->addToGroup($userId, $groupId);
                $this->configuration->getLogger()->info(sprintf('Added user %s to group %s', $login, $faqGroupName));
            }
        }
    }

    /**
     * Extract group name from DN
     *
     * @param string $dn Group DN
     * @return string Group name
     */
    private function extractGroupNameFromDn(string $dn): string
    {
        // Extract CN from DN, e.g., "CN=Domain Users,CN=Users,DC=example,DC=com" -> "Domain Users"
        if (preg_match('/CN=([^,]+)/i', $dn, $matches)) {
            return $matches[1];
        }

        return $dn;
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
     * @throws AuthException|Exception
     */
    public function checkCredentials(
        string $login,
        #[SensitiveParameter]
        string $password,
        ?array $optionalData = null,
    ): bool {
        if ('' === trim($password)) {
            throw new AuthException(User::ERROR_USER_INCORRECT_PASSWORD);
        }

        // Get active LDAP server for current user
        if ($this->multipleServers) {
            $key = array_key_first($this->ldapServer);
            if ($key !== null) {
                $this->activeServer = (int) $key;
                $this->connect($this->activeServer);
            }
        }

        $bindLogin = $login;
        $usesDomainPrefix = (bool) $this->configuration->get(item: 'ldap.ldap_use_domain_prefix');
        if ($usesDomainPrefix) {
            if (is_array($optionalData) && array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'] . '\\' . $login;
            }
        }

        if (!$usesDomainPrefix) {
            $this->connect($this->activeServer);
            $userDn = $this->ldapCore->getDn($login);
            $bindLogin = is_string($userDn) && $userDn !== '' ? $userDn : $login;
        }

        // Check user in LDAP
        $server = $this->ldapServer[$this->activeServer] ?? [];
        $this->ldapCore->connect(
            (string) ($server['ldap_server'] ?? ''),
            (int) ($server['ldap_port'] ?? 389),
            (string) ($server['ldap_base'] ?? ''),
            $bindLogin,
            htmlspecialchars_decode($password),
        );

        if (!$this->ldapCore->bind($bindLogin, htmlspecialchars_decode($password))) {
            throw new AuthException($this->ldapCore->error ?? 'LDAP bind failed.');
        }

        // Check AD group membership restrictions if enabled
        $ldapGroupConfig = $this->configuration->getLdapGroupConfig();
        if ($ldapGroupConfig['use_group_restriction'] === 'true') {
            $userGroups = $this->ldapCore->getGroupMemberships($login);
            if ($userGroups === false) {
                throw new AuthException('Unable to retrieve user group memberships');
            }

            $allowedGroupsRaw = $ldapGroupConfig['allowed_groups'] ?? [];
            $allowedGroups = is_array($allowedGroupsRaw) ? $allowedGroupsRaw : [];
            if ($allowedGroups !== []) {
                $hasAllowedGroup = false;
                foreach ($userGroups as $userGroup) {
                    foreach ($allowedGroups as $allowedGroup) {
                        if (!str_contains($userGroup, trim((string) $allowedGroup))) {
                            continue;
                        }

                        $hasAllowedGroup = true;
                        break 2;
                    }
                }

                if (!$hasAllowedGroup) {
                    throw new AuthException('User is not a member of any allowed LDAP/Active Directory groups');
                }
            }
        }

        $this->create($login, htmlspecialchars_decode($password));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin(string $login, ?array $optionalData = null): int
    {
        // Get active LDAP server for current user
        if ($this->multipleServers) {
            $key = array_key_first($this->ldapServer);
            if ($key !== null) {
                $this->activeServer = (int) $key;
                $this->connect($this->activeServer);
            }
        }

        $this->connect($this->activeServer);

        return strlen((string) $this->ldapCore->getCompleteName($login));
    }

    private function connect(int $activeServer = 0): void
    {
        $server = $this->ldapServer[$activeServer] ?? [];
        $this->ldapCore->connect(
            (string) ($server['ldap_server'] ?? ''),
            (int) ($server['ldap_port'] ?? 389),
            (string) ($server['ldap_base'] ?? ''),
            (string) ($server['ldap_user'] ?? ''),
            (string) ($server['ldap_password'] ?? ''),
        );

        if ($this->ldapCore->error) {
            $this->configuration->getLogger()->error($this->ldapCore->error);
            $this->errors[] = $this->ldapCore->error;
        }
    }

    private function createUser(): User
    {
        if ($this->userFactory instanceof Closure) {
            $user = ($this->userFactory)();
            if ($user instanceof User) {
                return $user;
            }
        }

        return new User($this->configuration);
    }

    private function createMediumPermission(): MediumPermission
    {
        if ($this->mediumPermissionFactory instanceof Closure) {
            $mediumPermission = ($this->mediumPermissionFactory)();
            if ($mediumPermission instanceof MediumPermission) {
                return $mediumPermission;
            }
        }

        return new MediumPermission($this->configuration);
    }
}
