<?php

declare(strict_types=1);

namespace phpMyFAQ\News\Test;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\NewsMessage;
use phpMyFAQ\News\NewsRepository;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class NewsRepositoryTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        Strings::init();
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-news-repository-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);
        $this->configuration = new Configuration($this->dbHandle);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
        }

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testInsertAndFetchById(): void
    {
        $repo = new NewsRepository($this->configuration);

        $news = new NewsMessage()
            ->setId(0)
            ->setLanguage('en')
            ->setHeader('Unit Test Header')
            ->setMessage('Unit Test Message')
            ->setCreated(new DateTime('2025-01-01T12:00:00Z'))
            ->setAuthor('Tester')
            ->setEmail('tester@example.com')
            ->setActive(true)
            ->setComment(true)
            ->setLink('')
            ->setLinkTitle('')
            ->setLinkTarget('');

        $this->assertTrue($repo->insert($news));

        // fetch the latest and take first id
        $rows = iterator_to_array($repo->getLatest('en', active: true, limit: 1));
        $this->assertNotEmpty($rows);
        $row = $rows[0];
        $this->assertSame('Unit Test Header', $row->header);

        $fetched = $repo->getById((int) $row->id, 'en');
        $this->assertNotNull($fetched);
        $this->assertSame('Unit Test Message', $fetched->artikel);
    }

    public function testActivateAndDelete(): void
    {
        $repo = new NewsRepository($this->configuration);
        $rows = iterator_to_array($repo->getLatest('en', active: true, limit: 1));
        if (empty($rows)) {
            $this->markTestSkipped('No news to activate/delete.');
        }
        $id = (int) $rows[0]->id;

        $this->assertTrue($repo->activate($id, false));
        $row = $repo->getById($id, 'en');
        $this->assertSame('n', $row->active);

        $this->assertTrue($repo->delete($id, 'en'));
        $this->assertNull($repo->getById($id, 'en'));
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }
}
