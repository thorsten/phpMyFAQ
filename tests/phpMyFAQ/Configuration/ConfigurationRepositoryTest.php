<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use Monolog\Logger;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ConfigurationRepository::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\HybridConfigurationStore::class)]
#[UsesClass(Database::class)]
#[UsesClass(Sqlite3::class)]
class ConfigurationRepositoryTest extends TestCase
{
    private string $databaseFile;

    private Sqlite3 $databaseDriver;

    private ConfigurationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = PMF_TEST_DIR . '/configuration-repository-' . uniqid('', true) . '.db';
        $this->databaseDriver = new Sqlite3();
        $this->databaseDriver->connect($this->databaseFile, '', '');

        Database::setTablePrefix('');
        $this->databaseDriver->query(
            'CREATE TABLE faqconfig (config_name VARCHAR(255) NOT NULL PRIMARY KEY, config_value TEXT)',
        );
        $this->databaseDriver->query(
            'CREATE TABLE faqdata (id INTEGER NOT NULL, lang VARCHAR(10) NOT NULL, content TEXT)',
        );

        $configuration = $this->createMock(\phpMyFAQ\Configuration::class);
        $configuration->method('getDb')->willReturn($this->databaseDriver);
        $configuration->method('getLogger')->willReturn(new Logger('test'));

        $this->repository = new ConfigurationRepository($configuration);
    }

    protected function tearDown(): void
    {
        @unlink($this->databaseFile);
        parent::tearDown();
    }

    public function testCrudMethodsDelegateToHybridStore(): void
    {
        $this->assertTrue($this->repository->insert('main.language', 'en'));
        $this->assertTrue($this->repository->updateConfigValue('main.language', 'de'));
        $this->assertTrue($this->repository->renameKey('main.language', 'main.defaultLanguage'));

        $rows = $this->repository->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertSame('main.defaultLanguage', $rows[0]->config_name);
        $this->assertSame('de', $rows[0]->config_value);

        $this->assertTrue($this->repository->delete('main.defaultLanguage'));
        $this->assertSame([], $this->repository->fetchAll());
    }

    public function testGetFaqDataContentsReturnsRowsFromFaqDataTable(): void
    {
        $this->databaseDriver->query(
            "INSERT INTO faqdata (id, lang, content) VALUES (1, 'en', 'First'), (2, 'de', 'Zweite')",
        );

        $rows = $this->repository->getFaqDataContents();

        $this->assertCount(2, $rows);
        $this->assertSame(1, (int) $rows[0]->id);
        $this->assertSame('en', $rows[0]->lang);
        $this->assertSame('First', $rows[0]->content);
        $this->assertSame('de', $rows[1]->lang);
    }

    public function testUpdateFaqDataContentByIdEscapesAndUpdatesMatchingRow(): void
    {
        $this->databaseDriver->query(
            "INSERT INTO faqdata (id, lang, content) VALUES (10, 'en', 'Old'), (10, 'de', 'Alt')",
        );

        $this->assertTrue($this->repository->updateFaqDataContentById(10, 'en', "Updated 'Content'"));

        $rows = $this->repository->getFaqDataContents();
        $contentByLanguage = [];
        foreach ($rows as $row) {
            $contentByLanguage[$row->lang] = $row->content;
        }

        $this->assertSame("Updated 'Content'", $contentByLanguage['en']);
        $this->assertSame('Alt', $contentByLanguage['de']);
    }
}
