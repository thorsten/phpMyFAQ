<?php

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\TestCase;
use stdClass;

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
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
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
    public function testSearchWithNumericTerm(): void
    {
        $this->search = $this->getMockBuilder(Search::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['searchDatabase'])
            ->getMock();

        $this->search->expects($this->once())
            ->method('searchDatabase')
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

        $this->search->expects($this->once())
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
            "INSERT INTO faqsearches VALUES (1, 'en', 'foo', ''), (2, 'en', 'bar', ''), (3, 'en', 'foo', '')"
        );

        $actualSearches = $this->search->getMostPopularSearches(2);

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
        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->search->setCategory($categoryMock);

        $this->assertEquals($categoryMock, $this->search->getCategory());
    }
}
