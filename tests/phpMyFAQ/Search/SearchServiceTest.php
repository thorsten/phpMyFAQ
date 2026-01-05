<?php

declare(strict_types=1);

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class SearchServiceTest extends TestCase
{
    private Configuration|MockObject $configuration;
    private CurrentUser|MockObject $currentUser;
    private array $currentGroups;
    private SearchService $searchService;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        // Create configuration with real database
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $baseConfiguration = new Configuration($dbHandle);

        $language = new Language($baseConfiguration, $this->createStub(Session::class));
        $baseConfiguration->setLanguage($language);

        // Set configuration values
        $baseConfiguration->set('records.numberOfRecordsPerPage', 10);
        $baseConfiguration->set('search.numberSearchTerms', 7);
        $baseConfiguration->set('search.searchForSolutionId', true);

        // Create an anonymous class that extends Configuration and overrides getDefaultUrl
        $this->configuration = new class($dbHandle) extends Configuration {
            public function getDefaultUrl(): string
            {
                return 'http://example.com/';
            }
        };

        // Copy the configuration from base
        $this->configuration->setLanguage($language);
        $this->configuration->set('records.numberOfRecordsPerPage', 10);
        $this->configuration->set('search.numberSearchTerms', 7);
        $this->configuration->set('search.searchForSolutionId', true);

        // Create mock current user
        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->currentUser->method('getUserId')->willReturn(1);

        $this->currentGroups = [1];
    }

    /**
     * @throws Exception
     */
    public function testConstructor(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);
        $this->assertInstanceOf(SearchService::class, $searchService);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchWithEmptySearchTerm(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: '',
            inputTag: '',
            inputCategory: 0,
            allLanguages: false,
            page: 1,
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tagSearch', $result);
        $this->assertArrayHasKey('selectedCategory', $result);
        $this->assertArrayHasKey('categories', $result);
        $this->assertArrayHasKey('numberOfSearchResults', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('searchTerm', $result);
        $this->assertArrayHasKey('searchResults', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertFalse($result['tagSearch']);
        $this->assertEquals(1, $result['currentPage']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchWithCategoryPercentConvertsToZero(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: '',
            inputTag: '',
            inputCategory: '%',
            allLanguages: false,
            page: 1,
        );

        $this->assertEquals(0, $result['selectedCategory']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchPagination(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: '',
            inputTag: '',
            inputCategory: 0,
            allLanguages: false,
            page: 2,
        );

        $this->assertEquals(2, $result['currentPage']);
        $this->assertNotEmpty($result['pagination']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchReturnsAllRequiredKeys(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: 'test',
            inputTag: '',
            inputCategory: 0,
            allLanguages: false,
            page: 1,
        );

        $expectedKeys = [
            'tagSearch',
            'selectedCategory',
            'categories',
            'numberOfSearchResults',
            'totalPages',
            'currentPage',
            'searchTerm',
            'searchTags',
            'searchResults',
            'allLanguages',
            'mostPopularSearches',
            'relatedTags',
            'tagList',
            'pagination',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: $key");
        }
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchWithAllLanguages(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: 'test',
            inputTag: '',
            inputCategory: 0,
            allLanguages: true,
            page: 1,
        );

        $this->assertTrue($result['allLanguages']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testShouldRedirectToSolutionIdReturnsTrueForValidSolutionId(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->shouldRedirectToSolutionId('1000', 5);
        $this->assertTrue($result);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testShouldRedirectToSolutionIdReturnsFalseForNonNumeric(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->shouldRedirectToSolutionId('test', 5);
        $this->assertFalse($result);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testShouldRedirectToSolutionIdReturnsFalseForSmallNumber(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->shouldRedirectToSolutionId('10', 5);
        $this->assertFalse($result);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testShouldRedirectToSolutionIdReturnsFalseForZeroResults(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->shouldRedirectToSolutionId('1000', 0);
        $this->assertFalse($result);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testShouldRedirectToSolutionIdReturnsFalseWhenDisabled(): void
    {
        // Temporarily disable solution ID redirect
        $this->configuration->set('search.searchForSolutionId', false);

        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->shouldRedirectToSolutionId('1000', 5);
        $this->assertFalse($result);

        // Restore original value
        $this->configuration->set('search.searchForSolutionId', true);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testGetSolutionIdRedirectUrl(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->getSolutionIdRedirectUrl('1000');
        $this->assertEquals('http://example.com/solution_id_1000.html', $result);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchCalculatesTotalPages(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        // Mock configuration to return 10 records per page
        $result = $searchService->processSearch(
            inputSearchTerm: '',
            inputTag: '',
            inputCategory: 0,
            allLanguages: false,
            page: 1,
        );

        // With no results, total pages should be 0
        $this->assertIsInt($result['totalPages']);
        $this->assertGreaterThanOrEqual(0, $result['totalPages']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchReturnsEmptyTagsForNonTagSearch(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: 'test',
            inputTag: '',
            inputCategory: 0,
            allLanguages: false,
            page: 1,
        );

        $this->assertEquals('', $result['searchTags']);
        $this->assertFalse($result['tagSearch']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchWithValidCategory(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: 'test',
            inputTag: '',
            inputCategory: 5,
            allLanguages: false,
            page: 1,
        );

        $this->assertEquals(5, $result['selectedCategory']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchReturnsArrays(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: '',
            inputTag: '',
            inputCategory: 0,
            allLanguages: false,
            page: 1,
        );

        $this->assertIsArray($result['categories']);
        $this->assertIsArray($result['searchResults']);
        $this->assertIsArray($result['mostPopularSearches']);
        $this->assertIsArray($result['tagList']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchWithHighPageNumber(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $result = $searchService->processSearch(
            inputSearchTerm: '',
            inputTag: '',
            inputCategory: 0,
            allLanguages: false,
            page: 100,
        );

        $this->assertEquals(100, $result['currentPage']);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testProcessSearchPreservesSearchTerm(): void
    {
        $searchService = new SearchService($this->configuration, $this->currentUser, $this->currentGroups);

        $searchTerm = 'my search query';
        $result = $searchService->processSearch(
            inputSearchTerm: $searchTerm,
            inputTag: '',
            inputCategory: 0,
            allLanguages: false,
            page: 1,
        );

        $this->assertEquals($searchTerm, $result['searchTerm']);
    }
}
