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
        $store
            ->expects($this->once())
            ->method('fetchValues')
            ->with([
                'storage.cacheAdapter',
                'storage.cacheRedisDsn',
                'storage.cacheRedisPrefix',
                'storage.cacheRedisConnectTimeout',
                'storage.cacheDefaultTtl',
            ])
            ->willReturn([]);

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('filesystem', $settings->adapter);
        $this->assertSame('redis://redis:6379/2', $settings->redisDsn);
        $this->assertSame('pmf_cache_', $settings->redisPrefix);
        $this->assertSame(1.0, $settings->connectTimeout);
        $this->assertSame(3600, $settings->defaultTtl);
    }

    public function testResolveWithRedisAdapter(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store
            ->expects($this->once())
            ->method('fetchValues')
            ->willReturnCallback(static fn(array $keys): array => array_replace(array_fill_keys($keys, null), [
                'storage.cacheAdapter' => 'redis',
                'storage.cacheRedisDsn' => 'tcp://myredis:6380?database=5',
                'storage.cacheRedisPrefix' => 'app:cache:',
                'storage.cacheRedisConnectTimeout' => '3.0',
                'storage.cacheDefaultTtl' => '1800',
            ]));

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('redis', $settings->adapter);
        $this->assertSame('tcp://myredis:6380?database=5', $settings->redisDsn);
        $this->assertSame('app_cache_', $settings->redisPrefix);
        $this->assertSame(3.0, $settings->connectTimeout);
        $this->assertSame(1800, $settings->defaultTtl);
    }

    public function testResolveWithInvalidAdapterFallsBackToFilesystem(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store->expects($this->once())->method('fetchValues')->willReturn(['storage.cacheAdapter' => 'memcached']);

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('filesystem', $settings->adapter);
    }

    public function testResolveWithEmptyStringsFallsBackToDefaults(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store
            ->expects($this->once())
            ->method('fetchValues')
            ->willReturn([
                'storage.cacheAdapter' => '',
                'storage.cacheRedisDsn' => '',
                'storage.cacheRedisPrefix' => '',
                'storage.cacheRedisConnectTimeout' => '',
                'storage.cacheDefaultTtl' => '',
            ]);

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('filesystem', $settings->adapter);
        $this->assertSame('redis://redis:6379/2', $settings->redisDsn);
        $this->assertSame('pmf_cache_', $settings->redisPrefix);
        $this->assertSame(1.0, $settings->connectTimeout);
        $this->assertSame(3600, $settings->defaultTtl);
    }

    public function testResolveWithInvalidRedisPrefixFallsBackToDefault(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store
            ->expects($this->once())
            ->method('fetchValues')
            ->willReturn([
                'storage.cacheAdapter' => 'redis',
                'storage.cacheRedisPrefix' => ':::',
            ]);

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame('pmf_cache_', $settings->redisPrefix);
    }

    public function testResolveWithNegativeTimeoutFallsBackToDefault(): void
    {
        $store = $this->createMock(DatabaseConfigurationStore::class);
        $store
            ->expects($this->once())
            ->method('fetchValues')
            ->willReturnCallback(static fn(array $keys): array => array_replace(array_fill_keys($keys, null), [
                'storage.cacheRedisConnectTimeout' => '-1',
                'storage.cacheDefaultTtl' => '0',
            ]));

        $resolver = new CacheSettingsResolver($store);
        $settings = $resolver->resolve();

        $this->assertSame(1.0, $settings->connectTimeout);
        $this->assertSame(3600, $settings->defaultTtl);
    }
}
