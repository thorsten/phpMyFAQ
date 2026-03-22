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
    private mixed $previousDatabaseDriver = null;
    private mixed $previousDbType = null;
    private ?string $previousTablePrefix = null;

    protected function setUp(): void
    {
        Strings::init();
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $this->previousDatabaseDriver = $databaseDriverProperty->getValue();
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $this->previousDbType = $dbTypeProperty->getValue();
        $this->previousTablePrefix = Database::getTablePrefix();

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
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->previousDatabaseDriver);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, $this->previousDbType);
        Database::setTablePrefix($this->previousTablePrefix ?? '');

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
        $this->assertTrue($repo->insert($this->createNewsMessage(
            'Activation Target',
            '2025-01-08T12:00:00Z',
            active: true,
        )));
        $rows = array_values(iterator_to_array($repo->getLatest('en', active: true, limit: 1)));
        $this->assertNotEmpty($rows);
        $id = (int) $rows[0]->id;

        $this->assertTrue($repo->activate($id, false));
        $row = $repo->getById($id, 'en');
        $this->assertSame('n', $row->active);

        $this->assertTrue($repo->delete($id, 'en'));
        $this->assertNull($repo->getById($id, 'en'));
    }

    public function testGetLatestPaginatedReturnsSortedRowsAndFallsBackForInvalidSortField(): void
    {
        $repo = new NewsRepository($this->configuration);

        $firstNews = $this->createNewsMessage('Paginated A', '2025-01-01T12:00:00Z', active: true);
        $secondNews = $this->createNewsMessage('Paginated B', '2025-01-02T12:00:00Z', active: false);
        $thirdNews = $this->createNewsMessage('Paginated C', '2025-01-03T12:00:00Z', active: true);

        $this->assertTrue($repo->insert($firstNews));
        $this->assertTrue($repo->insert($secondNews));
        $this->assertTrue($repo->insert($thirdNews));

        $activeRows = array_values(iterator_to_array($repo->getLatestPaginated('en', true, 10, 0, 'datum', 'ASC')));
        $activeHeaders = array_map(static fn(object $row): string => $row->header, $activeRows);

        $this->assertContains('Paginated A', $activeHeaders);
        $this->assertContains('Paginated C', $activeHeaders);
        $this->assertNotContains('Paginated B', $activeHeaders);

        $fallbackRows = array_values(iterator_to_array($repo->getLatestPaginated(
            'en',
            false,
            1,
            0,
            'invalid-sort-field',
            'DESC',
        )));

        $this->assertCount(1, $fallbackRows);
        $this->assertSame('Paginated C', $fallbackRows[0]->header);
    }

    public function testCountLatestAndGetHeadersIncludeActiveFilter(): void
    {
        $repo = new NewsRepository($this->configuration);

        $this->assertTrue($repo->insert($this->createNewsMessage(
            'Header Active',
            '2025-01-04T12:00:00Z',
            active: true,
        )));
        $this->assertTrue($repo->insert($this->createNewsMessage(
            'Header Inactive',
            '2025-01-05T12:00:00Z',
            active: false,
        )));

        $activeCount = $repo->countLatest('en', true);
        $allCount = $repo->countLatest('en', false);

        $this->assertGreaterThanOrEqual($activeCount, $allCount - 1);
        $this->assertGreaterThan(0, $activeCount);

        $headers = array_values(iterator_to_array($repo->getHeaders('en')));
        $headerTitles = array_map(static fn(object $row): string => $row->header, $headers);

        $this->assertContains('Header Active', $headerTitles);
        $this->assertContains('Header Inactive', $headerTitles);
    }

    public function testUpdatePersistsChangedFields(): void
    {
        $repo = new NewsRepository($this->configuration);

        $original = $this->createNewsMessage('Before Update', '2025-01-06T12:00:00Z', active: true);
        $this->assertTrue($repo->insert($original));

        $rows = array_values(iterator_to_array($repo->getLatest('en', true, 1)));
        $this->assertNotEmpty($rows);
        $newsId = (int) $rows[0]->id;

        $updated = $this
            ->createNewsMessage('After Update', '2025-01-07T12:00:00Z', active: false)
            ->setId($newsId)
            ->setMessage('Updated Message')
            ->setComment(false)
            ->setLink('https://example.org/news')
            ->setLinkTitle('Read more')
            ->setLinkTarget('_blank');

        $this->assertTrue($repo->update($updated));

        $fetched = $repo->getById($newsId, 'en');
        $this->assertNotNull($fetched);
        $this->assertSame('After Update', $fetched->header);
        $this->assertSame('Updated Message', $fetched->artikel);
        $this->assertSame('n', $fetched->active);
        $this->assertSame('n', $fetched->comment);
        $this->assertSame('https://example.org/news', $fetched->link);
        $this->assertSame('Read more', $fetched->linktitel);
        $this->assertSame('_blank', $fetched->target);
    }

    public function testGetByIdReturnsNullForUnknownId(): void
    {
        $repo = new NewsRepository($this->configuration);

        $this->assertNull($repo->getById(999999, 'en'));
    }

    private function createNewsMessage(string $header, string $created, bool $active): NewsMessage
    {
        return new NewsMessage()
            ->setId(0)
            ->setLanguage('en')
            ->setHeader($header)
            ->setMessage($header . ' message')
            ->setCreated(new DateTime($created))
            ->setAuthor('Tester')
            ->setEmail('tester@example.com')
            ->setActive($active)
            ->setComment(true)
            ->setLink('')
            ->setLinkTitle('')
            ->setLinkTarget('');
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
