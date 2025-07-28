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
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-01
 */

namespace phpMyFAQ\Auth;

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
    private ?LdapCore $ldapCore = null;

    /** @var string[] Array of LDAP servers */
    private readonly array $ldapServer;

    /** @var int Active LDAP server */
    private int $activeServer = 0;

    /** @var bool */
    private readonly mixed $multipleServers;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->ldapServer = $this->configuration->getLdapServer();
        $this->multipleServers = $this->configuration->get('ldap.ldap_use_multiple_servers');

        parent::__construct($this->configuration);

        if (0 === (is_countable($this->ldapServer) ? count($this->ldapServer) : 0)) {
            throw new AuthException('An error occurred while contacting LDAP: No configuration found.');
        }

        $this->ldapCore = new LdapCore($this->configuration);
        $this->connect($this->activeServer);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, #[SensitiveParameter] string $password, string $domain = ''): bool
    {
        $result = false;
        $user = new User($this->configuration);

        try {
            $result = $user->createUser($login, '', $domain);
        } catch (\Exception $exception) {
            $this->configuration->getLogger()->info($exception->getMessage());
        }

        $this->connect($this->activeServer);

        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_LDAP->value);

        // Set user information from LDAP
        $user->setUserData(
            [
                'display_name' => $this->ldapCore->getCompleteName($login),
                'email' => $this->ldapCore->getMail($login)
            ]
        );

        // Handle group assignments if enabled
        $ldapGroupConfig = $this->configuration->getLdapGroupConfig();
        if ($ldapGroupConfig['auto_assign'] && $this->configuration->get('security.permLevel') === 'medium') {
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

        $mediumPermission = new MediumPermission($this->configuration);
        $groupMapping = $ldapGroupConfig['group_mapping'];

        foreach ($userGroups as $userGroup) {
            $groupName = $this->extractGroupNameFromDn($userGroup);

            // Check if there's a specific mapping for this AD group
            if (!empty($groupMapping) && isset($groupMapping[$groupName])) {
                $faqGroupName = $groupMapping[$groupName];
            } else {
                // Default: use the AD group name
                $faqGroupName = $groupName;
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
        #[SensitiveParameter] string $password,
        ?array $optionalData = null
    ): bool {
        if ('' === trim($password)) {
            throw new AuthException(User::ERROR_USER_INCORRECT_PASSWORD);
        }

        // Get active LDAP server for current user
        if ($this->multipleServers) {
            // Try all LDAP servers
            foreach (array_keys($this->ldapServer) as $key) {
                $this->connect($key);
                $this->activeServer = (int)$key;
                break;
            }
        }

        $bindLogin = $login;
        if ($this->configuration->get('ldap.ldap_use_domain_prefix')) {
            if (array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'] . '\\' . $login;
            }
        } else {
            $this->connect($this->activeServer);

            $bindLogin = $this->ldapCore->getDn($login);
        }

        // Check user in LDAP
        $this->ldapCore = new LdapCore($this->configuration);
        $this->ldapCore->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
            $bindLogin,
            htmlspecialchars_decode($password)
        );

        if (!$this->ldapCore->bind($bindLogin, htmlspecialchars_decode($password))) {
            throw new AuthException($this->ldapCore->error);
        }

        // Check AD group membership restrictions if enabled
        $ldapGroupConfig = $this->configuration->getLdapGroupConfig();
        if ($ldapGroupConfig['use_group_restriction'] === 'true') {
            $userGroups = $this->ldapCore->getGroupMemberships($login);
            if ($userGroups === false) {
                throw new AuthException('Unable to retrieve user group memberships');
            }

            $allowedGroups = $ldapGroupConfig['allowed_groups'];
            if (!empty($allowedGroups)) {
                $hasAllowedGroup = false;
                foreach ($userGroups as $userGroup) {
                    foreach ($allowedGroups as $allowedGroup) {
                        if (str_contains($userGroup, trim((string) $allowedGroup))) {
                            $hasAllowedGroup = true;
                            break 2;
                        }
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
            // Try all LDAP servers
            foreach (array_keys($this->ldapServer) as $key) {
                $this->connect($key);
                $this->activeServer = (int)$key;
                break;
            }
        }

        $this->connect($this->activeServer);

        return strlen((string) $this->ldapCore->getCompleteName($login));
    }

    private function connect(int $activeServer = 0): void
    {
        $this->ldapCore->connect(
            $this->ldapServer[$activeServer]['ldap_server'],
            $this->ldapServer[$activeServer]['ldap_port'],
            $this->ldapServer[$activeServer]['ldap_base'],
            $this->ldapServer[$activeServer]['ldap_user'],
            $this->ldapServer[$activeServer]['ldap_password']
        );

        if ($this->ldapCore->error) {
            $this->configuration->getLogger()->error($this->ldapCore->error);
            $this->errors[] = $this->ldapCore->error;
        }
    }
}
