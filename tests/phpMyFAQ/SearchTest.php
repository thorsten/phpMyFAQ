<?php

namespace phpMyFAQ;

use Exception;
use OpenSearch\Client as OpenSearchClient;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Plugin\PluginException;
use phpMyFAQ\Search\Search\Elasticsearch;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Elasticsearch::class)]
#[UsesClass(ElasticsearchConfiguration::class)]
#[UsesClass(OpenSearchConfiguration::class)]
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

    /**
     * @throws Exception
     */
    public function testLogSearchTermReturnsEarlyForEmptyString(): void
    {
        $this->assertSame(0, $this->search->getSearchesCount());

        $this->search->logSearchTerm('');

        $this->assertSame(0, $this->search->getSearchesCount());
    }

    /**
     * @throws Exception
     */
    public function testAutoCompleteUsesOpenSearchWhenConfigured(): void
    {
        $language = $this->createMock(Language::class);
        $language->method('getLanguage')->willReturn('en');

        $category = $this->createMock(Category::class);
        $category->method('getAllCategoryIds')->willReturn([1, 2, 3]);

        $client = $this
            ->getMockBuilder(OpenSearchClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['search'])
            ->getMock();
        $client
            ->expects($this->once())
            ->method('search')
            ->with($this->callback(static function (array $params): bool {
                return $params['body']['query']['bool']['filter']['term']['lang'] === 'en';
            }))
            ->willReturn([
                'hits' => [
                    'total' => ['value' => 1],
                    'hits' => [
                        [
                            '_source' => [
                                'id' => 42,
                                'lang' => 'en',
                                'question' => 'elastic result',
                                'answer' => 'answer',
                                'keywords' => 'phpmyfaq',
                                'category_id' => 1,
                            ],
                            '_score' => 1.0,
                        ],
                    ],
                ],
            ]);

        $config = $this->createMock(Configuration::class);
        $openSearchConfigFile = tempnam(sys_get_temp_dir(), 'pmf-opensearch-');
        file_put_contents(
            $openSearchConfigFile,
            "<?php\n\$PMF_OS = ['hosts' => ['http://localhost:9200'], 'index' => 'faq-index'];\n",
        );
        $config
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($item) {
                'search.enableElasticsearch' => false,
                'search.enableOpenSearch' => true,
                default => null,
            });
        $config->method('getLanguage')->willReturn($language);
        $config->method('getOpenSearch')->willReturn($client);
        $config->method('getOpenSearchConfig')->willReturn(new OpenSearchConfiguration($openSearchConfigFile));

        $search = new Search($config);
        $search->setCategory($category);

        $results = $search->autoComplete('elastic');

        $this->assertCount(1, $results);
        $this->assertSame('elastic result', $results[0]->question);

        unlink($openSearchConfigFile);
    }

    /**
     * @throws Exception
     */
    public function testSearchOpenSearchUsesCategoryAndLanguageFilters(): void
    {
        $language = $this->createMock(Language::class);
        $language->method('getLanguage')->willReturn('en');

        $category = $this->createMock(Category::class);
        $category->method('getAllCategoryIds')->willReturn([1, 2, 3]);
        $category->expects($this->once())->method('getChildNodes')->with(2)->willReturn([4, 5]);

        $client = $this
            ->getMockBuilder(OpenSearchClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['search'])
            ->getMock();
        $client
            ->expects($this->once())
            ->method('search')
            ->with($this->callback(static function (array $params): bool {
                return $params['body']['query']['bool']['should'][0]['bool']['filter']['terms']['category_id'] === [
                    2,
                    4,
                    5,
                ];
            }))
            ->willReturn([
                'hits' => [
                    'total' => ['value' => 1],
                    'hits' => [
                        [
                            '_source' => [
                                'id' => 84,
                                'lang' => 'en',
                                'question' => 'open search result',
                                'answer' => 'answer',
                                'keywords' => 'phpmyfaq',
                                'category_id' => 2,
                            ],
                            '_score' => 1.5,
                        ],
                    ],
                ],
            ]);

        $config = $this->createMock(Configuration::class);
        $openSearchConfigFile = tempnam(sys_get_temp_dir(), 'pmf-opensearch-');
        file_put_contents(
            $openSearchConfigFile,
            "<?php\n\$PMF_OS = ['hosts' => ['http://localhost:9200'], 'index' => 'faq-index'];\n",
        );
        $config->method('getLanguage')->willReturn($language);
        $config->method('getOpenSearch')->willReturn($client);
        $config->method('getOpenSearchConfig')->willReturn(new OpenSearchConfiguration($openSearchConfigFile));

        $search = new Search($config);
        $search->setCategory($category);
        $search->setCategoryId(2);

        $results = $search->searchOpenSearch('elastic', false);

        $this->assertCount(1, $results);
        $this->assertSame('open search result', $results[0]->question);

        unlink($openSearchConfigFile);
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

    // =========================================================================
    // LIKE wildcard injection tests for searchCustomPages()
    // =========================================================================

    /**
     * Helper to insert test custom pages and clean up after test.
     */
    private function insertCustomPages(): void
    {
        $this->dbHandle->query(
            'INSERT INTO faqcustompages (id, lang, page_title, slug, content, author_name, author_email, active, created, updated) VALUES '
            . "(601, 'en', 'FAQ Setup Guide', 'faq-setup', 'How to configure your FAQ system', 'Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),"
            . "(602, 'en', 'API Documentation', 'api-docs', 'REST API endpoints and authentication', 'Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),"
            . "(603, 'en', 'Internal Credentials Page', 'internal-creds', 'Database password: secret_value_123', 'Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),"
            . "(604, 'en', 'Release Notes v4.1', 'release-notes', 'Bug fixes and improvements for version 4.1', 'Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00'),"
            . "(605, 'de', 'Deutsche Seite', 'de-page', 'Inhalt auf Deutsch', 'Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00')",
        );
    }

    private function deleteCustomPages(): void
    {
        $this->dbHandle->query('DELETE FROM faqcustompages WHERE id IN (601, 602, 603, 604, 605)');
    }

    public function testSearchCustomPagesDoesNotMatchAllRecordsWithPercentWildcard(): void
    {
        $this->insertCustomPages();

        // "_%_" is 3 chars, passes the strlen > 2 filter.
        // Without the fix, this would generate LIKE '%_%_%' matching virtually all rows.
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', '_%_', true);

        // The literal string "_%_" does not appear in any of our test pages,
        // so the result should be empty.
        $this->assertCount(0, $result);

        $this->deleteCustomPages();
    }

    public function testSearchCustomPagesDoesNotMatchWithPercentOnlyTerm(): void
    {
        $this->insertCustomPages();

        // "%%%" is 3 chars. Without fix, LIKE '%%%%%' matches everything.
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', '%%%', true);

        $this->assertCount(0, $result);

        $this->deleteCustomPages();
    }

    public function testSearchCustomPagesDoesNotMatchWithUnderscoreWildcard(): void
    {
        $this->insertCustomPages();

        // "F_Q" should NOT match "FAQ" because _ must be treated as literal.
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', 'F_Q', true);

        $this->assertCount(0, $result);

        $this->deleteCustomPages();
    }

    public function testSearchCustomPagesMatchesExactTermWithPercent(): void
    {
        // Insert a page that literally contains "100% complete" in the content
        $this->dbHandle->query(
            'INSERT INTO faqcustompages (id, lang, page_title, slug, content, author_name, author_email, active, created, updated) VALUES '
            . "(610, 'en', 'Progress Report', 'progress', 'The migration is 100% complete now', 'Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00')",
        );

        // Searching for "100%" should still find this page (the % is literal in the content)
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', '100%', true);

        $this->assertCount(1, $result);
        $this->assertSame('Progress Report', $result[0]->question);

        $this->dbHandle->query('DELETE FROM faqcustompages WHERE id = 610');
    }

    public function testSearchCustomPagesMatchesExactTermWithUnderscore(): void
    {
        // Insert a page that literally contains "my_variable" in the content
        $this->dbHandle->query(
            'INSERT INTO faqcustompages (id, lang, page_title, slug, content, author_name, author_email, active, created, updated) VALUES '
            . "(611, 'en', 'Code Docs', 'code-docs', 'Use my_variable to store the value', 'Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00')",
        );

        // Searching for "my_variable" should find the page (literal underscore match)
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', 'my_variable', true);

        $this->assertCount(1, $result);
        $this->assertSame('Code Docs', $result[0]->question);

        $this->dbHandle->query('DELETE FROM faqcustompages WHERE id = 611');
    }

    public function testSearchCustomPagesLiteralSearchStillWorks(): void
    {
        $this->insertCustomPages();

        // Normal search should still find matching pages
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', 'FAQ Setup', true);

        $this->assertCount(1, $result);
        $this->assertSame('FAQ Setup Guide', $result[0]->question);

        $this->deleteCustomPages();
    }

    public function testSearchCustomPagesDoesNotMatchWithMixedWildcards(): void
    {
        $this->insertCustomPages();

        // "a%b_c" contains both wildcards, should be treated literally
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', 'a%b_c', true);

        $this->assertCount(0, $result);

        $this->deleteCustomPages();
    }

    public function testSearchCustomPagesWildcardDoesNotDumpAllPages(): void
    {
        $this->insertCustomPages();

        // "___" (three underscores) would match any 3-char substring without fix.
        // With fix, it searches for literal "___" which appears nowhere.
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', '___', true);

        $this->assertCount(0, $result);

        $this->deleteCustomPages();
    }

    public function testSearchCustomPagesWildcardPercentUnderscoreCombo(): void
    {
        $this->insertCustomPages();

        // "%_%_%_%" is 7 chars, passes filter. Without fix, matches almost everything.
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', '%_%_%_%', true);

        $this->assertCount(0, $result);

        $this->deleteCustomPages();
    }

    public function testSearchCustomPagesMultipleWordsWithWildcards(): void
    {
        $this->insertCustomPages();

        // "FAQ _%_" — "FAQ" is a real match, but "_%_" should not broaden it
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', 'FAQ _%_', true);

        // Should find pages matching "FAQ" literally, but not everything
        foreach ($result as $row) {
            $this->assertTrue(
                str_contains($row->question, 'FAQ') || str_contains($row->answer, 'FAQ'),
                'All results should contain the literal word "FAQ"',
            );
        }

        $this->deleteCustomPages();
    }

    public function testSearchCustomPagesBackslashInSearchTerm(): void
    {
        // Insert a page with a literal backslash in content
        $this->dbHandle->query(
            'INSERT INTO faqcustompages (id, lang, page_title, slug, content, author_name, author_email, active, created, updated) VALUES '
            . "(612, 'en', 'Windows Paths', 'win-paths', 'Use C:\\\\Users\\\\path for Windows', 'Test', 'test@example.org', 'y', '2024-01-01 00:00:00', '2024-01-01 00:00:00')",
        );

        // Search with a backslash should not cause SQL errors
        $result = $this->invokePrivateMethod($this->search, 'searchCustomPages', 'C:\\Users', true);

        // Whether it matches depends on escape handling, but it must not error out
        $this->assertIsArray($result);

        $this->dbHandle->query('DELETE FROM faqcustompages WHERE id = 612');
    }
}
