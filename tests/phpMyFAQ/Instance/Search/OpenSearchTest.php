<?php

declare(strict_types=1);

namespace phpMyFAQ\Instance\Search;

use Monolog\Logger;
use OpenSearch\Client;
use OpenSearch\Namespaces\IndicesNamespace;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class OpenSearchTest extends TestCase
{
    private OpenSearch $openSearch;
    private Client $clientMock;
    private IndicesNamespace $indicesMock;
    private OpenSearchConfiguration $osConfigMock;
    private Configuration $configMock;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->osConfigMock = $this->createStub(OpenSearchConfiguration::class);
        $this->osConfigMock->method('getIndex')->willReturn('phpmyfaq_os_test');

        $this->indicesMock = $this->createMock(IndicesNamespace::class);

        $this->clientMock = $this->createMock(Client::class);
        $this->clientMock->method('indices')->willReturn($this->indicesMock);

        $this->configMock = $this->createStub(Configuration::class);
        $this->configMock->method('getOpenSearch')->willReturn($this->clientMock);
        $this->configMock->method('getOpenSearchConfig')->willReturn($this->osConfigMock);
        $this->configMock->method('getDefaultLanguage')->willReturn('en');

        // OpenSearch is readonly, so we must use reflection to bypass constructor
        $ref = new ReflectionClass(OpenSearch::class);
        $this->openSearch = $ref->newInstanceWithoutConstructor();

        $configProp = $ref->getProperty('configuration');
        $configProp->setValue($this->openSearch, $this->configMock);

        $clientProp = $ref->getProperty('client');
        $clientProp->setValue($this->openSearch, $this->clientMock);

        $osConfigProp = $ref->getProperty('openSearchConfiguration');
        $osConfigProp->setValue($this->openSearch, $this->osConfigMock);

        // Set mappings
        $mappingsProp = $ref->getProperty('mappings');
        $mappingsProp->setValue($this->openSearch, [
            'properties' => [
                'id' => ['type' => 'integer'],
                'lang' => ['type' => 'keyword'],
                'solution_id' => ['type' => 'integer'],
                'question' => ['type' => 'text', 'analyzer' => 'autocomplete'],
                'answer' => ['type' => 'text', 'analyzer' => 'autocomplete'],
                'keywords' => ['type' => 'text', 'analyzer' => 'autocomplete'],
                'category_id' => ['type' => 'integer'],
                'content_type' => ['type' => 'keyword'],
                'slug' => ['type' => 'keyword'],
            ],
        ]);
    }

    public function testGetParamsReturnsCorrectStructure(): void
    {
        if (!defined('PMF_OPENSEARCH_NUMBER_SHARDS')) {
            define('PMF_OPENSEARCH_NUMBER_SHARDS', 2);
        }
        if (!defined('PMF_OPENSEARCH_NUMBER_REPLICAS')) {
            define('PMF_OPENSEARCH_NUMBER_REPLICAS', 0);
        }
        if (!defined('PMF_OPENSEARCH_STEMMING_LANGUAGE')) {
            define('PMF_OPENSEARCH_STEMMING_LANGUAGE', ['en' => 'english', 'de' => 'german']);
        }
        if (!defined('PMF_OPENSEARCH_TOKENIZER')) {
            define('PMF_OPENSEARCH_TOKENIZER', 'standard');
        }

        $ref = new ReflectionClass(OpenSearch::class);
        $getParams = $ref->getMethod('getParams');
        $params = $getParams->invoke($this->openSearch);

        $this->assertSame('phpmyfaq_os_test', $params['index']);
        $this->assertArrayHasKey('body', $params);
        $this->assertArrayHasKey('settings', $params['body']);

        $settings = $params['body']['settings'];
        $this->assertSame(2, $settings['number_of_shards']);
        $this->assertSame(0, $settings['number_of_replicas']);

        $analysis = $settings['analysis'];
        $this->assertArrayHasKey('filter', $analysis);
        $this->assertArrayHasKey('analyzer', $analysis);

        $autocompleteFilter = $analysis['filter']['autocomplete_filter'];
        $this->assertSame('edge_ngram', $autocompleteFilter['type']);
        $this->assertSame(1, $autocompleteFilter['min_gram']);
        $this->assertSame(20, $autocompleteFilter['max_gram']);

        $stemmer = $analysis['filter']['Language_stemmer'];
        $this->assertSame('stemmer', $stemmer['type']);
        $this->assertSame('english', $stemmer['name']);

        $autocomplete = $analysis['analyzer']['autocomplete'];
        $this->assertSame('custom', $autocomplete['type']);
        $this->assertSame('standard', $autocomplete['tokenizer']);
    }

    public function testMappingsContainExpectedFields(): void
    {
        $ref = new ReflectionClass(OpenSearch::class);
        $mappingsProp = $ref->getProperty('mappings');
        $mappings = $mappingsProp->getValue($this->openSearch);

        $this->assertArrayHasKey('properties', $mappings);
        $properties = $mappings['properties'];

        $expectedFields = [
            'id',
            'lang',
            'solution_id',
            'question',
            'answer',
            'keywords',
            'category_id',
            'content_type',
            'slug',
        ];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $properties, "Missing field: $field");
        }

        // Integer fields
        $this->assertSame('integer', $properties['id']['type']);
        $this->assertSame('integer', $properties['solution_id']['type']);
        $this->assertSame('integer', $properties['category_id']['type']);

        // Keyword fields
        $this->assertSame('keyword', $properties['lang']['type']);
        $this->assertSame('keyword', $properties['content_type']['type']);
        $this->assertSame('keyword', $properties['slug']['type']);

        // Text fields with autocomplete analyzer
        $this->assertSame('text', $properties['question']['type']);
        $this->assertSame('autocomplete', $properties['question']['analyzer']);
    }

    public function testCreateIndexWhenIndexDoesNotExist(): void
    {
        $this->indicesMock
            ->expects($this->once())
            ->method('exists')
            ->with(['index' => 'phpmyfaq_os_test'])
            ->willReturn(false);

        $this->indicesMock
            ->expects($this->once())
            ->method('create')
            ->with($this->isArray());

        // Mock getMapping for putMapping
        $this->indicesMock
            ->method('getMapping')
            ->willReturn([
                'phpmyfaq_os_test' => ['mappings' => []],
            ]);

        $this->indicesMock->method('putMapping')->willReturn(['acknowledged' => true]);

        $result = $this->openSearch->createIndex();

        $this->assertTrue($result);
    }

    public function testCreateIndexSkipsCreateWhenIndexExists(): void
    {
        $this->indicesMock->method('exists')->willReturn(true);

        // create should NOT be called
        $this->indicesMock->expects($this->never())->method('create');

        // Mock getMapping for putMapping
        $this->indicesMock
            ->method('getMapping')
            ->willReturn([
                'phpmyfaq_os_test' => ['mappings' => ['properties' => ['q' => 'text']]],
            ]);

        $result = $this->openSearch->createIndex();

        $this->assertTrue($result);
    }

    public function testPutMappingAppliesMappingWhenEmpty(): void
    {
        $this->indicesMock
            ->method('getMapping')
            ->willReturn([
                'phpmyfaq_os_test' => ['mappings' => []],
            ]);

        $this->indicesMock
            ->expects($this->once())
            ->method('putMapping')
            ->with($this->callback(function (array $params) {
                return $params['index'] === 'phpmyfaq_os_test' && isset($params['body']['properties']);
            }))
            ->willReturn(['acknowledged' => true]);

        $result = $this->openSearch->putMapping();

        $this->assertTrue($result);
    }

    public function testPutMappingSkipsWhenMappingsExist(): void
    {
        $this->indicesMock
            ->method('getMapping')
            ->willReturn([
                'phpmyfaq_os_test' => [
                    'mappings' => ['properties' => ['question' => ['type' => 'text']]],
                ],
            ]);

        $this->indicesMock->expects($this->never())->method('putMapping');

        $result = $this->openSearch->putMapping();

        $this->assertTrue($result);
    }

    public function testGetMappingReturnsClientResponse(): void
    {
        $expected = ['phpmyfaq_os_test' => ['mappings' => []]];
        $this->indicesMock->method('getMapping')->willReturn($expected);

        $result = $this->openSearch->getMapping();

        $this->assertSame($expected, $result);
    }

    public function testDropIndexDeletesTheIndex(): void
    {
        $this->indicesMock
            ->expects($this->once())
            ->method('delete')
            ->with(['index' => 'phpmyfaq_os_test'])
            ->willReturn(['acknowledged' => true]);

        $result = $this->openSearch->dropIndex();

        $this->assertSame(['acknowledged' => true], $result);
    }

    public function testIndexFaqSendsCorrectParams(): void
    {
        $faq = [
            'solution_id' => '42',
            'id' => '1',
            'lang' => 'en',
            'question' => 'What is phpMyFAQ?',
            'answer' => '<p>A FAQ tool</p>',
            'keywords' => 'faq,tool',
            'category_id' => '5',
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('index')
            ->with($this->callback(function (array $params) {
                return (
                    $params['index'] === 'phpmyfaq_os_test'
                    && $params['id'] === '42'
                    && $params['body']['question'] === 'What is phpMyFAQ?'
                    && $params['body']['answer'] === 'A FAQ tool' // strip_tags applied
                    && $params['body']['content_type'] === 'faq'
                );
            }))
            ->willReturn(['result' => 'created']);

        $result = $this->openSearch->index($faq);

        $this->assertSame(['result' => 'created'], $result);
    }

    public function testUpdateFaqSendsCorrectParams(): void
    {
        $faq = [
            'solution_id' => '42',
            'id' => '1',
            'lang' => 'en',
            'question' => 'Updated question',
            'answer' => '<b>Updated answer</b>',
            'keywords' => 'updated',
            'category_id' => '3',
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (array $params) {
                return (
                    $params['index'] === 'phpmyfaq_os_test'
                    && $params['id'] === '42'
                    && $params['body']['doc']['answer'] === 'Updated answer'
                );
            }))
            ->willReturn(['result' => 'updated']);

        $result = $this->openSearch->update($faq);

        $this->assertSame(['result' => 'updated'], $result);
    }

    public function testDeleteFaqSendsCorrectParams(): void
    {
        $this->clientMock
            ->expects($this->once())
            ->method('delete')
            ->with([
                'index' => 'phpmyfaq_os_test',
                'id' => 42,
            ])
            ->willReturn(['result' => 'deleted']);

        $result = $this->openSearch->delete(42);

        $this->assertSame(['result' => 'deleted'], $result);
    }

    public function testIsAvailableReturnsTrueOnPing(): void
    {
        $this->clientMock->method('ping')->willReturn(true);

        $this->assertTrue($this->openSearch->isAvailable());
    }

    public function testIsAvailableReturnsFalseOnException(): void
    {
        $loggerMock = $this->createStub(Logger::class);
        $this->configMock->method('getLogger')->willReturn($loggerMock);

        $this->clientMock->method('ping')->willThrowException(new \Exception('Connection refused'));

        $this->assertFalse($this->openSearch->isAvailable());
    }

    public function testIndexCustomPageWithActivePage(): void
    {
        $page = [
            'id' => 1,
            'lang' => 'en',
            'page_title' => 'About Us',
            'content' => '<h1>About</h1><p>Info</p>',
            'slug' => 'about-us',
            'active' => 'y',
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('index')
            ->with($this->callback(function (array $params) {
                return (
                    $params['id'] === 'page_1_en'
                    && $params['body']['content_type'] === 'page'
                    && $params['body']['slug'] === 'about-us'
                    && $params['body']['answer'] === 'AboutInfo' // strip_tags
                );
            }))
            ->willReturn(['result' => 'created']);

        $result = $this->openSearch->indexCustomPage($page);

        $this->assertSame(['result' => 'created'], $result);
    }

    public function testIndexCustomPageWithInactivePageCallsDelete(): void
    {
        $page = [
            'id' => 1,
            'lang' => 'en',
            'page_title' => 'Hidden Page',
            'content' => 'content',
            'slug' => 'hidden',
            'active' => 'n',
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('delete')
            ->with([
                'index' => 'phpmyfaq_os_test',
                'id' => 'page_1_en',
            ])
            ->willReturn(['result' => 'deleted']);

        // index() should NOT be called
        $this->clientMock->expects($this->never())->method('index');

        $this->openSearch->indexCustomPage($page);
    }

    public function testDeleteCustomPage(): void
    {
        $this->clientMock
            ->expects($this->once())
            ->method('delete')
            ->with([
                'index' => 'phpmyfaq_os_test',
                'id' => 'page_5_de',
            ])
            ->willReturn(['result' => 'deleted']);

        $result = $this->openSearch->deleteCustomPage(5, 'de');

        $this->assertSame(['result' => 'deleted'], $result);
    }

    public function testDeleteCustomPageReturnsErrorOnException(): void
    {
        $this->clientMock->method('delete')->willThrowException(new \Exception('not found'));

        $result = $this->openSearch->deleteCustomPage(99, 'en');

        $this->assertArrayHasKey('error', $result);
    }

    public function testBulkIndexSkipsInactiveFaqs(): void
    {
        $faqs = [
            [
                'active' => 'no',
                'solution_id' => '1',
                'id' => '1',
                'lang' => 'en',
                'title' => 'Inactive',
                'content' => 'content',
                'keywords' => '',
                'category_id' => '1',
            ],
            [
                'active' => 'yes',
                'solution_id' => '2',
                'id' => '2',
                'lang' => 'en',
                'title' => 'Active FAQ',
                'content' => '<p>content</p>',
                'keywords' => 'test',
                'category_id' => '1',
            ],
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('bulk')
            ->with($this->callback(function (array $params) {
                // Should only contain the active FAQ (2 body entries: action + doc)
                return count($params['body']) === 2 && $params['body'][0]['index']['_id'] === '2';
            }))
            ->willReturn(['errors' => false, 'items' => []]);

        $this->openSearch->bulkIndex($faqs);
    }

    public function testUpdateCustomPageWithInactivePageCallsDelete(): void
    {
        $page = [
            'id' => 3,
            'lang' => 'en',
            'page_title' => 'Deactivated',
            'content' => 'content',
            'slug' => 'deactivated',
            'active' => 'n',
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('delete')
            ->with([
                'index' => 'phpmyfaq_os_test',
                'id' => 'page_3_en',
            ])
            ->willReturn(['result' => 'deleted']);

        $this->openSearch->updateCustomPage($page);
    }

    public function testUpdateCustomPageWithDocumentMissingExceptionCreatesNew(): void
    {
        $page = [
            'id' => 4,
            'lang' => 'en',
            'page_title' => 'New Page',
            'content' => 'content',
            'slug' => 'new-page',
            'active' => 'y',
        ];

        $this->clientMock->method('update')->willThrowException(new \Exception('document_missing_exception'));

        // Should fall back to indexCustomPage which calls index()
        $this->clientMock
            ->expects($this->once())
            ->method('index')
            ->willReturn(['result' => 'created']);

        $result = $this->openSearch->updateCustomPage($page);

        $this->assertSame(['result' => 'created'], $result);
    }

    public function testUpdateCustomPageWithGenericErrorReturnsError(): void
    {
        $loggerMock = $this->createStub(Logger::class);
        $this->configMock->method('getLogger')->willReturn($loggerMock);

        $page = [
            'id' => 5,
            'lang' => 'en',
            'page_title' => 'Broken Page',
            'content' => 'content',
            'slug' => 'broken',
            'active' => 'y',
        ];

        $this->clientMock->method('update')->willThrowException(new \Exception('some_other_error'));

        $result = $this->openSearch->updateCustomPage($page);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('some_other_error', $result['error']);
    }

    public function testBulkIndexCustomPagesWithActivePages(): void
    {
        $pages = [
            [
                'id' => 1,
                'lang' => 'en',
                'page_title' => 'Page 1',
                'content' => '<p>Content 1</p>',
                'slug' => 'page-1',
                'active' => 'y',
            ],
            [
                'id' => 2,
                'lang' => 'en',
                'page_title' => 'Page 2',
                'content' => '<p>Content 2</p>',
                'slug' => 'page-2',
                'active' => 'n', // inactive - should be skipped
            ],
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('bulk')
            ->with($this->callback(function (array $params) {
                // Only 1 active page = 2 body entries (action + doc)
                return (
                    count($params['body']) === 2
                    && $params['body'][0]['index']['_id'] === 'page_1_en'
                    && $params['body'][1]['content_type'] === 'page'
                );
            }))
            ->willReturn(['errors' => false]);

        $result = $this->openSearch->bulkIndexCustomPages($pages);

        $this->assertArrayHasKey('success', $result);
    }

    public function testBulkIndexCustomPagesAllInactiveReturnsSuccess(): void
    {
        $pages = [
            [
                'id' => 1,
                'lang' => 'en',
                'page_title' => 'Hidden',
                'content' => 'content',
                'slug' => 'hidden',
                'active' => 'n',
            ],
        ];

        // bulk should NOT be called since body is empty
        $this->clientMock->expects($this->never())->method('bulk');

        $result = $this->openSearch->bulkIndexCustomPages($pages);

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function testBulkIndexReturnsErrorOnBulkException(): void
    {
        $faqs = [
            [
                'active' => 'yes',
                'solution_id' => '1',
                'id' => '1',
                'lang' => 'en',
                'title' => 'FAQ',
                'content' => 'content',
                'keywords' => '',
                'category_id' => '1',
            ],
        ];

        $this->clientMock->method('bulk')->willThrowException(new \Exception('bulk error'));

        // The OpenSearch bulkIndex doesn't catch exceptions, so this should propagate
        $this->expectException(\Exception::class);
        $this->openSearch->bulkIndex($faqs);
    }

    public function testIndexCustomPageWithErrorReturnsErrorArray(): void
    {
        $loggerMock = $this->createStub(Logger::class);
        $this->configMock->method('getLogger')->willReturn($loggerMock);

        $page = [
            'id' => 10,
            'lang' => 'en',
            'page_title' => 'Error Page',
            'content' => 'content',
            'slug' => 'error-page',
            'active' => 'y',
        ];

        $this->clientMock->method('index')->willThrowException(new \Exception('index error'));

        $result = $this->openSearch->indexCustomPage($page);

        $this->assertArrayHasKey('error', $result);
    }

    public function testUpdateActivePage(): void
    {
        $page = [
            'id' => 6,
            'lang' => 'de',
            'page_title' => 'Updated Page',
            'content' => '<p>Updated content</p>',
            'slug' => 'updated-page',
            'active' => 'y',
        ];

        $this->clientMock
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (array $params) {
                return (
                    $params['id'] === 'page_6_de'
                    && $params['body']['doc']['content_type'] === 'page'
                    && $params['body']['doc']['answer'] === 'Updated content'
                );
            }))
            ->willReturn(['result' => 'updated']);

        $result = $this->openSearch->updateCustomPage($page);

        $this->assertSame(['result' => 'updated'], $result);
    }
}
