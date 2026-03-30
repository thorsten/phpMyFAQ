<?php

/**
 * Resolves cache settings from database configuration values.
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
 * @since     2026-03-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Cache;

use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;

readonly class CacheSettingsResolver
{
    private const string DEFAULT_ADAPTER = 'filesystem';
    private const string DEFAULT_REDIS_DSN = 'redis://redis:6379/2';
    private const string DEFAULT_REDIS_PREFIX = 'pmf_cache_';
    private const float DEFAULT_CONNECT_TIMEOUT = 1.0;
    private const int DEFAULT_TTL = 3600;

    public function __construct(
        private DatabaseConfigurationStore $databaseConfigurationStore,
    ) {
    }

    public function resolve(): CacheSettings
    {
        $values = $this->databaseConfigurationStore->fetchValues([
            'storage.cacheAdapter',
            'storage.cacheRedisDsn',
            'storage.cacheRedisPrefix',
            'storage.cacheRedisConnectTimeout',
            'storage.cacheDefaultTtl',
        ]);

        $adapter = trim($values['storage.cacheAdapter'] ?? '');
        if (!in_array($adapter, ['filesystem', 'redis'], strict: true)) {
            $adapter = self::DEFAULT_ADAPTER;
        }

        $redisDsn = trim($values['storage.cacheRedisDsn'] ?? '');
        if ($redisDsn === '') {
            $redisDsn = self::DEFAULT_REDIS_DSN;
        }

        $redisPrefix = trim($values['storage.cacheRedisPrefix'] ?? '');
        if ($redisPrefix === '') {
            $redisPrefix = self::DEFAULT_REDIS_PREFIX;
        }
        $redisPrefix = $this->normalizeRedisPrefix($redisPrefix);

        $connectTimeout = (float) ($values['storage.cacheRedisConnectTimeout'] ?? '');
        if ($connectTimeout <= 0) {
            $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;
        }

        $defaultTtl = (int) ($values['storage.cacheDefaultTtl'] ?? '');
        if ($defaultTtl <= 0) {
            $defaultTtl = self::DEFAULT_TTL;
        }

        return new CacheSettings($adapter, $redisDsn, $redisPrefix, $connectTimeout, $defaultTtl);
    }

    private function normalizeRedisPrefix(string $redisPrefix): string
    {
        $normalizedPrefix = preg_replace('/[^-+_.A-Za-z0-9]/', '_', subject: $redisPrefix) ?? '';

        return trim($normalizedPrefix, characters: '_') !== '' ? $normalizedPrefix : self::DEFAULT_REDIS_PREFIX;
    }
}
