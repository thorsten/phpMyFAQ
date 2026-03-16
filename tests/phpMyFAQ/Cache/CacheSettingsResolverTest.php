<?php

declare(strict_types=1);

namespace phpMyFAQ\Cache;

use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use PHPUnit\Framework\TestCase;

class CacheSettingsResolverTest extends TestCase
{
    public function testResolveWithDefaults(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store->method('fetchValue')->willReturn(null);

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('filesystem', $settings->adapter);
        $this->assertSame('tcp://redis:6379?database=2', $settings->redisDsn);
        $this->assertSame('pmf:cache:', $settings->redisPrefix);
        $this->assertSame(1.0, $settings->connectTimeout);
        $this->assertSame(3600, $settings->defaultTtl);
    }

    public function testResolveWithRedisAdapter(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store->method('fetchValue')->willReturnCallback(
            static fn(string $key): ?string => match ($key) {
                'storage.cacheAdapter' => 'redis',
                'storage.cacheRedisDsn' => 'tcp://myredis:6380?database=5',
                'storage.cacheRedisPrefix' => 'app:cache:',
                'storage.cacheRedisConnectTimeout' => '3.0',
                'storage.cacheDefaultTtl' => '1800',
                default => null,
            },
        );

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('redis', $settings->adapter);
        $this->assertSame('tcp://myredis:6380?database=5', $settings->redisDsn);
        $this->assertSame('app:cache:', $settings->redisPrefix);
        $this->assertSame(3.0, $settings->connectTimeout);
        $this->assertSame(1800, $settings->defaultTtl);
    }

    public function testResolveWithInvalidAdapterFallsBackToFilesystem(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store->method('fetchValue')->willReturnCallback(
            static fn(string $key): ?string => match ($key) {
                'storage.cacheAdapter' => 'memcached',
                default => null,
            },
        );

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('filesystem', $settings->adapter);
    }

    public function testResolveWithEmptyStringsFallsBackToDefaults(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store->method('fetchValue')->willReturn('');

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('filesystem', $settings->adapter);
        $this->assertSame('tcp://redis:6379?database=2', $settings->redisDsn);
        $this->assertSame('pmf:cache:', $settings->redisPrefix);
        $this->assertSame(1.0, $settings->connectTimeout);
        $this->assertSame(3600, $settings->defaultTtl);
    }

    public function testResolveWithNegativeTimeoutFallsBackToDefault(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store->method('fetchValue')->willReturnCallback(
            static fn(string $key): ?string => match ($key) {
                'storage.cacheRedisConnectTimeout' => '-1',
                'storage.cacheDefaultTtl' => '0',
                default => null,
            },
        );

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame(1.0, $settings->connectTimeout);
        $this->assertSame(3600, $settings->defaultTtl);
    }
}
