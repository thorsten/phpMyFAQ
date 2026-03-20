<?php

declare(strict_types=1);

namespace phpMyFAQ\Cache;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

#[AllowMockObjectsWithoutExpectations]
class CacheFactoryTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $v): string => $v);
        $db->method('query')->willReturn(false);
        $db->method('fetchAll')->willReturn([]);

        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getDb')->willReturn($db);
    }

    public function testCreateReturnsFilesystemAdapterByDefault(): void
    {
        $cacheDir = sys_get_temp_dir() . '/pmf-cache-test-' . uniqid();

        $factory = new CacheFactory($this->configuration, $cacheDir);
        $cache = $factory->create();

        $this->assertInstanceOf(FilesystemAdapter::class, $cache);

        // Cleanup
        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
        }
    }

    public function testCreateReturnsFilesystemAdapterForInvalidAdapterValue(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $v): string => $v);
        $db->method('query')->willReturn('result');
        $db->method('fetchAll')->willReturn([
            (object) ['config_name' => 'storage.cacheAdapter', 'config_value' => 'memcached'],
        ]);

        $config = $this->createMock(Configuration::class);
        $config->method('getDb')->willReturn($db);

        $cacheDir = sys_get_temp_dir() . '/pmf-cache-test-' . uniqid();

        $factory = new CacheFactory($config, $cacheDir);
        $cache = $factory->create();

        // 'memcached' is not a valid adapter, should fall back to filesystem
        $this->assertInstanceOf(FilesystemAdapter::class, $cache);

        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
        }
    }

    public function testCreateThrowsRuntimeExceptionForRedisWithoutExtension(): void
    {
        if (extension_loaded('redis')) {
            $this->markTestSkipped('Test requires the redis extension to NOT be loaded.');
        }

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $v): string => $v);
        $db->method('query')->willReturn('result');
        $db->method('fetchAll')->willReturn([
            (object) ['config_name' => 'storage.cacheAdapter', 'config_value' => 'redis'],
        ]);

        $config = $this->createMock(Configuration::class);
        $config->method('getDb')->willReturn($db);

        $cacheDir = sys_get_temp_dir() . '/pmf-cache-test-' . uniqid();

        $factory = new CacheFactory($config, $cacheDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The "redis" PHP extension is required');
        $factory->create();
    }

    #[RequiresPhpExtension('redis')]
    public function testCreateReturnsRedisAdapterWhenExtensionLoaded(): void
    {
        if (!$this->isRedisServerAvailable('127.0.0.1', 6379)) {
            $this->markTestSkipped('Redis extension is loaded, but no Redis server is reachable on 127.0.0.1:6379.');
        }

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $v): string => $v);
        $db->method('query')->willReturn('result');
        $db->method('fetchAll')->willReturn([
            (object) ['config_name' => 'storage.cacheAdapter', 'config_value' => 'redis'],
            (object) ['config_name' => 'storage.cacheRedisDsn', 'config_value' => 'redis://127.0.0.1:6379'],
            (object) ['config_name' => 'storage.cacheRedisPrefix', 'config_value' => 'pmf_test_'],
            (object) ['config_name' => 'storage.cacheRedisConnectTimeout', 'config_value' => '2.0'],
            (object) ['config_name' => 'storage.cacheDefaultTtl', 'config_value' => '1800'],
        ]);

        $config = $this->createMock(Configuration::class);
        $config->method('getDb')->willReturn($db);

        $cacheDir = sys_get_temp_dir() . '/pmf-cache-test-' . uniqid();

        $factory = new CacheFactory($config, $cacheDir);
        $cache = $factory->create();

        $this->assertInstanceOf(RedisAdapter::class, $cache);
    }

    private function isRedisServerAvailable(string $host, int $port): bool
    {
        $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 0.2);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    private function removeDirectory(string $dir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($dir);
    }
}
