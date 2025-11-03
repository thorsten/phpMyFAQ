<?php

/**
 * LDAP configuration class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

class LdapConfiguration
{
    private readonly string $mainServer;

    private readonly int $mainPort;

    private readonly string $mainUser;

    private readonly string $mainPassword;

    private readonly string $mainBase;

    private array $servers = [];

    public function __construct(string $filename)
    {
        $PMF_LDAP = [
            'ldap_server' => '',
            'ldap_port' => 389,
            'ldap_user' => '',
            'ldap_password' => '',
            'ldap_base' => '',
        ];

        include $filename;

        $this->mainServer = $PMF_LDAP['ldap_server'];
        $this->mainPort = (int) $PMF_LDAP['ldap_port'];
        $this->mainUser = $PMF_LDAP['ldap_user'];
        $this->mainPassword = $PMF_LDAP['ldap_password'];
        $this->mainBase = $PMF_LDAP['ldap_base'];

        foreach ($PMF_LDAP as $key => $server) {
            if (is_array($server)) {
                $this->servers[$key] = [
                    'server' => $server['ldap_server'],
                    'port' => (int) $server['ldap_port'],
                    'user' => $server['ldap_user'],
                    'password' => $server['ldap_password'],
                    'base' => $server['ldap_base'],
                ];
            }
        }
    }

    public function getMainServer(): string
    {
        return $this->mainServer;
    }

    public function getMainPort(): int
    {
        return $this->mainPort;
    }

    public function getMainUser(): string
    {
        return $this->mainUser;
    }

    public function getMainPassword(): string
    {
        return $this->mainPassword;
    }

    public function getMainBase(): string
    {
        return $this->mainBase;
    }

    public function getServers(): array
    {
        return $this->servers;
    }
}
