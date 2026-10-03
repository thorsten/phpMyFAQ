<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(FilesystemConfigurationCache::class)]
final class FilesystemConfigurationCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/pmf-config-cache-' . bin2hex(random_bytes(6));
        mkdir($this->cacheDir, 0777, true);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->cacheDir);
    }

    public function testReadReturnsNullOnColdCache(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'identity');

        $this->assertNull($cache->read());
    }

    public function testWarmThenReadRoundTripsRowsAsObjects(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'identity');
        $cache->warm([
            (object) ['config_name' => 'main.title', 'config_value' => 'phpMyFAQ'],
            (object) ['config_name' => 'main.count', 'config_value' => 3],
            (object) ['config_name' => 'main.empty', 'config_value' => null],
        ]);

        $rows = $cache->read();

        $this->assertNotNull($rows);
        $this->assertCount(3, $rows);
        $this->assertSame('main.title', $rows[0]->config_name);
        $this->assertSame('phpMyFAQ', $rows[0]->config_value);
        $this->assertSame('3', $rows[1]->config_value);
        $this->assertSame('', $rows[2]->config_value);
    }

    public function testWarmSkipsRowsWithoutName(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'identity');
        $cache->warm([
            (object) ['config_value' => 'orphan'],
            (object) ['config_name' => null, 'config_value' => 'orphan'],
            (object) ['config_name' => 'kept', 'config_value' => 'v'],
        ]);

        $rows = $cache->read();

        $this->assertNotNull($rows);
        $this->assertCount(1, $rows);
        $this->assertSame('kept', $rows[0]->config_name);
    }

    public function testWarmWithOnlyInvalidRowsStoresNothing(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'identity');
        $cache->warm([(object) ['config_value' => 'orphan']]);

        $this->assertNull($cache->read());
    }

    public function testWarmWithEmptyRowsStoresNothing(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'identity');
        $cache->warm([]);

        $this->assertNull($cache->read());
    }

    public function testClearInvalidatesCachedRows(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'identity');
        $cache->warm([(object) ['config_name' => 'a', 'config_value' => '1']]);
        $this->assertNotNull($cache->read());

        $cache->clear();

        $this->assertNull($cache->read());
    }

    public function testDifferentIdentitiesDoNotShareEntries(): void
    {
        $first = new FilesystemConfigurationCache($this->cacheDir, 'tenant-a');
        $second = new FilesystemConfigurationCache($this->cacheDir, 'tenant-b');

        $first->warm([(object) ['config_name' => 'a', 'config_value' => '1']]);

        $this->assertNotNull($first->read());
        $this->assertNull($second->read());
    }

    public function testSameIdentitySharesEntriesAcrossInstances(): void
    {
        new FilesystemConfigurationCache($this->cacheDir, 'shared')->warm([
            (object) ['config_name' => 'a', 'config_value' => '1'],
        ]);

        $rows = new FilesystemConfigurationCache($this->cacheDir, 'shared')->read();

        $this->assertNotNull($rows);
        $this->assertSame('a', $rows[0]->config_name);
    }

    public function testExpiredEntriesAreMisses(): void
    {
        $cache = new FilesystemConfigurationCache($this->cacheDir, 'identity', ttl: 1);
        $cache->warm([(object) ['config_name' => 'a', 'config_value' => '1']]);

        sleep(2);

        $this->assertNull($cache->read());
    }

    public function testCreateIfEnabledReturnsNullInDebugMode(): void
    {
        $this->assertNull(FilesystemConfigurationCache::createIfEnabled(true, 'true', $this->cacheDir, 'id'));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function disabledValueProvider(): iterable
    {
        yield 'false string' => ['false'];
        yield 'zero' => ['0'];
        yield 'off' => ['off'];
        yield 'no' => ['no'];
        yield 'bool false' => [false];
        yield 'empty string' => [''];
    }

    #[DataProvider('disabledValueProvider')]
    public function testCreateIfEnabledReturnsNullForFalsyFlag(mixed $enabled): void
    {
        $this->assertNull(FilesystemConfigurationCache::createIfEnabled(false, $enabled, $this->cacheDir, 'id'));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function enabledValueProvider(): iterable
    {
        yield 'true string' => ['true'];
        yield 'one' => ['1'];
        yield 'yes' => ['yes'];
        yield 'bool true' => [true];
        yield 'unset defaults to enabled' => [null];
    }

    #[DataProvider('enabledValueProvider')]
    public function testCreateIfEnabledReturnsInstanceForTruthyFlag(mixed $enabled): void
    {
        $this->assertInstanceOf(
            FilesystemConfigurationCache::class,
            FilesystemConfigurationCache::createIfEnabled(false, $enabled, $this->cacheDir, 'id'),
        );
    }
}
