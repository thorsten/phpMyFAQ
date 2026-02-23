<?php

/**
 * Hybrid configuration storage (database + optional Redis cache)
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

use Psr\Log\LoggerInterface;
use RuntimeException;

readonly class HybridConfigurationStore implements ConfigurationStoreInterface
{
    public function __construct(
        private DatabaseConfigurationStore $databaseConfigurationStore,
        private ConfigurationStorageSettingsResolver $settingsResolver,
        private LoggerInterface $logger,
    ) {
    }

    public function updateConfigValue(string $key, string $value): bool
    {
        $result = $this->databaseConfigurationStore->updateConfigValue($key, $value);
        if (!$result) {
            return false;
        }

        $redisStore = $this->resolveRedisStore();
        if ($redisStore !== null) {
            try {
                $redisStore->updateConfigValue($key, $value);
            } catch (RuntimeException $exception) {
                $this->logger->warning('Failed to update configuration key in Redis storage.', [
                    'key' => $key,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return true;
    }

    /**
     * @return array<int, object>
     */
    public function fetchAll(): array
    {
        $redisStore = $this->resolveRedisStore();
        if ($redisStore !== null) {
            try {
                $rows = $redisStore->fetchAll();
                if ($rows !== []) {
                    return $rows;
                }
            } catch (RuntimeException $exception) {
                $this->logger->warning('Failed to fetch configuration from Redis storage.', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $rows = $this->databaseConfigurationStore->fetchAll();

        if ($redisStore !== null && $rows !== []) {
            try {
                $redisStore->warmFromRows($rows);
            } catch (RuntimeException $exception) {
                $this->logger->warning('Failed to warm Redis configuration storage from database.', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $rows;
    }

    public function insert(string $name, string $value): bool
    {
        $result = $this->databaseConfigurationStore->insert($name, $value);
        if (!$result) {
            return false;
        }

        $redisStore = $this->resolveRedisStore();
        if ($redisStore !== null) {
            try {
                $redisStore->insert($name, $value);
            } catch (RuntimeException $exception) {
                $this->logger->warning('Failed to insert configuration key into Redis storage.', [
                    'key' => $name,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return true;
    }

    public function delete(string $name): bool
    {
        $result = $this->databaseConfigurationStore->delete($name);
        if (!$result) {
            return false;
        }

        $redisStore = $this->resolveRedisStore();
        if ($redisStore !== null) {
            try {
                $redisStore->delete($name);
            } catch (RuntimeException $exception) {
                $this->logger->warning('Failed to delete configuration key from Redis storage.', [
                    'key' => $name,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return true;
    }

    public function renameKey(string $currentKey, string $newKey): bool
    {
        $result = $this->databaseConfigurationStore->renameKey($currentKey, $newKey);
        if (!$result) {
            return false;
        }

        $redisStore = $this->resolveRedisStore();
        if ($redisStore !== null) {
            try {
                $redisStore->renameKey($currentKey, $newKey);
            } catch (RuntimeException $exception) {
                $this->logger->warning('Failed to rename configuration key in Redis storage.', [
                    'currentKey' => $currentKey,
                    'newKey' => $newKey,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return true;
    }

    private function resolveRedisStore(): ?RedisConfigurationStore
    {
        $settings = $this->settingsResolver->resolve();
        if (!$settings->enabled) {
            return null;
        }

        return new RedisConfigurationStore($settings);
    }
}
