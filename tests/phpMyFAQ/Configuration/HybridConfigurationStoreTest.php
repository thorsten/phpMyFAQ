<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\FilesystemConfigurationCache;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class HybridConfigurationStoreTest extends TestCase
{
    private string $databaseFile;

    private Sqlite3 $databaseDriver;

    private DatabaseConfigurationStore $databaseConfigurationStore;

    /** @var string[] */
    private array $cacheDirs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = PMF_TEST_DIR . '/hybrid-config-store-' . uniqid('', true) . '.db';
        $this->databaseDriver = new Sqlite3();
        $this->databaseDriver->connect($this->databaseFile, '', '');

        Database::setTablePrefix('');
        $this->databaseDriver->query(
            'CREATE TABLE IF NOT EXISTS faqconfig (config_name VARCHAR(255) NOT NULL PRIMARY KEY, config_value TEXT)',
        );

        $this->databaseConfigurationStore = new DatabaseConfigurationStore($this->databaseDriver);
    }

    protected function tearDown(): void
    {
        @unlink($this->databaseFile);

        foreach ($this->cacheDirs as $cacheDir) {
            if (!is_dir($cacheDir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($cacheDir);
        }

        $this->cacheDirs = [];

        parent::tearDown();
    }

    public function testFetchAllFallsBackToDatabaseIfRedisIsUnavailable(): void
    {
        $this->databaseConfigurationStore->insert('main.language', 'en');
        $this->databaseConfigurationStore->insert('storage.useRedisForConfiguration', 'true');
        $this->databaseConfigurationStore->insert('storage.redisDsn', 'tcp://127.0.0.1:1?database=1');
        $this->databaseConfigurationStore->insert('storage.redisPrefix', 'pmf:config:');
        $this->databaseConfigurationStore->insert('storage.redisConnectTimeout', '0.1');

        $store = new HybridConfigurationStore(
            $this->databaseConfigurationStore,
            new ConfigurationStorageSettingsResolver($this->databaseConfigurationStore),
            new NullLogger(),
        );

        $rows = $store->fetchAll();
        $this->assertNotEmpty($rows);

        $configMap = [];
        foreach ($rows as $row) {
            $configMap[$row->config_name] = $row->config_value;
        }

        $this->assertSame('en', $configMap['main.language']);
    }

    public function testUpdateWritesToDatabaseWhenRedisIsUnavailable(): void
    {
        $this->databaseConfigurationStore->insert('main.language', 'en');
        $this->databaseConfigurationStore->insert('storage.useRedisForConfiguration', 'true');
        $this->databaseConfigurationStore->insert('storage.redisDsn', 'tcp://127.0.0.1:1?database=1');
        $this->databaseConfigurationStore->insert('storage.redisPrefix', 'pmf:config:');
        $this->databaseConfigurationStore->insert('storage.redisConnectTimeout', '0.1');

        $store = new HybridConfigurationStore(
            $this->databaseConfigurationStore,
            new ConfigurationStorageSettingsResolver($this->databaseConfigurationStore),
            new NullLogger(),
        );

        $this->assertTrue($store->updateConfigValue('main.language', 'de'));
        $this->assertSame('de', $this->databaseConfigurationStore->fetchValue('main.language'));
    }

    private function createStoreWithFilesystemCache(FilesystemConfigurationCache $cache): HybridConfigurationStore
    {
        return new HybridConfigurationStore(
            $this->databaseConfigurationStore,
            new ConfigurationStorageSettingsResolver($this->databaseConfigurationStore),
            new NullLogger(),
            $cache,
        );
    }

    private function createFilesystemCache(): FilesystemConfigurationCache
    {
        $cacheDir = PMF_TEST_DIR . '/hybrid-config-cache-' . uniqid('', true);
        $this->cacheDirs[] = $cacheDir;

        return new FilesystemConfigurationCache($cacheDir, 'test-tenant');
    }

    public function testFetchAllServesFromFilesystemCacheWithoutRedis(): void
    {
        $this->databaseConfigurationStore->insert('main.language', 'en');

        $store = $this->createStoreWithFilesystemCache($this->createFilesystemCache());

        $firstRows = $store->fetchAll();
        $this->assertNotEmpty($firstRows);

        // Bypass the store: a direct database change must not be visible while cached.
        $this->databaseDriver->query("UPDATE faqconfig SET config_value = 'de' WHERE config_name = 'main.language'");

        $configMap = [];
        foreach ($store->fetchAll() as $row) {
            $configMap[$row->config_name] = $row->config_value;
        }

        $this->assertSame('en', $configMap['main.language']);
    }

    public function testWritesThroughTheStoreInvalidateTheFilesystemCache(): void
    {
        $this->databaseConfigurationStore->insert('main.language', 'en');

        $store = $this->createStoreWithFilesystemCache($this->createFilesystemCache());
        $store->fetchAll();

        $this->assertTrue($store->updateConfigValue('main.language', 'de'));

        $configMap = [];
        foreach ($store->fetchAll() as $row) {
            $configMap[$row->config_name] = $row->config_value;
        }

        $this->assertSame('de', $configMap['main.language']);
    }

    public function testInsertAndDeleteInvalidateTheFilesystemCache(): void
    {
        $this->databaseConfigurationStore->insert('main.language', 'en');

        $store = $this->createStoreWithFilesystemCache($this->createFilesystemCache());
        $store->fetchAll();

        $this->assertTrue($store->insert('main.titleFAQ', 'phpMyFAQ'));
        $names = array_map(static fn(object $row): string => $row->config_name, $store->fetchAll());
        $this->assertContains('main.titleFAQ', $names);

        $this->assertTrue($store->delete('main.titleFAQ'));
        $names = array_map(static fn(object $row): string => $row->config_name, $store->fetchAll());
        $this->assertNotContains('main.titleFAQ', $names);
    }
}
