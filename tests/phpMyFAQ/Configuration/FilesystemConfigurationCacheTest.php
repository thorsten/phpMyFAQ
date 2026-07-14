<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use FilesystemIterator;
use phpMyFAQ\Configuration\Storage\FilesystemConfigurationCache;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FilesystemConfigurationCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = PMF_TEST_DIR . '/config-cache-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($this->cacheDir);
        }

        parent::tearDown();
    }

    public function testReadReturnsNullWhenNothingIsCached(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'tenant-a');

        $this->assertNull($cache->read());
    }

    public function testWarmedRowsCanBeReadBack(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'tenant-a');

        $cache->warm([
            (object) ['config_name' => 'main.language', 'config_value' => 'en'],
            (object) ['config_name' => 'main.titleFAQ', 'config_value' => 'phpMyFAQ'],
        ]);

        $rows = $cache->read();

        $this->assertNotNull($rows);
        $this->assertCount(2, $rows);
        $this->assertSame('main.language', $rows[0]->config_name);
        $this->assertSame('en', $rows[0]->config_value);
    }

    public function testClearRemovesCachedRows(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'tenant-a');
        $cache->warm([(object) ['config_name' => 'main.language', 'config_value' => 'en']]);

        $cache->clear();

        $this->assertNull($cache->read());
    }

    public function testTenantsAreIsolatedByIdentity(): void
    {
        $cacheA = new FilesystemConfigurationCache($this->cacheDir, 'tenant-a');
        $cacheB = new FilesystemConfigurationCache($this->cacheDir, 'tenant-b');

        $cacheA->warm([(object) ['config_name' => 'main.language', 'config_value' => 'en']]);

        $this->assertNotNull($cacheA->read());
        $this->assertNull($cacheB->read());
    }

    public function testCreateIfEnabledReturnsNullInDebugMode(): void
    {
        $this->assertNull(
            FilesystemConfigurationCache::createIfEnabled(true, 'true', $this->cacheDir, 'tenant-a'),
        );
    }

    public function testCreateIfEnabledReturnsNullWhenDisabledByEnvironment(): void
    {
        $this->assertNull(
            FilesystemConfigurationCache::createIfEnabled(false, 'false', $this->cacheDir, 'tenant-a'),
        );
    }

    public function testCreateIfEnabledDefaultsToEnabled(): void
    {
        $this->assertInstanceOf(
            FilesystemConfigurationCache::class,
            FilesystemConfigurationCache::createIfEnabled(false, null, $this->cacheDir, 'tenant-a'),
        );
    }
}
