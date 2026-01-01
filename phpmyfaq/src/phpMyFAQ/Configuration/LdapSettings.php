<?php

/**
 * The LDAP settings class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;

readonly class LdapSettings
{
    public function __construct(
        private CoreConfiguration $configuration,
    ) {
    }

    /**
     * Erzeugt das core.ldapServer-Array basierend auf der übergebenen LdapConfiguration.
     * @return array<int, array<string, mixed>>
     */
    public function buildServers(LdapConfiguration $ldapConfiguration): array
    {
        $servers = [];

        // Main LDAP server
        $servers[0] = [
            'ldap_server' => $ldapConfiguration->getMainServer(),
            'ldap_port' => $ldapConfiguration->getMainPort(),
            'ldap_user' => $ldapConfiguration->getMainUser(),
            'ldap_password' => $ldapConfiguration->getMainPassword(),
            'ldap_base' => $ldapConfiguration->getMainBase(),
        ];

        // Additional servers if enabled
        if (true === $this->configuration->get(item: 'ldap.ldap_use_multiple_servers')) {
            $key = 1;
            while (isset($ldapConfiguration->getServers()[$key])) {
                $servers[$key] = $ldapConfiguration->getServers()[$key];
                ++$key;
            }
        }

        return $servers;
    }

    /**
     * Builds the core.ldapConfig array from current configuration values.
     * @return array<string, mixed>
     */
    public function buildConfig(): array
    {
        return [
            'ldap_use_multiple_servers' => $this->configuration->get(item: 'ldap.ldap_use_multiple_servers'),
            'ldap_mapping' => $this->getLdapMapping(),
            'ldap_use_domain_prefix' => $this->configuration->get(item: 'ldap.ldap_use_domain_prefix'),
            'ldap_options' => $this->getLdapOptions(),
            'ldap_use_memberOf' => $this->configuration->get(item: 'ldap.ldap_use_memberOf'),
            'ldap_use_sasl' => $this->configuration->get(item: 'ldap.ldap_use_sasl'),
            'ldap_use_anonymous_login' => $this->configuration->get(item: 'ldap.ldap_use_anonymous_login'),
            'ldap_group_config' => $this->getLdapGroupConfig(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getLdapMapping(): array
    {
        return [
            'name' => $this->configuration->get(item: 'ldap.ldap_mapping.name'),
            'username' => $this->configuration->get(item: 'ldap.ldap_mapping.username'),
            'mail' => $this->configuration->get(item: 'ldap.ldap_mapping.mail'),
            'memberOf' => $this->configuration->get(item: 'ldap.ldap_mapping.memberOf'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLdapOptions(): array
    {
        return [
            'LDAP_OPT_PROTOCOL_VERSION' => $this->configuration->get(
                item: 'ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION',
            ),
            'LDAP_OPT_REFERRALS' => $this->configuration->get(item: 'ldap.ldap_options.LDAP_OPT_REFERRALS'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLdapGroupConfig(): array
    {
        $allowedGroups = $this->configuration->get(item: 'ldap.ldap_group_allowed_groups');
        $groupMapping = $this->configuration->get(item: 'ldap.ldap_group_mapping');

        return [
            'use_group_restriction' => $this->configuration->get(item: 'ldap.ldap_use_group_restriction'),
            'allowed_groups' => $allowedGroups ? explode(separator: ',', string: (string) $allowedGroups) : [],
            'auto_assign' => $this->configuration->get(item: 'ldap.ldap_group_auto_assign'),
            'group_mapping' => $groupMapping ? json_decode((string) $groupMapping, associative: true) : [],
        ];
    }

    public function isActive(): bool
    {
        return (bool) $this->configuration->get(item: 'ldap.ldapSupport');
    }
}
