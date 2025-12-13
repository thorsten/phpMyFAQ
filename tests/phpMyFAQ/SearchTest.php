<?php

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

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

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->search = new Search($this->configuration);
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

        $this->search = $this->getMockBuilder(Search::class)
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

        $this->search = $this->getMockBuilder(Search::class)
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

        $this->search = $this->getMockBuilder(Search::class)
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

        $this->search = $this->getMockBuilder(Search::class)
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
        $this->search = $this->getMockBuilder(Search::class)
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
        $categoryMock = $this->getMockBuilder(Category::class)->disableOriginalConstructor()->getMock();
        $this->search->setCategory($categoryMock);

        $this->assertEquals($categoryMock, $this->search->getCategory());
    }
}
