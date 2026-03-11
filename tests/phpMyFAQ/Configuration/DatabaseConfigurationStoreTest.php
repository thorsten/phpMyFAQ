<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseConfigurationStore::class)]
#[UsesClass(Database::class)]
#[UsesClass(Sqlite3::class)]
class DatabaseConfigurationStoreTest extends TestCase
{
    private string $databaseFile;

    private Sqlite3 $databaseDriver;

    private DatabaseConfigurationStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = PMF_TEST_DIR . '/database-config-store-' . uniqid('', true) . '.db';
        $this->databaseDriver = new Sqlite3();
        $this->databaseDriver->connect($this->databaseFile, '', '');

        Database::setTablePrefix('');
        $this->databaseDriver->query(
            'CREATE TABLE faqconfig (config_name VARCHAR(255) NOT NULL PRIMARY KEY, config_value TEXT)',
        );

        $this->store = new DatabaseConfigurationStore($this->databaseDriver);
    }

    protected function tearDown(): void
    {
        @unlink($this->databaseFile);
        parent::tearDown();
    }

    public function testFetchAllReturnsEmptyArrayForEmptyTable(): void
    {
        $this->assertSame([], $this->store->fetchAll());
    }

    public function testInsertFetchUpdateRenameAndDeleteWorkWithTrimmedValues(): void
    {
        $this->assertTrue($this->store->insert(' main.language ', ' en '));
        $this->assertSame('en', $this->store->fetchValue('main.language'));

        $this->assertTrue($this->store->updateConfigValue(' main.language ', ' de '));
        $this->assertSame('de', $this->store->fetchValue('main.language'));

        $this->assertTrue($this->store->renameKey(' main.language ', ' main.defaultLanguage '));
        $this->assertNull($this->store->fetchValue('main.language'));
        $this->assertSame('de', $this->store->fetchValue('main.defaultLanguage'));

        $rows = $this->store->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertSame('main.defaultLanguage', $rows[0]->config_name);
        $this->assertSame('de', $rows[0]->config_value);

        $this->assertTrue($this->store->delete(' main.defaultLanguage '));
        $this->assertNull($this->store->fetchValue('main.defaultLanguage'));
        $this->assertSame([], $this->store->fetchAll());
    }

    public function testFetchValueReturnsNullForMissingKey(): void
    {
        $this->assertNull($this->store->fetchValue('does.not.exist'));
    }
}
