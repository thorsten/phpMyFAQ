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
use RedisException;
use RuntimeException;

class RedisConfigurationStore implements ConfigurationStoreInterface
{
    private ?Redis $redisClient = null;

    public function __construct(
        private readonly ConfigurationStorageSettings $settings,
    ) {
    }

    public function updateConfigValue(string $key, string $value): bool
    {
        try {
            $redis = $this->getRedisClient();
            return false !== $redis->hSet($this->getHashKey(), $key, $value);
        } catch (RedisException $e) {
            throw new RuntimeException('Redis updateConfigValue failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<int, object>
     */
    public function fetchAll(): array
    {
        try {
            $redis = $this->getRedisClient();
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
        } catch (RedisException $e) {
            throw new RuntimeException('Redis fetchAll failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function insert(string $name, string $value): bool
    {
        try {
            $redis = $this->getRedisClient();
            return (bool) $redis->hSetNx($this->getHashKey(), $name, $value);
        } catch (RedisException $e) {
            throw new RuntimeException('Redis insert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function delete(string $name): bool
    {
        try {
            $redis = $this->getRedisClient();
            $result = $redis->hDel($this->getHashKey(), $name);
            return $result !== false && $result > 0;
        } catch (RedisException $e) {
            throw new RuntimeException('Redis delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function renameKey(string $currentKey, string $newKey): bool
    {
        try {
            $redis = $this->getRedisClient();
            $oldValue = $redis->hGet($this->getHashKey(), $currentKey);
            if ($oldValue === false) {
                return false;
            }

            $setResult = $redis->hSet($this->getHashKey(), $newKey, (string) $oldValue);
            if ($setResult === false) {
                return false;
            }

            if ($redis->hDel($this->getHashKey(), $currentKey) < 1) {
                $redis->hDel($this->getHashKey(), $newKey);
                return false;
            }

            return true;
        } catch (RedisException $e) {
            throw new RuntimeException('Redis renameKey failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getInstalledRedisVersion(): string
    {
        try {
            $redis = $this->getRedisClient();
            $serverInfo = $redis->info('server');

            $serverVersion = 'unknown';
            if (is_array($serverInfo) && array_key_exists('redis_version', $serverInfo)) {
                $serverVersion = (string) $serverInfo['redis_version'];
            }
            $configuredExtensionVersion = phpversion('redis');
            $extensionVersion = (string) (
                $configuredExtensionVersion !== false ? $configuredExtensionVersion : 'unknown'
            );

            return sprintf('%s (ext-redis %s)', $serverVersion, $extensionVersion);
        } catch (RedisException $e) {
            throw new RuntimeException('Redis getInstalledRedisVersion failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<int, object> $rows
     */
    public function warmFromRows(array $rows): bool
    {
        if ($rows === []) {
            return true;
        }

        try {
            $redis = $this->getRedisClient();
            $keyValueMap = [];
            foreach ($rows as $row) {
                if (!property_exists($row, 'config_name') || $row->config_name === null) {
                    continue;
                }

                $keyValueMap[(string) $row->config_name] = (string) ($row->config_value ?? '');
            }

            if ($keyValueMap === []) {
                return true;
            }

            $hashKey = $this->getHashKey();
            $redis->multi();
            $redis->del($hashKey);
            $redis->hMSet($hashKey, $keyValueMap);
            $results = $redis->exec();

            return is_array($results) && ($results[1] ?? null) !== false;
        } catch (RedisException $e) {
            throw new RuntimeException('Redis warmFromRows failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function getRedisClient(): Redis
    {
        if ($this->redisClient === null) {
            $this->redisClient = $this->createRedisClient();
        }

        return $this->redisClient;
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
        if ($parsedUrl === false || !array_key_exists('scheme', $parsedUrl)) {
            throw new RuntimeException('Invalid Redis DSN for configuration storage.');
        }

        try {
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

            if (array_key_exists('pass', $parsedUrl) && $parsedUrl['pass'] !== '') {
                $pass = urldecode($parsedUrl['pass']);
                if (array_key_exists('user', $parsedUrl) && $parsedUrl['user'] !== '') {
                    $user = urldecode($parsedUrl['user']);
                    $authResult = $redis->auth([$user, $pass]);
                } else {
                    $authResult = $redis->auth($pass);
                }

                if ($authResult !== true) {
                    throw new RuntimeException('Redis authentication failed for configuration storage.');
                }
            }

            $database = 0;
            if (array_key_exists('query', $parsedUrl)) {
                parse_str($parsedUrl['query'], $queryParams);
                $database = (int) ($queryParams['database'] ?? $queryParams['db'] ?? 0);
            }

            if ($redis->select($database) !== true) {
                throw new RuntimeException(sprintf(
                    'Failed to select Redis database %d for configuration storage.',
                    $database,
                ));
            }

            $readTimeout = $timeout > 0 ? $timeout : (float) ini_get('default_socket_timeout');
            $redis->setOption(Redis::OPT_READ_TIMEOUT, $readTimeout);

            return $redis;
        } catch (RedisException $e) {
            throw new RuntimeException('Redis connection failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
