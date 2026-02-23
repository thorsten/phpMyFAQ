<?php

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class ConfigurationStorageSettingsResolverTest extends TestCase
{
    private string $databaseFile;

    private Sqlite3 $databaseDriver;

    private DatabaseConfigurationStore $databaseConfigurationStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = PMF_TEST_DIR . '/config-storage-settings-' . uniqid('', true) . '.db';
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

    public function testResolveReturnsDefaultsIfKeysAreMissing(): void
    {
        $resolver = new ConfigurationStorageSettingsResolver($this->databaseConfigurationStore);
        $settings = $resolver->resolve();

        $this->assertFalse($settings->enabled);
        $this->assertSame('tcp://redis:6379?database=1', $settings->redisDsn);
        $this->assertSame('pmf:config:', $settings->redisPrefix);
        $this->assertSame(1.0, $settings->connectTimeout);
    }

    public function testResolveReturnsConfiguredValues(): void
    {
        $this->databaseConfigurationStore->insert('storage.useRedisForConfiguration', 'true');
        $this->databaseConfigurationStore->insert('storage.redisDsn', 'tcp://127.0.0.1:6380?database=5');
        $this->databaseConfigurationStore->insert('storage.redisPrefix', 'custom:cfg:');
        $this->databaseConfigurationStore->insert('storage.redisConnectTimeout', '2.5');

        $resolver = new ConfigurationStorageSettingsResolver($this->databaseConfigurationStore);
        $settings = $resolver->resolve();

        $this->assertTrue($settings->enabled);
        $this->assertSame('tcp://127.0.0.1:6380?database=5', $settings->redisDsn);
        $this->assertSame('custom:cfg:', $settings->redisPrefix);
        $this->assertSame(2.5, $settings->connectTimeout);
    }
}
