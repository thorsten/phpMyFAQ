<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(HybridConfigurationStore::class)]
#[UsesClass(ConfigurationStorageSettings::class)]
#[UsesClass(FilesystemConfigurationCache::class)]
#[UsesClass(RedisConfigurationStore::class)]
final class HybridConfigurationStoreTest extends TestCase
{
    private DatabaseConfigurationStore&MockObject $databaseStore;

    private LoggerInterface&MockObject $logger;

    private string $cacheDir;

    protected function setUp(): void
    {
        $this->databaseStore = $this->createMock(DatabaseConfigurationStore::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cacheDir = sys_get_temp_dir() . '/pmf-hybrid-store-' . bin2hex(random_bytes(6));
        mkdir($this->cacheDir, 0777, true);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->cacheDir);
    }

    private function createStore(
        bool $redisEnabled = false,
        ?FilesystemConfigurationCache $cache = null,
    ): HybridConfigurationStore {
        // An unreachable loopback port makes every Redis call fail fast with a RuntimeException.
        $settings = new ConfigurationStorageSettings($redisEnabled, 'tcp://127.0.0.1:1', 'pmf:test:', 0.1);

        $resolver = $this->createStub(ConfigurationStorageSettingsResolver::class);
        $resolver->method('resolve')->willReturn($settings);

        return new HybridConfigurationStore($this->databaseStore, $resolver, $this->logger, $cache);
    }

    private function createCache(): FilesystemConfigurationCache
    {
        return new FilesystemConfigurationCache($this->cacheDir, 'test');
    }

    public function testUpdateConfigValueDelegatesToDatabaseAndClearsCache(): void
    {
        $cache = $this->createCache();
        $cache->warm([(object) ['config_name' => 'a', 'config_value' => '1']]);

        $this->databaseStore->expects($this->once())->method('updateConfigValue')->with('a', '2')->willReturn(true);
        $this->logger->expects($this->never())->method('warning');

        $this->assertTrue($this->createStore(cache: $cache)->updateConfigValue('a', '2'));
        $this->assertNull($cache->read());
    }

    public function testUpdateConfigValueKeepsCacheWhenDatabaseUpdateFails(): void
    {
        $cache = $this->createCache();
        $cache->warm([(object) ['config_name' => 'a', 'config_value' => '1']]);

        $this->databaseStore->method('updateConfigValue')->willReturn(false);

        $this->assertFalse($this->createStore(cache: $cache)->updateConfigValue('a', '2'));
        $this->assertNotNull($cache->read());
    }

    public function testInsertDelegatesToDatabaseAndClearsCache(): void
    {
        $cache = $this->createCache();
        $cache->warm([(object) ['config_name' => 'a', 'config_value' => '1']]);

        $this->databaseStore->expects($this->once())->method('insert')->with('b', '2')->willReturn(true);

        $this->assertTrue($this->createStore(cache: $cache)->insert('b', '2'));
        $this->assertNull($cache->read());
    }

    public function testInsertReportsDatabaseFailure(): void
    {
        $this->databaseStore->method('insert')->willReturn(false);

        $this->assertFalse($this->createStore()->insert('b', '2'));
    }

    public function testDeleteDelegatesToDatabaseAndClearsCache(): void
    {
        $cache = $this->createCache();
        $cache->warm([(object) ['config_name' => 'a', 'config_value' => '1']]);

        $this->databaseStore->expects($this->once())->method('delete')->with('a')->willReturn(true);

        $this->assertTrue($this->createStore(cache: $cache)->delete('a'));
        $this->assertNull($cache->read());
    }

    public function testDeleteReportsDatabaseFailure(): void
    {
        $this->databaseStore->method('delete')->willReturn(false);

        $this->assertFalse($this->createStore()->delete('a'));
    }

    public function testRenameKeyDelegatesToDatabaseAndClearsCache(): void
    {
        $cache = $this->createCache();
        $cache->warm([(object) ['config_name' => 'a', 'config_value' => '1']]);

        $this->databaseStore->expects($this->once())->method('renameKey')->with('a', 'b')->willReturn(true);

        $this->assertTrue($this->createStore(cache: $cache)->renameKey('a', 'b'));
        $this->assertNull($cache->read());
    }

    public function testRenameKeyReportsDatabaseFailure(): void
    {
        $this->databaseStore->method('renameKey')->willReturn(false);

        $this->assertFalse($this->createStore()->renameKey('a', 'b'));
    }

    public function testFetchAllReturnsFilesystemCacheHitWithoutTouchingDatabase(): void
    {
        $cache = $this->createCache();
        $cache->warm([(object) ['config_name' => 'cached', 'config_value' => 'yes']]);

        $this->databaseStore->expects($this->never())->method('fetchAll');

        $rows = $this->createStore(cache: $cache)->fetchAll();

        $this->assertCount(1, $rows);
        $this->assertSame('cached', $rows[0]->config_name);
    }

    public function testFetchAllFallsBackToDatabaseAndWarmsFilesystemCache(): void
    {
        $cache = $this->createCache();
        $databaseRows = [(object) ['config_name' => 'from.db', 'config_value' => '1']];

        $this->databaseStore->expects($this->once())->method('fetchAll')->willReturn($databaseRows);

        $this->assertSame($databaseRows, $this->createStore(cache: $cache)->fetchAll());

        $cached = $cache->read();
        $this->assertNotNull($cached);
        $this->assertSame('from.db', $cached[0]->config_name);
    }

    public function testFetchAllWorksWithoutFilesystemCache(): void
    {
        $databaseRows = [(object) ['config_name' => 'from.db', 'config_value' => '1']];
        $this->databaseStore->method('fetchAll')->willReturn($databaseRows);

        $this->assertSame($databaseRows, $this->createStore()->fetchAll());
    }

    public function testFetchAllLogsWarningsAndFallsBackToDatabaseWhenRedisIsUnreachable(): void
    {
        $this->requireRedisExtension();

        $databaseRows = [(object) ['config_name' => 'from.db', 'config_value' => '1']];
        $this->databaseStore->method('fetchAll')->willReturn($databaseRows);

        // One warning for the failed Redis read, one for the failed warm-up from the database rows.
        $this->logger->expects($this->exactly(2))->method('warning')->with(
            $this->logicalOr(
                'Failed to fetch configuration from Redis storage.',
                'Failed to warm Redis configuration storage from database.',
            ),
            $this->arrayHasKey('error'),
        );

        $this->assertSame($databaseRows, $this->createStore(redisEnabled: true)->fetchAll());
    }

    public function testWriteOperationsSucceedAndLogWhenRedisIsUnreachable(): void
    {
        $this->requireRedisExtension();

        $this->databaseStore->method('updateConfigValue')->willReturn(true);
        $this->databaseStore->method('insert')->willReturn(true);
        $this->databaseStore->method('delete')->willReturn(true);
        $this->databaseStore->method('renameKey')->willReturn(true);

        $this->logger->expects($this->exactly(4))->method('warning')->with(
            $this->logicalOr(
                'Failed to update configuration key in Redis storage.',
                'Failed to insert configuration key into Redis storage.',
                'Failed to delete configuration key from Redis storage.',
                'Failed to rename configuration key in Redis storage.',
            ),
            $this->arrayHasKey('error'),
        );

        $store = $this->createStore(redisEnabled: true);

        $this->assertTrue($store->updateConfigValue('a', '1'));
        $this->assertTrue($store->insert('b', '2'));
        $this->assertTrue($store->delete('a'));
        $this->assertTrue($store->renameKey('b', 'c'));
    }

    private function requireRedisExtension(): void
    {
        // Fully qualified on purpose: RedisConfigurationStoreTest installs a shim named
        // phpMyFAQ\Configuration\Storage\extension_loaded() that an unqualified call would hit.
        if (!\extension_loaded('redis')) {
            $this->markTestSkipped('The redis extension is required for the Redis fallback tests.');
        }
    }
}
