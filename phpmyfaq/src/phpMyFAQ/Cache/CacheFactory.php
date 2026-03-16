<?php

/**
 * Factory for creating cache adapters based on configuration.
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

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use RuntimeException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;

readonly class CacheFactory
{
    public function __construct(
        private Configuration $configuration,
        private string $cacheDir,
    ) {
    }

    public function create(): CacheInterface
    {
        $resolver = new CacheSettingsResolver(new DatabaseConfigurationStore($this->configuration->getDb()));
        $settings = $resolver->resolve();

        return match ($settings->adapter) {
            'redis' => $this->createRedisAdapter($settings),
            default => $this->createFilesystemAdapter($settings),
        };
    }

    private function createFilesystemAdapter(CacheSettings $settings): FilesystemAdapter
    {
        return new FilesystemAdapter(
            namespace: 'pmf',
            defaultLifetime: $settings->defaultTtl,
            directory: $this->cacheDir,
        );
    }

    private function createRedisAdapter(CacheSettings $settings): RedisAdapter
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException(
                'The "redis" PHP extension is required when using the Redis cache adapter. '
                . 'Please install ext-redis or change storage.cacheAdapter to "filesystem".',
            );
        }

        $connection = RedisAdapter::createConnection($settings->redisDsn, ['timeout' => $settings->connectTimeout]);

        return new RedisAdapter(
            redis: $connection,
            namespace: $settings->redisPrefix,
            defaultLifetime: $settings->defaultTtl,
        );
    }
}
