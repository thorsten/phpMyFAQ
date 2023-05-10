<?php

namespace phpMyFAQ\Configuration;

class DatabaseConfiguration
{
    private readonly string $server;
    private readonly ?int $port;
    private readonly string $user;
    private readonly string $password;
    private readonly string $db;
    private readonly string $prefix;
    private readonly string $type;

    public function __construct(string $filename)
    {
        $DB = [
            'server' => '',
            'port' => '',
            'user' => '',
            'password' => '',
            'db' => '',
            'prefix' => '',
            'type' => '',
        ];

        include($filename);

        $this->server = $DB['server'];
        $this->port = strlen($DB['port']) === 0 ? null : (int) $DB['port'];
        $this->user = $DB['user'];
        $this->password = $DB['password'];
        $this->db = $DB['db'];
        $this->prefix = $DB['prefix'];
        $this->type = $DB['type'];
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getDatabase(): string
    {
        return $this->db;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
