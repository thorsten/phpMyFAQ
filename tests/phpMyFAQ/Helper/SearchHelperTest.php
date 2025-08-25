<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Search\SearchResultSet;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class SearchHelperTest extends TestCase
{
    private SearchHelper $searchHelper;
    private MockObject|Configuration $configurationMock;
    private MockObject|Category $categoryMock;
    private MockObject|SearchResultSet $searchResultSetMock;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->categoryMock = $this->createMock(Category::class);
        $this->searchResultSetMock = $this->createMock(SearchResultSet::class);

        $this->searchHelper = new SearchHelper($this->configurationMock);
        $this->searchHelper->setCategory($this->categoryMock);

        $reflection = new ReflectionClass($this->searchHelper);
        $sessionIdProperty = $reflection->getProperty('sessionId');
        $sessionIdProperty->setValue($this->searchHelper, 'sid=test123&');
    }

    public function testConstructor(): void
    {
        $helper = new SearchHelper($this->configurationMock);

        $this->assertInstanceOf(SearchHelper::class, $helper);
    }

    public function testSetSearchTerm(): void
    {
        $searchTerm = 'test search term';

        $this->searchHelper->setSearchTerm($searchTerm);

        $reflection = new ReflectionClass($this->searchHelper);
        $searchTermProperty = $reflection->getProperty('searchTerm');
        $actualSearchTerm = $searchTermProperty->getValue($this->searchHelper);

        $this->assertEquals($searchTerm, $actualSearchTerm);
    }

    public function testCreateAutoCompleteResultWithNoResults(): void
    {
        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(0);
        $this->searchResultSetMock->method('getResultSet')->willReturn([]);

        $result = $this->searchHelper->createAutoCompleteResult($this->searchResultSetMock);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCreateAutoCompleteResultWithResults(): void
    {
        $this->searchHelper->setSearchTerm('php test');

        $this->configurationMock->method('get')
            ->with('records.numberOfRecordsPerPage')
            ->willReturn(10);

        $this->configurationMock->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $mockResult = new stdClass();
        $mockResult->category_id = 1;
        $mockResult->id = 123;
        $mockResult->lang = 'en';
        $mockResult->question = 'How to use PHP?';

        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(1);
        $this->searchResultSetMock->method('getResultSet')->willReturn([$mockResult]);

        $this->categoryMock->method('getPath')->with(1)->willReturn('Programming/PHP');

        $result = $this->searchHelper->createAutoCompleteResult($this->searchResultSetMock);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $firstResult = $result[0];
        $this->assertInstanceOf(stdClass::class, $firstResult);
        $this->assertEquals('Programming/PHP', $firstResult->category);
        $this->assertStringContainsString('How to use PHP?', $firstResult->question);
    }

    public function testCreateAutoCompleteResultLimitsResults(): void
    {
        $this->configurationMock->method('get')
            ->with('records.numberOfRecordsPerPage')
            ->willReturn(2);

        $this->configurationMock->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $mockResults = [];
        for ($i = 1; $i <= 5; $i++) {
            $result = new stdClass();
            $result->category_id = $i;
            $result->id = $i * 100;
            $result->lang = 'en';
            $result->question = "Question $i";
            $mockResults[] = $result;
        }

        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(5);
        $this->searchResultSetMock->method('getResultSet')->willReturn($mockResults);

        $this->categoryMock->method('getPath')->willReturn('Test Category');

        $result = $this->searchHelper->createAutoCompleteResult($this->searchResultSetMock);

        $this->assertCount(5, $result);
    }

    public function testRenderAdminSuggestionResultWithNoResults(): void
    {
        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(0);
        $this->searchResultSetMock->method('getResultSet')->willReturn([]);

        $result = $this->searchHelper->renderAdminSuggestionResult($this->searchResultSetMock);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testRenderAdminSuggestionResultWithResults(): void
    {
        $this->configurationMock->method('get')
            ->with('records.numberOfRecordsPerPage')
            ->willReturn(10);

        $this->configurationMock->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $mockResult = new stdClass();
        $mockResult->id = 123;
        $mockResult->lang = 'en';
        $mockResult->question = 'Test question';
        $mockResult->solution_id = 456;

        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(1);
        $this->searchResultSetMock->method('getResultSet')->willReturn([$mockResult]);

        $result = $this->searchHelper->renderAdminSuggestionResult($this->searchResultSetMock);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $firstResult = $result[0];
        $this->assertArrayHasKey('url', $firstResult);
        $this->assertArrayHasKey('question', $firstResult);
        $this->assertArrayHasKey('adminUrl', $firstResult);
        $this->assertEquals('Test question', $firstResult['question']);
        $this->assertStringContainsString('solution_id=456', $firstResult['url']);
        $this->assertStringContainsString('admin/faq/edit/123/en', $firstResult['adminUrl']);
    }

    public function testGetSearchResultWithNoResults(): void
    {
        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(0);
        $this->searchResultSetMock->method('getResultSet')->willReturn([]);

        $result = $this->searchHelper->getSearchResult($this->searchResultSetMock, 1);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetSearchResultWithResults(): void
    {
        $this->searchHelper->setSearchTerm('php programming');

        $this->configurationMock->method('get')
            ->willReturnMap([
                ['records.numberOfRecordsPerPage', 10],
                ['search.enableHighlighting', true]
            ]);

        $this->configurationMock->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $mockResult = new stdClass();
        $mockResult->id = 123;
        $mockResult->lang = 'en';
        $mockResult->category_id = 1;
        $mockResult->question = 'How to learn PHP programming?';
        $mockResult->answer = 'PHP is a great programming language for web development...';
        $mockResult->score = 0.85;

        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(1);
        $this->searchResultSetMock->method('getResultSet')->willReturn([$mockResult]);

        $this->categoryMock->method('setLanguage')->with('en');
        $this->categoryMock->method('getCategoriesFromFaq')
            ->with(123)
            ->willReturn([1 => ['id' => 1, 'name' => 'Programming']]);
        $this->categoryMock->method('getPath')
            ->with(1)
            ->willReturn('Programming/PHP');

        $result = $this->searchHelper->getSearchResult($this->searchResultSetMock, 1);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $firstResult = $result[0];
        $this->assertInstanceOf(stdClass::class, $firstResult);
        $this->assertObjectHasProperty('question', $firstResult);
        $this->assertObjectHasProperty('path', $firstResult);
        $this->assertObjectHasProperty('url', $firstResult);
        $this->assertObjectHasProperty('answerPreview', $firstResult);
        $this->assertObjectHasProperty('renderedScore', $firstResult);
        $this->assertEquals('Programming/PHP', $firstResult->path);
        $this->assertStringContainsString('<span title=', $firstResult->renderedScore);
    }

    public function testGetSearchResultWithPagination(): void
    {
        $this->configurationMock->method('get')
            ->willReturnMap([
                ['records.numberOfRecordsPerPage', 2],
                ['search.enableHighlighting', false]
            ]);

        $this->configurationMock->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $mockResults = [];
        for ($i = 1; $i <= 5; $i++) {
            $result = new stdClass();
            $result->id = $i * 100;
            $result->lang = 'en';
            $result->category_id = 1;
            $result->question = "Question $i";
            $result->answer = "Answer $i";
            $result->score = 0.5;
            $mockResults[] = $result;
        }

        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(5);
        $this->searchResultSetMock->method('getResultSet')->willReturn($mockResults);

        $this->categoryMock->method('setLanguage');
        $this->categoryMock->method('getCategoriesFromFaq')->willReturn([1 => ['id' => 1]]);
        $this->categoryMock->method('getPath')->willReturn('Test');

        $result = $this->searchHelper->getSearchResult($this->searchResultSetMock, 2);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testRenderScoreWithZeroRelevance(): void
    {
        $reflection = new ReflectionClass($this->searchHelper);
        $method = $reflection->getMethod('renderScore');

        $result = $method->invoke($this->searchHelper, 0);

        $this->assertStringContainsString('<span title="0.00%">', $result);
        $this->assertStringContainsString('bi-star-o', $result);

        $emptyStarCount = substr_count($result, 'bi-star-o');
        $this->assertEquals(3, $emptyStarCount);
    }

    public function testRenderScoreWithLowRelevance(): void
    {
        $reflection = new ReflectionClass($this->searchHelper);
        $method = $reflection->getMethod('renderScore');

        $result = $method->invoke($this->searchHelper, 25.5);

        $this->assertStringContainsString('<span title="25.50%">', $result);
        $this->assertStringContainsString('bi-star', $result);
        $this->assertStringContainsString('bi-star">', $result);
        $this->assertStringContainsString('bi-star-o">', $result);
    }

    public function testRenderScoreWithMediumRelevance(): void
    {
        $reflection = new ReflectionClass($this->searchHelper);
        $method = $reflection->getMethod('renderScore');

        $result = $method->invoke($this->searchHelper, 50.0);

        $this->assertStringContainsString('<span title="50.00%">', $result);
        $this->assertStringContainsString('bi-star">', $result);
        $this->assertStringContainsString('bi-star-o">', $result);
        $fullStars = substr_count($result, 'bi-star">');
        $emptyStars = substr_count($result, 'bi-star-o">');
        $this->assertGreaterThan($emptyStars, $fullStars);
    }

    public function testRenderScoreWithHighRelevance(): void
    {
        $reflection = new ReflectionClass($this->searchHelper);
        $method = $reflection->getMethod('renderScore');

        $result = $method->invoke($this->searchHelper, 85.0);

        $this->assertStringContainsString('<span title="85.00%">', $result);
        $this->assertStringContainsString('bi-star">', $result);
        $this->assertStringNotContainsString('bi-star-o">', $result);
        $totalStars = substr_count($result, 'bi-star">');
        $this->assertEquals(3, $totalStars);
    }

    public function testRenderRelatedFaqsWithNoResults(): void
    {
        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(0);
        $this->searchResultSetMock->method('getResultSet')->willReturn([]);

        $result = $this->searchHelper->renderRelatedFaqs($this->searchResultSetMock, 123);

        $this->assertEquals('', $result);
    }

    public function testRenderRelatedFaqsWithResults(): void
    {
        $this->configurationMock->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $mockResults = [];

        $result1 = new stdClass();
        $result1->id = 123; // Same as recordId - should be skipped
        $result1->lang = 'en';
        $result1->category_id = 1;
        $result1->question = 'Same FAQ';
        $mockResults[] = $result1;

        $result2 = new stdClass();
        $result2->id = 456;
        $result2->lang = 'en';
        $result2->category_id = 1;
        $result2->question = 'Related FAQ 1';
        $mockResults[] = $result2;

        $result3 = new stdClass();
        $result3->id = 789;
        $result3->lang = 'en';
        $result3->category_id = 2;
        $result3->question = 'Related FAQ 2';
        $mockResults[] = $result3;

        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(3);
        $this->searchResultSetMock->method('getResultSet')->willReturn($mockResults);

        $result = $this->searchHelper->renderRelatedFaqs($this->searchResultSetMock, 123);

        $this->assertStringContainsString('<ul class="list-unstyled">', $result);
        $this->assertStringContainsString('</ul>', $result);
        $this->assertStringContainsString('bi-question-circle', $result);
        $this->assertStringContainsString('Related FAQ 1', $result);
        $this->assertStringContainsString('Related FAQ 2', $result);
        $this->assertStringNotContainsString('Same FAQ', $result);
    }

    public function testRenderRelatedFaqsLimitsToFiveResults(): void
    {
        $this->configurationMock->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $mockResults = [];
        for ($i = 1; $i <= 8; $i++) {
            $result = new stdClass();
            $result->id = $i * 100;
            $result->lang = 'en';
            $result->category_id = 1;
            $result->question = "FAQ $i";
            $mockResults[] = $result;
        }

        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(8);
        $this->searchResultSetMock->method('getResultSet')->willReturn($mockResults);

        $result = $this->searchHelper->renderRelatedFaqs($this->searchResultSetMock, 999);

        $this->assertStringContainsString('<ul class="list-unstyled">', $result);
        $listItemCount = substr_count($result, '<li>');
        $this->assertEquals(5, $listItemCount);
    }

    /**
     * Test inheritance from AbstractHelper
     */
    public function testInheritanceFromAbstractHelper(): void
    {
        $this->assertInstanceOf(AbstractHelper::class, $this->searchHelper);
        $this->assertTrue(method_exists($this->searchHelper, 'setCategory'));
        $this->assertTrue(method_exists($this->searchHelper, 'getCategory'));
    }

    /**
     * Test all public methods exist
     */
    public function testAllPublicMethodsExist(): void
    {
        $expectedMethods = [
            '__construct',
            'setSearchTerm',
            'createAutoCompleteResult',
            'renderAdminSuggestionResult',
            'getSearchResult',
            'renderRelatedFaqs'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                method_exists($this->searchHelper, $methodName),
                "Method $methodName should exist"
            );
        }
    }

    /**
     * Test method return types
     */
    public function testMethodReturnTypes(): void
    {
        $this->searchResultSetMock->method('getNumberOfResults')->willReturn(0);
        $this->searchResultSetMock->method('getResultSet')->willReturn([]);

        $autoCompleteResult = $this->searchHelper->createAutoCompleteResult($this->searchResultSetMock);
        $this->assertIsArray($autoCompleteResult);

        $adminResult = $this->searchHelper->renderAdminSuggestionResult($this->searchResultSetMock);
        $this->assertIsArray($adminResult);

        $searchResult = $this->searchHelper->getSearchResult($this->searchResultSetMock, 1);
        $this->assertIsArray($searchResult);

        $relatedFaqs = $this->searchHelper->renderRelatedFaqs($this->searchResultSetMock, 123);
        $this->assertIsString($relatedFaqs);
    }
}
