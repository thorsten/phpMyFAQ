<?php

namespace phpMyFAQ\Configuration;

class LdapConfiguration
{
    private string $mainServer;
    private int $mainPort;
    private string $mainUser;
    private string $mainPassword;
    private string $mainBase;
    private array $servers;

    public function __construct(string $filename)
    {
        $PMF_LDAP = [
            'ldap_server' => '',
            'ldap_port' => 389,
            'ldap_user' => '',
            'ldap_password' => '',
            'ldap_base' => '',
        ];

        include($filename);

        $this->mainServer = $PMF_LDAP['ldap_server'];
        $this->mainPort = $PMF_LDAP['ldap_port'];
        $this->mainUser = $PMF_LDAP['ldap_user'];
        $this->mainPassword = $PMF_LDAP['ldap_password'];
        $this->mainBase = $PMF_LDAP['ldap_base'];

        $this->servers = [];

        foreach ($PMF_LDAP as $key => $server) {
            if (is_array($server)) {
                $this->servers[$key] = array(
                    'server' => $server['ldap_server'],
                    'port' => $server['ldap_port'],
                    'user' => $server['ldap_user'],
                    'password' => $server['ldap_password'],
                    'base' => $server['ldap_base']
                );
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
