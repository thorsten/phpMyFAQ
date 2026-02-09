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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

readonly class DatabaseConfiguration
{
    private string $server;

    private ?int $port;

    private string $user;

    private string $password;

    private string $database;

    private string $prefix;

    private string $type;

    private ?string $schema;

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
            'schema' => '',
        ];

        include $filename;

        $this->server = $DB['server'];
        $this->port = $DB['port'] === '' ? null : (int) $DB['port'];
        $this->user = $DB['user'];
        $this->password = $DB['password'];
        $this->database = $DB['db'];
        $this->prefix = $DB['prefix'];
        $this->type = $DB['type'];
        $this->schema = $DB['schema'] === '' ? null : $DB['schema'];
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
        return $this->database;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }
}
