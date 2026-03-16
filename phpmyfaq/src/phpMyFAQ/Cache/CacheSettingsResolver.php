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
    private const string DEFAULT_REDIS_DSN = 'tcp://redis:6379?database=2';
    private const string DEFAULT_REDIS_PREFIX = 'pmf:cache:';
    private const float DEFAULT_CONNECT_TIMEOUT = 1.0;
    private const int DEFAULT_TTL = 3600;

    public function __construct(
        private DatabaseConfigurationStore $databaseConfigurationStore,
    ) {
    }

    public function resolve(): CacheSettings
    {
        $adapter = trim($this->databaseConfigurationStore->fetchValue('storage.cacheAdapter') ?? '');
        if (!in_array($adapter, ['filesystem', 'redis'], strict: true)) {
            $adapter = self::DEFAULT_ADAPTER;
        }

        $redisDsn = trim($this->databaseConfigurationStore->fetchValue('storage.cacheRedisDsn') ?? '');
        if ($redisDsn === '') {
            $redisDsn = self::DEFAULT_REDIS_DSN;
        }

        $redisPrefix = $this->databaseConfigurationStore->fetchValue('storage.cacheRedisPrefix') ?? '';
        if ($redisPrefix === '') {
            $redisPrefix = self::DEFAULT_REDIS_PREFIX;
        }

        $connectTimeout = (float) (
            $this->databaseConfigurationStore->fetchValue('storage.cacheRedisConnectTimeout') ?? ''
        );
        if ($connectTimeout <= 0) {
            $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;
        }

        $defaultTtl = (int) ($this->databaseConfigurationStore->fetchValue('storage.cacheDefaultTtl') ?? '');
        if ($defaultTtl <= 0) {
            $defaultTtl = self::DEFAULT_TTL;
        }

        return new CacheSettings($adapter, $redisDsn, $redisPrefix, $connectTimeout, $defaultTtl);
    }
}
