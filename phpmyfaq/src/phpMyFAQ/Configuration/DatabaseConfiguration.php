<?php

/**
 * Database configuration class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-30
 */

namespace phpMyFAQ\Configuration;

readonly class DatabaseConfiguration
{
    private string $server;

    private ?int $port;

    private string $user;

    private string $password;

    private string $db;

    private string $prefix;

    private string $type;

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
        $this->port = $DB['port'] === '' ? null : (int) $DB['port'];
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
