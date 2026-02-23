<?php

/**
 * Redis-backed configuration storage
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
 * @since     2026-02-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage;

use Redis;
use RuntimeException;

readonly class RedisConfigurationStore implements ConfigurationStoreInterface
{
    public function __construct(
        private ConfigurationStorageSettings $settings,
    ) {
    }

    public function updateConfigValue(string $key, string $value): bool
    {
        $redis = $this->createRedisClient();
        return false !== $redis->hSet($this->getHashKey(), $key, $value);
    }

    /**
     * @return array<int, object>
     */
    public function fetchAll(): array
    {
        $redis = $this->createRedisClient();
        $entries = $redis->hGetAll($this->getHashKey());
        if (!is_array($entries) || $entries === []) {
            return [];
        }

        $rows = [];
        foreach ($entries as $name => $value) {
            $rows[] = (object) [
                'config_name' => (string) $name,
                'config_value' => (string) $value,
            ];
        }

        return $rows;
    }

    public function insert(string $name, string $value): bool
    {
        $redis = $this->createRedisClient();
        return (bool) $redis->hSetNx($this->getHashKey(), $name, $value);
    }

    public function delete(string $name): bool
    {
        $redis = $this->createRedisClient();
        return $redis->hDel($this->getHashKey(), $name) >= 0;
    }

    public function renameKey(string $currentKey, string $newKey): bool
    {
        $redis = $this->createRedisClient();
        $oldValue = $redis->hGet($this->getHashKey(), $currentKey);
        if ($oldValue === false) {
            return false;
        }

        $setResult = $redis->hSet($this->getHashKey(), $newKey, (string) $oldValue);
        if ($setResult === false) {
            return false;
        }

        $redis->hDel($this->getHashKey(), $currentKey);
        return true;
    }

    public function getInstalledRedisVersion(): string
    {
        $redis = $this->createRedisClient();
        $serverInfo = $redis->info('server');

        $serverVersion = is_array($serverInfo) && isset($serverInfo['redis_version'])
            ? (string) $serverInfo['redis_version']
            : 'unknown';
        $extensionVersion = (string) (phpversion('redis') ?: 'unknown');

        return sprintf('%s (ext-redis %s)', $serverVersion, $extensionVersion);
    }

    /**
     * @param array<int, object> $rows
     */
    public function warmFromRows(array $rows): bool
    {
        if ($rows === []) {
            return true;
        }

        $redis = $this->createRedisClient();
        $keyValueMap = [];
        foreach ($rows as $row) {
            if (!isset($row->config_name)) {
                continue;
            }

            $keyValueMap[(string) $row->config_name] = (string) ($row->config_value ?? '');
        }

        if ($keyValueMap === []) {
            return true;
        }

        return false !== $redis->hMSet($this->getHashKey(), $keyValueMap);
    }

    private function getHashKey(): string
    {
        return $this->settings->redisPrefix . 'items';
    }

    private function createRedisClient(): Redis
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('Redis configuration storage requires the PHP redis extension (ext-redis).');
        }

        $parsedUrl = parse_url($this->settings->redisDsn);
        if ($parsedUrl === false || !isset($parsedUrl['scheme'])) {
            throw new RuntimeException('Invalid Redis DSN for configuration storage.');
        }

        $redis = new Redis();
        $scheme = strtolower((string) $parsedUrl['scheme']);
        $timeout = $this->settings->connectTimeout;

        if ($scheme === 'redis' || $scheme === 'tcp') {
            $host = $parsedUrl['host'] ?? '127.0.0.1';
            $port = (int) ($parsedUrl['port'] ?? 6379);
            $connected = $redis->connect($host, $port, $timeout);
            if ($connected !== true) {
                throw new RuntimeException(sprintf('Unable to connect to Redis at %s:%d', $host, $port));
            }
        } elseif ($scheme === 'unix') {
            $path = $parsedUrl['path'] ?? '';
            if ($path === '') {
                throw new RuntimeException('Invalid Redis unix socket DSN for configuration storage.');
            }

            $connected = $redis->connect($path, 0, $timeout);
            if ($connected !== true) {
                throw new RuntimeException(sprintf('Unable to connect to Redis unix socket at %s', $path));
            }
        } else {
            throw new RuntimeException(sprintf(
                'Unsupported Redis DSN scheme "%s" for configuration storage.',
                $scheme,
            ));
        }

        if (isset($parsedUrl['pass']) && $parsedUrl['pass'] !== '') {
            if ($redis->auth($parsedUrl['pass']) !== true) {
                throw new RuntimeException('Redis authentication failed for configuration storage.');
            }
        }

        $database = 0;
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            $database = (int) ($queryParams['database'] ?? $queryParams['db'] ?? 0);
        }

        if ($redis->select($database) !== true) {
            throw new RuntimeException(sprintf(
                'Failed to select Redis database %d for configuration storage.',
                $database,
            ));
        }

        return $redis;
    }
}
