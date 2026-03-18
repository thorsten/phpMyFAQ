<?php

namespace phpMyFAQ\Service\McpServer;

use Exception;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Search;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class FaqSearchToolTest extends TestCase
{
    private Configuration $configMock;
    private Search $searchMock;
    private Faq $faqMock;
    private FaqSearchTool $tool;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configuration::class);
        $this->configMock->method('getDefaultUrl')->willReturn('https://example.com/');
        $this->configMock->method('getLogger')->willReturn($this->createMock(Logger::class));

        $this->searchMock = $this->createMock(Search::class);
        $this->faqMock = $this->createMock(Faq::class);

        $this->tool = new FaqSearchTool($this->configMock, $this->searchMock, $this->faqMock);
    }

    public function testImplementsMcpToolExecutorInterface(): void
    {
        $this->assertInstanceOf(McpToolExecutorInterface::class, $this->tool);
    }

    public function testGetDefinitionReturnsCorrectToolDefinition(): void
    {
        $definition = $this->tool->getDefinition();

        $this->assertInstanceOf(McpToolDefinition::class, $definition);
        $this->assertSame('faq_search', $definition->name);
        $this->assertSame('FAQ Search', $definition->title);
        $this->assertStringContainsString('Search through the phpMyFAQ knowledge base', $definition->description);
    }

    public function testGetDefinitionInputSchemaHasRequiredQueryProperty(): void
    {
        $definition = $this->tool->getDefinition();

        $this->assertSame('object', $definition->inputSchema['type']);
        $this->assertArrayHasKey('query', $definition->inputSchema['properties']);
        $this->assertSame(['query'], $definition->inputSchema['required']);
    }

    public function testGetDefinitionInputSchemaHasOptionalProperties(): void
    {
        $definition = $this->tool->getDefinition();
        $properties = $definition->inputSchema['properties'];

        $this->assertArrayHasKey('category_id', $properties);
        $this->assertSame('integer', $properties['category_id']['type']);

        $this->assertArrayHasKey('limit', $properties);
        $this->assertSame(10, $properties['limit']['default']);
        $this->assertSame(1, $properties['limit']['minimum']);
        $this->assertSame(50, $properties['limit']['maximum']);

        $this->assertArrayHasKey('all_languages', $properties);
        $this->assertSame('boolean', $properties['all_languages']['type']);
        $this->assertFalse($properties['all_languages']['default']);
    }

    public function testGetDefinitionHasOutputSchema(): void
    {
        $definition = $this->tool->getDefinition();

        $this->assertNotNull($definition->outputSchema);
        $this->assertArrayHasKey('results', $definition->outputSchema['properties']);
        $this->assertArrayHasKey('total_found', $definition->outputSchema['properties']);
    }

    public function testExecuteWithEmptyQueryReturnsError(): void
    {
        $result = $this->tool->execute(['query' => '']);

        $this->assertSame('Error: Search query cannot be empty.', $result['content']);
        $this->assertSame('text', $result['type']);
        $this->assertSame('application/json', $result['mimeType']);
    }

    public function testExecuteWithWhitespaceOnlyQueryReturnsError(): void
    {
        $result = $this->tool->execute(['query' => '   ']);

        $this->assertSame('Error: Search query cannot be empty.', $result['content']);
    }

    public function testExecuteWithMissingQueryReturnsError(): void
    {
        $result = $this->tool->execute([]);

        $this->assertSame('Error: Search query cannot be empty.', $result['content']);
    }

    public function testExecuteWithNoResultsReturnsEmptyJson(): void
    {
        $this->searchMock->method('search')->willReturn([]);

        $result = $this->tool->execute(['query' => 'nonexistent']);

        $decoded = json_decode($result['content'], true);
        $this->assertSame([], $decoded['results']);
        $this->assertSame(0, $decoded['total_found']);
    }

    public function testExecuteWithResultsReturnsFormattedJson(): void
    {
        $searchResult = new stdClass();
        $searchResult->id = 42;
        $searchResult->lang = 'en';
        $searchResult->question = 'How to install?';
        $searchResult->answer = 'Run the installer.';
        $searchResult->category_id = 5;
        $searchResult->score = 0.95;

        $this->searchMock->method('search')->willReturn([$searchResult]);

        $result = $this->tool->execute(['query' => 'install']);

        $decoded = json_decode($result['content'], true);
        $this->assertSame(1, $decoded['total_found']);
        $this->assertCount(1, $decoded['results']);

        $faq = $decoded['results'][0];
        $this->assertSame(42, $faq['id']);
        $this->assertSame('en', $faq['language']);
        $this->assertSame('How to install?', $faq['question']);
        $this->assertSame('Run the installer.', $faq['answer']);
        $this->assertSame(5, $faq['category_id']);
        $this->assertSame(0.95, $faq['relevance_score']);
        $this->assertSame('https://example.com/content/42/en', $faq['url']);
    }

    public function testExecuteWithCategoryIdSetsCategoryOnSearch(): void
    {
        $this->searchMock
            ->expects($this->once())
            ->method('setCategoryId')
            ->with(7);
        $this->searchMock->method('search')->willReturn([]);

        $this->tool->execute(['query' => 'test', 'category_id' => 7]);
    }

    public function testExecuteWithoutCategoryIdDoesNotSetCategoryId(): void
    {
        $this->searchMock->expects($this->never())->method('setCategoryId');
        $this->searchMock->method('search')->willReturn([]);

        $this->tool->execute(['query' => 'test']);
    }

    public function testExecuteRespectsLimitParameter(): void
    {
        $results = [];
        for ($i = 1; $i <= 5; $i++) {
            $obj = new stdClass();
            $obj->id = $i;
            $obj->lang = 'en';
            $obj->question = "Question $i";
            $obj->answer = "Answer $i";
            $obj->category_id = 1;
            $obj->score = 1.0 - ($i * 0.1);
            $results[] = $obj;
        }

        $this->searchMock->method('search')->willReturn($results);

        $result = $this->tool->execute(['query' => 'test', 'limit' => 2]);

        $decoded = json_decode($result['content'], true);
        $this->assertSame(2, $decoded['total_found']);
        $this->assertCount(2, $decoded['results']);
        $this->assertSame(1, $decoded['results'][0]['id']);
        $this->assertSame(2, $decoded['results'][1]['id']);
    }

    public function testExecutePassesAllLanguagesFlag(): void
    {
        $this->searchMock
            ->expects($this->once())
            ->method('search')
            ->with('test', true)
            ->willReturn([]);

        $this->tool->execute(['query' => 'test', 'all_languages' => true]);
    }

    public function testExecuteHandlesExceptionGracefully(): void
    {
        $this->searchMock->method('search')->willThrowException(new Exception('Database connection failed'));

        $result = $this->tool->execute(['query' => 'test']);

        $this->assertSame('Error searching FAQ database: Database connection failed', $result['content']);
        $this->assertSame('text', $result['type']);
        $this->assertSame('application/json', $result['mimeType']);
    }

    public function testExecuteHandlesResultsWithNullFields(): void
    {
        $searchResult = new stdClass();
        $searchResult->id = 1;
        $searchResult->lang = 'de';
        $searchResult->question = null;
        $searchResult->answer = null;
        $searchResult->category_id = null;
        $searchResult->score = null;

        $this->searchMock->method('search')->willReturn([$searchResult]);

        $result = $this->tool->execute(['query' => 'test']);

        $decoded = json_decode($result['content'], true);
        $this->assertSame(1, $decoded['total_found']);
        $faq = $decoded['results'][0];
        $this->assertSame('', $faq['question']);
        $this->assertSame('', $faq['answer']);
        $this->assertNull($faq['category_id']);
        $this->assertSame(0, $faq['relevance_score']);
    }

    public function testExecuteDefaultLimitIsTen(): void
    {
        $results = [];
        for ($i = 1; $i <= 15; $i++) {
            $obj = new stdClass();
            $obj->id = $i;
            $obj->lang = 'en';
            $obj->question = "Q$i";
            $obj->answer = "A$i";
            $obj->category_id = 1;
            $obj->score = 0.5;
            $results[] = $obj;
        }

        $this->searchMock->method('search')->willReturn($results);

        $result = $this->tool->execute(['query' => 'test']);

        $decoded = json_decode($result['content'], true);
        $this->assertSame(10, $decoded['total_found']);
        $this->assertCount(10, $decoded['results']);
    }

    public function testGetSearchReturnsSearchInstance(): void
    {
        $this->assertSame($this->searchMock, $this->tool->getSearch());
    }

    public function testGetFaqReturnsFaqInstance(): void
    {
        $this->assertSame($this->faqMock, $this->tool->getFaq());
    }

    public function testExecuteResultStructure(): void
    {
        $this->searchMock->method('search')->willReturn([]);

        $result = $this->tool->execute(['query' => 'test']);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('mimeType', $result);
        $this->assertSame('text', $result['type']);
        $this->assertSame('application/json', $result['mimeType']);
    }
}
