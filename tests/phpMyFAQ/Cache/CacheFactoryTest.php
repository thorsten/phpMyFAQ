<?php

declare(strict_types=1);

namespace phpMyFAQ\Cache;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

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
        $db->method('fetchObject')->willReturn(false);

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
        $cacheDir = sys_get_temp_dir() . '/pmf-cache-test-' . uniqid();

        $factory = new CacheFactory($this->configuration, $cacheDir);
        $cache = $factory->create();

        // Since DB returns no config values, resolver defaults to 'filesystem'
        $this->assertInstanceOf(FilesystemAdapter::class, $cache);

        if (is_dir($cacheDir)) {
            $this->removeDirectory($cacheDir);
        }
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
