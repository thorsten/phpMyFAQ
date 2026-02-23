<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
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
}
