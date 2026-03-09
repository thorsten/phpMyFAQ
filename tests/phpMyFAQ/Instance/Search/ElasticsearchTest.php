<?php

declare(strict_types=1);

namespace phpMyFAQ\Instance\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class ElasticsearchTest extends TestCase
{
    private Elasticsearch $elasticsearch;
    private Indices $indicesMock;
    private ElasticsearchConfiguration $esConfigMock;
    private Configuration $configMock;

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
}
