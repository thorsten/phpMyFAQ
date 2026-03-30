<?php

declare(strict_types=1);

namespace phpMyFAQ\Instance\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Elastic\Transport\NodePool\Node;
use Elastic\Transport\NodePool\NodePoolInterface;
use Elastic\Transport\Transport;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class ElasticsearchTest extends TestCase
{
    private Elasticsearch $elasticsearch;
    private Indices $indicesMock;
    private ElasticsearchConfiguration $esConfigMock;
    private Configuration $configMock;
    private HttpClientInterface $httpClientMock;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->esConfigMock = $this->createStub(ElasticsearchConfiguration::class);
        $this->esConfigMock->method('getIndex')->willReturn('phpmyfaq_test');

        $this->indicesMock = $this->createMock(Indices::class);

        $this->configMock = $this->createStub(Configuration::class);
        $this->configMock->method('getElasticsearchConfig')->willReturn($this->esConfigMock);
        $this->configMock->method('getDefaultLanguage')->willReturn('en');

        // Create a real Client via reflection (it's final, can't mock)
        $clientRef = new ReflectionClass(Client::class);
        $client = $clientRef->newInstanceWithoutConstructor();

        // Inject our mocked Indices into the client's namespace array
        $namespaceProp = $clientRef->getProperty('namespace');
        $namespaceProp->setValue($client, ['Indices' => $this->indicesMock]);

        // Set up PSR-18 HTTP client mock for document-level operations
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);

        $nodePoolMock = $this->createMock(NodePoolInterface::class);
        $nodePoolMock->method('nextNode')->willReturn(new Node('http://localhost:9200'));

        $transport = new Transport($this->httpClientMock, $nodePoolMock, new NullLogger());

        // Inject transport and logger into the Client
        $transportProp = $clientRef->getProperty('transport');
        $transportProp->setValue($client, $transport);

        $loggerProp = $clientRef->getProperty('logger');
        $loggerProp->setValue($client, new NullLogger());

        $responseExceptionProp = $clientRef->getProperty('responseException');
        $responseExceptionProp->setValue($client, true);

        $elasticMetaProp = $clientRef->getProperty('elasticMetaHeader');
        $elasticMetaProp->setValue($client, false);

        $asyncProp = $clientRef->getProperty('async');
        $asyncProp->setValue($client, false);

        // Create Elasticsearch instance via reflection to bypass constructor
        $ref = new ReflectionClass(Elasticsearch::class);
        $this->elasticsearch = $ref->newInstanceWithoutConstructor();

        $configProp = $ref->getProperty('configuration');
        $configProp->setValue($this->elasticsearch, $this->configMock);

        $esConfigProp = $ref->getProperty('elasticsearchConfiguration');
        $esConfigProp->setValue($this->elasticsearch, $this->esConfigMock);

        $clientProp = $ref->getProperty('client');
        $clientProp->setValue($this->elasticsearch, $client);

        // Invoke buildMappings to populate the mappings property
        $buildMappings = $ref->getMethod('buildMappings');
        $mappings = $buildMappings->invoke($this->elasticsearch);
        $mappingsProp = $ref->getProperty('mappings');
        $mappingsProp->setValue($this->elasticsearch, $mappings);
    }

    private function createElasticsearchResponse(int $statusCode, array $body): Response
    {
        return new Response(
            $statusCode,
            [
                'Content-Type' => 'application/json',
                'X-Elastic-Product' => 'Elasticsearch',
            ],
            json_encode($body),
        );
    }

    // --- Existing tests for params, mappings, and private helpers ---

    public function testGetParamsReturnsCorrectStructure(): void
    {
        $ref = new ReflectionClass(Elasticsearch::class);
        $getParams = $ref->getMethod('getParams');
        $params = $getParams->invoke($this->elasticsearch);

        $this->assertSame('phpmyfaq_test', $params['index']);
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
        $this->assertContains('lowercase', $autocomplete['filter']);
        $this->assertContains('autocomplete_filter', $autocomplete['filter']);
        $this->assertContains('Language_stemmer', $autocomplete['filter']);
    }

    public function testBuildMappingsReturnsCorrectStructure(): void
    {
        $ref = new ReflectionClass(Elasticsearch::class);
        $buildMappings = $ref->getMethod('buildMappings');
        $mappings = $buildMappings->invoke($this->elasticsearch);

        $this->assertArrayHasKey('_source', $mappings);
        $this->assertTrue($mappings['_source']['enabled']);

        $this->assertArrayHasKey('properties', $mappings);
        $properties = $mappings['properties'];

        foreach (['question', 'answer', 'keywords', 'categories'] as $field) {
            $this->assertArrayHasKey($field, $properties);
            $this->assertSame('search_as_you_type', $properties[$field]['type']);
            $this->assertSame('autocomplete', $properties[$field]['analyzer']);
            $this->assertSame('standard', $properties[$field]['search_analyzer']);
        }

        $this->assertSame('keyword', $properties['content_type']['type']);
        $this->assertSame('keyword', $properties['slug']['type']);
    }

    public function testGetSearchAnalyzerReturnsStandardByDefault(): void
    {
        $ref = new ReflectionClass(Elasticsearch::class);
        $method = $ref->getMethod('getSearchAnalyzer');

        $this->assertSame('standard', $method->invoke($this->elasticsearch));
    }

    public function testGetTokenizerReturnsStandardByDefault(): void
    {
        $ref = new ReflectionClass(Elasticsearch::class);
        $method = $ref->getMethod('getTokenizer');

        $this->assertSame('standard', $method->invoke($this->elasticsearch));
    }

    public function testGetNumberOfShardsReturnsDefaultTwo(): void
    {
        $ref = new ReflectionClass(Elasticsearch::class);
        $method = $ref->getMethod('getNumberOfShards');

        $this->assertSame(2, $method->invoke($this->elasticsearch));
    }

    public function testGetNumberOfReplicasReturnsDefaultZero(): void
    {
        $ref = new ReflectionClass(Elasticsearch::class);
        $method = $ref->getMethod('getNumberOfReplicas');

        $this->assertSame(0, $method->invoke($this->elasticsearch));
    }

    public function testGetStemmingLanguageReturnsEnglishByDefault(): void
    {
        $ref = new ReflectionClass(Elasticsearch::class);
        $method = $ref->getMethod('getStemmingLanguage');

        $this->assertSame('english', $method->invoke($this->elasticsearch));
    }

    // --- Index management tests (use Indices mock) ---

    public function testCreateIndexCallsClientIndicesCreate(): void
    {
        $this->indicesMock
            ->expects($this->once())
            ->method('create')
            ->with($this->isArray());

        $responseMock = $this->createStub(ElasticsearchResponse::class);
        $responseMock->method('offsetExists')->willReturn(true);
        $responseMock->method('offsetGet')->willReturn(['mappings' => []]);

        $this->indicesMock->method('getMapping')->willReturn($responseMock);

        $this->indicesMock->method('putMapping')->willReturn(['acknowledged' => true]);

        $result = $this->elasticsearch->createIndex();

        $this->assertTrue($result);
    }

    public function testCreateIndexThrowsExceptionOnClientError(): void
    {
        $this->indicesMock
            ->method('create')
            ->willThrowException(new \Elastic\Elasticsearch\Exception\ClientResponseException('index_already_exists'));

        $this->expectException(Exception::class);
        $this->elasticsearch->createIndex();
    }

    public function testPutMappingReturnsTrueWhenMappingsEmpty(): void
    {
        $responseMock = $this->createStub(ElasticsearchResponse::class);
        $responseMock->method('offsetExists')->willReturn(true);
        $responseMock->method('offsetGet')->willReturn(['mappings' => []]);

        $this->indicesMock->method('getMapping')->willReturn($responseMock);

        $this->indicesMock
            ->expects($this->once())
            ->method('putMapping')
            ->willReturn(['acknowledged' => true]);

        $result = $this->elasticsearch->putMapping();

        $this->assertTrue($result);
    }

    public function testPutMappingReturnsTrueWhenMappingsExist(): void
    {
        $responseMock = $this->createStub(ElasticsearchResponse::class);
        $responseMock->method('offsetExists')->willReturn(true);
        $responseMock
            ->method('offsetGet')
            ->willReturn([
                'mappings' => ['properties' => ['question' => ['type' => 'text']]],
            ]);

        $this->indicesMock->method('getMapping')->willReturn($responseMock);

        $this->indicesMock->expects($this->never())->method('putMapping');

        $result = $this->elasticsearch->putMapping();

        $this->assertTrue($result);
    }

    public function testDropIndexCallsClientDelete(): void
    {
        $responseMock = $this->createStub(ElasticsearchResponse::class);
        $responseMock->method('asObject')->willReturn((object) ['acknowledged' => true]);

        $this->indicesMock
            ->expects($this->once())
            ->method('delete')
            ->with(['index' => 'phpmyfaq_test'])
            ->willReturn($responseMock);

        $result = $this->elasticsearch->dropIndex();

        $this->assertIsObject($result);
    }

    public function testDropIndexThrowsExceptionOnError(): void
    {
        $this->indicesMock
            ->method('delete')
            ->willThrowException(new \Elastic\Elasticsearch\Exception\ClientResponseException('index_not_found'));

        $this->expectException(Exception::class);
        $this->elasticsearch->dropIndex();
    }

    // --- Document-level operation tests (use HTTP client mock) ---

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

        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode((string) $request->getBody(), true);
                return (
                    str_contains($request->getUri()->getPath(), '/_doc/42')
                    && $body['question'] === 'What is phpMyFAQ?'
                    && $body['answer'] === 'A FAQ tool'
                    && $body['content_type'] === 'faq'
                );
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['result' => 'created']));

        $result = $this->elasticsearch->index($faq);

        $this->assertIsObject($result);
        $this->assertSame('created', $result->result);
    }

    public function testIndexFaqReturnsNullOnError(): void
    {
        $loggerMock = $this->createStub(Logger::class);
        $this->configMock->method('getLogger')->willReturn($loggerMock);

        $faq = [
            'solution_id' => '42',
            'id' => '1',
            'lang' => 'en',
            'question' => 'Q',
            'answer' => 'A',
            'keywords' => '',
            'category_id' => '1',
        ];

        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(400, ['error' => 'bad request']));

        $result = $this->elasticsearch->index($faq);

        $this->assertNull($result);
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

        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode((string) $request->getBody(), true);
                return (
                    str_contains($request->getUri()->getPath(), '/_update/42')
                    && $body['doc']['answer'] === 'Updated answer'
                    && $body['doc']['content_type'] === 'faq'
                );
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['result' => 'updated']));

        $result = $this->elasticsearch->update($faq);

        $this->assertArrayHasKey('result', $result);
        $this->assertSame('updated', $result['result']);
    }

    public function testUpdateFaqReturnsErrorOnException(): void
    {
        $faq = [
            'solution_id' => '42',
            'id' => '1',
            'lang' => 'en',
            'question' => 'Q',
            'answer' => 'A',
            'keywords' => '',
            'category_id' => '1',
        ];

        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(404, ['error' => 'not found']));

        $result = $this->elasticsearch->update($faq);

        $this->assertArrayHasKey('error', $result);
    }

    public function testDeleteFaqSendsCorrectParams(): void
    {
        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                return $request->getMethod() === 'DELETE'
                && str_contains($request->getUri()->getPath(), '/phpmyfaq_test/_doc/42');
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['result' => 'deleted']));

        $result = $this->elasticsearch->delete(42);

        $this->assertArrayHasKey('result', $result);
        $this->assertSame('deleted', $result['result']);
    }

    public function testDeleteFaqReturnsErrorOnException(): void
    {
        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(404, ['error' => 'not found']));

        $result = $this->elasticsearch->delete(999);

        $this->assertArrayHasKey('error', $result);
    }

    public function testIsAvailableReturnsTrueOnPing(): void
    {
        $this->httpClientMock->method('sendRequest')->willReturn($this->createElasticsearchResponse(200, []));

        $this->assertTrue($this->elasticsearch->isAvailable());
    }

    public function testIsAvailableReturnsFalseOnError(): void
    {
        $loggerMock = $this->createStub(Logger::class);
        $this->configMock->method('getLogger')->willReturn($loggerMock);

        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(500, ['error' => 'unavailable']));

        $this->assertFalse($this->elasticsearch->isAvailable());
    }

    // --- Bulk index tests ---

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

        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = (string) $request->getBody();
                // NDJSON: should only contain solution_id 2, not 1
                return str_contains($body, '"_id":"2"') && !str_contains($body, '"_id":"1"');
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['errors' => false, 'items' => []]));

        $result = $this->elasticsearch->bulkIndex($faqs);

        $this->assertArrayHasKey('success', $result);
    }

    public function testBulkIndexReturnsSuccessOnOk(): void
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

        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(200, ['errors' => false]));

        $result = $this->elasticsearch->bulkIndex($faqs);

        $this->assertArrayHasKey('success', $result);
    }

    public function testBulkIndexReturnsErrorOnException(): void
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

        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(400, ['error' => 'bulk error']));

        $result = $this->elasticsearch->bulkIndex($faqs);

        $this->assertArrayHasKey('error', $result);
    }

    // --- Custom page tests ---

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

        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode((string) $request->getBody(), true);
                return (
                    str_contains($request->getUri()->getPath(), '/_doc/page_1_en')
                    && $body['content_type'] === 'page'
                    && $body['slug'] === 'about-us'
                    && $body['answer'] === 'AboutInfo'
                );
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['result' => 'created']));

        $result = $this->elasticsearch->indexCustomPage($page);

        $this->assertIsObject($result);
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

        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                return $request->getMethod() === 'DELETE'
                && str_contains($request->getUri()->getPath(), '/_doc/page_1_en');
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['result' => 'deleted']));

        $result = $this->elasticsearch->indexCustomPage($page);

        $this->assertNull($result);
    }

    public function testIndexCustomPageReturnsNullOnError(): void
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

        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(500, ['error' => 'server error']));

        $result = $this->elasticsearch->indexCustomPage($page);

        $this->assertNull($result);
    }

    public function testUpdateCustomPageWithActivePage(): void
    {
        $page = [
            'id' => 6,
            'lang' => 'de',
            'page_title' => 'Updated Page',
            'content' => '<p>Updated content</p>',
            'slug' => 'updated-page',
            'active' => 'y',
        ];

        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode((string) $request->getBody(), true);
                return (
                    str_contains($request->getUri()->getPath(), '/_update/page_6_de')
                    && $body['doc']['content_type'] === 'page'
                    && $body['doc']['answer'] === 'Updated content'
                );
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['result' => 'updated']));

        $result = $this->elasticsearch->updateCustomPage($page);

        $this->assertArrayHasKey('result', $result);
        $this->assertSame('updated', $result['result']);
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

        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                return $request->getMethod() === 'DELETE'
                && str_contains($request->getUri()->getPath(), '/_doc/page_3_en');
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['result' => 'deleted']));

        $this->elasticsearch->updateCustomPage($page);
    }

    public function testUpdateCustomPageWithDocumentMissingFallsBackToIndex(): void
    {
        $page = [
            'id' => 4,
            'lang' => 'en',
            'page_title' => 'New Page',
            'content' => 'content',
            'slug' => 'new-page',
            'active' => 'y',
        ];

        $callCount = 0;
        $this->httpClientMock
            ->method('sendRequest')
            ->willReturnCallback(function ($request) use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    // First call (update) returns 404 with document_missing_exception
                    return $this->createElasticsearchResponse(404, ['error' => [
                        'type' => 'document_missing_exception',
                    ]]);
                }
                // Second call (index fallback) returns 200
                return $this->createElasticsearchResponse(200, ['result' => 'created']);
            });

        $result = $this->elasticsearch->updateCustomPage($page);

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
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

        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(500, ['error' => 'server error']));

        $result = $this->elasticsearch->updateCustomPage($page);

        $this->assertArrayHasKey('error', $result);
    }

    public function testDeleteCustomPage(): void
    {
        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                return $request->getMethod() === 'DELETE'
                && str_contains($request->getUri()->getPath(), '/_doc/page_5_de');
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['result' => 'deleted']));

        $result = $this->elasticsearch->deleteCustomPage(5, 'de');

        $this->assertArrayHasKey('result', $result);
        $this->assertSame('deleted', $result['result']);
    }

    public function testDeleteCustomPageReturnsErrorOnException(): void
    {
        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(404, ['error' => 'not found']));

        $result = $this->elasticsearch->deleteCustomPage(99, 'en');

        $this->assertArrayHasKey('error', $result);
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
                'active' => 'n',
            ],
        ];

        $this->httpClientMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = (string) $request->getBody();
                return str_contains($body, 'page_1_en') && !str_contains($body, 'page_2_en');
            }))
            ->willReturn($this->createElasticsearchResponse(200, ['errors' => false]));

        $result = $this->elasticsearch->bulkIndexCustomPages($pages);

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
        $this->httpClientMock->expects($this->never())->method('sendRequest');

        $result = $this->elasticsearch->bulkIndexCustomPages($pages);

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function testBulkIndexCustomPagesReturnsErrorOnException(): void
    {
        $pages = [
            [
                'id' => 1,
                'lang' => 'en',
                'page_title' => 'Page',
                'content' => 'content',
                'slug' => 'page',
                'active' => 'y',
            ],
        ];

        $this->httpClientMock
            ->method('sendRequest')
            ->willReturn($this->createElasticsearchResponse(400, ['error' => 'bulk error']));

        $result = $this->elasticsearch->bulkIndexCustomPages($pages);

        $this->assertArrayHasKey('error', $result);
    }
}
