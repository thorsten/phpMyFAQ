<?php

declare(strict_types=1);

namespace phpMyFAQ\Cache;

use PHPUnit\Framework\TestCase;

class CacheSettingsTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $settings = new CacheSettings(
            adapter: 'redis',
            redisDsn: 'tcp://localhost:6379?database=2',
            redisPrefix: 'pmf:test:',
            connectTimeout: 2.5,
            defaultTtl: 7200,
        );

        $this->assertSame('redis', $settings->adapter);
        $this->assertSame('tcp://localhost:6379?database=2', $settings->redisDsn);
        $this->assertSame('pmf:test:', $settings->redisPrefix);
        $this->assertSame(2.5, $settings->connectTimeout);
        $this->assertSame(7200, $settings->defaultTtl);
    }

    public function testConstructorWithFilesystemDefaults(): void
    {
        $settings = new CacheSettings(
            adapter: 'filesystem',
            redisDsn: '',
            redisPrefix: '',
            connectTimeout: 1.0,
            defaultTtl: 3600,
        );

        $this->assertSame('filesystem', $settings->adapter);
        $this->assertSame(3600, $settings->defaultTtl);
    }
}
