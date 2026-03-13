<?php

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
class SearchTest extends TestCase
{
    private Configuration $configuration;
    private Search $search;
    private Sqlite3 $dbHandle;

    /**
     * @throws PluginException|Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();
        Language::$language = 'en';

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->setDatabaseType('sqlite3');
        $this->configuration = new Configuration($this->dbHandle);
        $this->configuration->setLanguage(
            new Language($this->configuration, $this->createMock(SessionInterface::class)),
        );
        $this->search = new Search($this->configuration);

        // Clean up any existing search terms to ensure test isolation
        $this->search->deleteAllSearchTerms();
    }

    protected function tearDown(): void
    {
        $this->search->deleteAllSearchTerms();
    }

    /**
     * Helper method to set configuration values for testing using reflection
     */
    private function setConfigValue(string $key, mixed $value): void
    {
        $reflection = new ReflectionClass($this->configuration);
        $property = $reflection->getProperty('config');
        $config = $property->getValue($this->configuration);
        $config[$key] = $value;
        $property->setValue($this->configuration, $config);
    }

    private function invokePrivateMethod(object $object, string $method, mixed ...$arguments): mixed
    {
        $reflectionMethod = new ReflectionMethod($object, $method);

        return $reflectionMethod->invoke($object, ...$arguments);
    }

    private function setDatabaseType(string $databaseType): void
    {
        $reflectionProperty = new ReflectionProperty(Database::class, 'dbType');
        $reflectionProperty->setValue(null, $databaseType);
    }

    public function testSetCategoryId(): void
    {
        $this->search->setCategoryId(1);
        $this->assertEquals(1, $this->search->getCategoryId());
    }

    public function testGetCategoryId(): void
    {
        $this->search->setCategoryId(1);
        $this->assertEquals(1, $this->search->getCategoryId());
    }

    /**
     * @throws Exception
     */
    public function testSearchWithNumericTermWhenSolutionIdSearchEnabled(): void
    {
        $this->setConfigValue('search.searchForSolutionId', 'true');

        $this->search = $this
            ->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchDatabase'])
            ->getMock();

        $this->search
            ->expects($this->once())
            ->method('searchDatabase')
            ->with('123', true)
            ->willReturn([]);

        $this->assertEquals([], $this->search->search('123'));
    }

    /**
     * @throws Exception
     * When solution ID search is disabled and no search engines are enabled,
     * numeric searches should fall through to searchDatabase() which will
     * perform a full-text search (not a solution ID search). The database
     * search classes check the configuration internally to determine the
     * search type. The second parameter (true) indicates search across all
     * languages, not the search type.
     */
    public function testSearchWithNumericTermWhenSolutionIdSearchDisabled(): void
    {
        $this->setConfigValue('search.searchForSolutionId', 'false');
        $this->setConfigValue('search.enableElasticsearch', 'false');
        $this->setConfigValue('search.enableOpenSearch', 'false');

        $this->search = $this
            ->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchDatabase'])
            ->getMock();

        $this->search
            ->expects($this->once())
            ->method('searchDatabase')
            ->with('123', true)
            ->willReturn([]);

        $this->assertEquals([], $this->search->search('123'));
    }

    /**
     * @throws Exception
     */
    public function testSearchWithNumericTermWhenElasticsearchEnabledAndSolutionIdSearchDisabled(): void
    {
        $this->setConfigValue('search.searchForSolutionId', 'false');
        $this->setConfigValue('search.enableElasticsearch', 'true');

        $this->search = $this
            ->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchElasticsearch'])
            ->getMock();

        $this->search
            ->expects($this->once())
            ->method('searchElasticsearch')
            ->with('123', true)
            ->willReturn([]);

        $this->assertEquals([], $this->search->search('123'));
    }

    /**
     * @throws Exception
     */
    public function testSearchWithNumericTermWhenOpenSearchEnabledAndSolutionIdSearchDisabled(): void
    {
        $this->setConfigValue('search.searchForSolutionId', 'false');
        $this->setConfigValue('search.enableElasticsearch', 'false');
        $this->setConfigValue('search.enableOpenSearch', 'true');

        $this->search = $this
            ->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchOpenSearch'])
            ->getMock();

        $this->search
            ->expects($this->once())
            ->method('searchOpenSearch')
            ->with('123', true)
            ->willReturn([]);

        $this->assertEquals([], $this->search->search('123'));
    }

    /**
     * @throws Exception
     */
    public function testSearchWithNonNumericTerm(): void
    {
        $this->search = $this
            ->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchDatabase'])
            ->getMock();

        $this->search
            ->expects($this->once())
            ->method('searchDatabase')
            ->with('test', true)
            ->willReturn([]);

        $this->assertEquals([], $this->search->search('test'));
    }

    public function testDeleteSearchTermById(): void
    {
        $this->dbHandle->query("INSERT INTO faqsearches VALUES (1, 'en', 'foo', ''), (2, 'en', 'bar', '')");

        $result = $this->search->deleteSearchTermById(1);

        $this->assertTrue($result);
        $this->assertEquals(1, $this->search->getSearchesCount());
    }

    public function testDeleteAllSearchTermsSuccess(): void
    {
        $this->dbHandle->query("INSERT INTO faqsearches VALUES (1, 'en', 'foo', ''), (2, 'en', 'bar', '')");

        $this->assertTrue($this->search->deleteAllSearchTerms());
    }

    public function testGetMostPopularSearches(): void
    {
        $this->dbHandle->query(
            "INSERT INTO faqsearches VALUES (1, 'en', 'foo', '2023-01-01 12:00:00'), (2, 'en', 'bar', '2023-01-01 12:00:00'), (3, 'en', 'foo', '2023-01-01 12:00:00')",
        );

        $actualSearches = $this->search->getMostPopularSearches(2);

        $this->assertEquals(2, count($actualSearches));
        $this->assertEquals('foo', $actualSearches[0]['searchterm']);
        $this->assertEquals(2, $actualSearches[0]['number']);
    }

    public function testGetMostPopularSearchesWithLimit(): void
    {
        $this->dbHandle->query(
            "INSERT INTO faqsearches VALUES (1, 'en', 'foo', '2023-01-01 12:00:00'), (2, 'en', 'bar', '2023-01-01 12:00:00'), (3, 'en', 'baz', '2023-01-01 12:00:00'), (4, 'en', 'foo', '2023-01-01 12:00:00')",
        );

        $actualSearches = $this->search->getMostPopularSearches(1);

        $this->assertEquals(1, count($actualSearches));
        $this->assertEquals('foo', $actualSearches[0]['searchterm']);
        $this->assertEquals(2, $actualSearches[0]['number']);
    }

    public function testGetMostPopularSearchesBackwardCompatibility(): void
    {
        $this->dbHandle->query(
            "INSERT INTO faqsearches VALUES (1, 'en', 'foo', '2023-01-01 12:00:00'), (2, 'en', 'bar', '2023-01-01 12:00:00'), (3, 'en', 'foo', '2023-01-01 12:00:00')",
        );

        // Test existing signature still works (backward compatibility)
        $actualSearches = $this->search->getMostPopularSearches(2, false);

        $this->assertEquals(2, count($actualSearches));
        $this->assertEquals('foo', $actualSearches[0]['searchterm']);
        $this->assertEquals(2, $actualSearches[0]['number']);
    }

    public function testGetSearchesCount(): void
    {
        $this->dbHandle->query("INSERT INTO faqsearches VALUES (1, 'en', 'foo', ''), (2, 'en', 'bar', '')");

        $actualCount = $this->search->getSearchesCount();

        $this->assertEquals(2, $actualCount);
    }

    public function testSetAndGetCategory(): void
    {
        $categoryMock = $this->createMock(Category::class);
        $this->search->setCategory($categoryMock);

        $this->assertEquals($categoryMock, $this->search->getCategory());
    }

    /**
     * @throws Exception
     */
    public function testAutoCompleteFallsBackToDatabaseSearch(): void
    {
        $this->setConfigValue('search.enableElasticsearch', false);
        $this->setConfigValue('search.enableOpenSearch', false);

        $search = $this
            ->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchDatabase'])
            ->getMock();

        $search
            ->expects($this->once())
            ->method('searchDatabase')
            ->with('foo', false)
            ->willReturn([['question' => 'foo']]);

        $this->assertSame([['question' => 'foo']], $search->autoComplete('foo'));
    }

    public function testResolveSearchDatabaseTypeMapsKnownDrivers(): void
    {
        $driverMap = [
            [new \phpMyFAQ\Database\PdoMysql(), 'pdo_mysql'],
            [new \phpMyFAQ\Database\PdoPgsql(), 'pdo_pgsql'],
            [new \phpMyFAQ\Database\PdoSqlite(), 'pdo_sqlite'],
            [new \phpMyFAQ\Database\PdoSqlsrv(), 'pdo_sqlsrv'],
            [new \phpMyFAQ\Database\Sqlite3(), 'sqlite3'],
        ];

        foreach ($driverMap as [$driver, $expectedType]) {
            $config = $this->createMock(Configuration::class);
            $config->method('getDb')->willReturn($driver);
            $search = new Search($config);

            $this->assertSame($expectedType, $this->invokePrivateMethod($search, 'resolveSearchDatabaseType'));
        }
    }

    public function testSearchCustomPagesReturnsEmptyArrayForShortSearchWordsOnly(): void
    {
        $this->assertSame([], $this->invokePrivateMethod($this->search, 'searchCustomPages', 'an of', false));
    }

    public function testSearchCustomPagesReturnsLanguageScopedCustomPageResults(): void
    {
        $this->dbHandle->query(
            'INSERT INTO faqcustompages (id, lang, page_title, slug, content, author_name, author_email, active, created, updated) VALUES '
            . "(501, 'en', 'Search Page', 'search-page', 'Search body content', 'Unit Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),"
            . "(502, 'de', 'Deutsche Suche', 'deutsche-suche', 'Search body content', 'Unit Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00')",
        );

        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', 'Search body', false);

        $this->assertCount(1, $result);
        $this->assertSame('Search Page', $result[0]->question);
        $this->assertSame('search-page', $result[0]->slug);
        $this->assertSame('page', $result[0]->content_type);
    }

    public function testGetMostPopularSearchesBuildsPgsqlTimeWindowClause(): void
    {
        $db = $this->createMock(Sqlite3::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("NOW() - INTERVAL '7 days'"))
            ->willReturn(false);
        $db->expects($this->never())->method('fetchObject');

        $config = $this->createMock(Configuration::class);
        $config->method('getDb')->willReturn($db);

        $search = new Search($config);
        $this->setDatabaseType('pdo_pgsql');

        $this->assertSame([], $search->getMostPopularSearches(5, false, 7));
    }

    public function testGetMostPopularSearchesBuildsSqliteTimeWindowClause(): void
    {
        $db = $this->createMock(Sqlite3::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("datetime('now', '-3 days')"))
            ->willReturn(false);
        $db->expects($this->never())->method('fetchObject');

        $config = $this->createMock(Configuration::class);
        $config->method('getDb')->willReturn($db);

        $search = new Search($config);
        $this->setDatabaseType('pdo_sqlite');

        $this->assertSame([], $search->getMostPopularSearches(5, false, 3));
    }

    public function testGetMostPopularSearchesBuildsSqlsrvClauses(): void
    {
        $db = $this->createMock(Sqlite3::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->logicalAnd(
                $this->stringContains('DATEADD(day, -4, GETDATE())'),
                $this->stringContains('OFFSET 0 ROWS FETCH NEXT 2 ROWS ONLY'),
            ))
            ->willReturn(false);
        $db->expects($this->never())->method('fetchObject');

        $config = $this->createMock(Configuration::class);
        $config->method('getDb')->willReturn($db);

        $search = new Search($config);
        $this->setDatabaseType('pdo_sqlsrv');

        $this->assertSame([], $search->getMostPopularSearches(2, false, 4));
    }

    public function testGetMostPopularSearchesBuildsDefaultTimeWindowClause(): void
    {
        $db = $this->createMock(Sqlite3::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DATE_SUB(NOW(), INTERVAL 9 DAY)'))
            ->willReturn(false);
        $db->expects($this->never())->method('fetchObject');

        $config = $this->createMock(Configuration::class);
        $config->method('getDb')->willReturn($db);

        $search = new Search($config);
        $this->setDatabaseType('mysqli');

        $this->assertSame([], $search->getMostPopularSearches(5, false, 9));
    }
}
