<?php

namespace phpMyFAQ\Service\McpServer;

use Exception;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Search;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\AI\McpSdk\Server\JsonRpcHandler;

#[AllowMockObjectsWithoutExpectations]
class PhpMyFaqMcpServerTest extends TestCase
{
    private PhpMyFaqMcpServer $server;
    private Configuration $configMock;
    private Search $searchMock;
    private Faq $faqMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configuration::class);
        $languageMock = $this->createMock(Language::class);
        $this->searchMock = $this->createMock(Search::class);
        $this->faqMock = $this->createMock(Faq::class);

        $loggerMock = $this->createMock(Logger::class);
        $this->configMock->method('getLogger')->willReturn($loggerMock);
        $this->configMock->method('setLanguage')->with($languageMock);
        $this->configMock->method('getDefaultUrl')->willReturn('https://example.com');

        // Mock the configuration values needed by Language::setLanguage()
        $this->configMock
            ->method('get')
            ->willReturnMap([
                ['main.languageDetection', true],
                ['main.language',          'en'],
            ]);

        $this->server = new PhpMyFaqMcpServer($this->configMock, $languageMock, $this->searchMock, $this->faqMock);
    }

    public function testJsonRpcHandlerIsInitialized(): void
    {
        $handler = $this->server->getJsonRpcHandler();
        $this->assertInstanceOf(JsonRpcHandler::class, $handler);
    }

    public function testGetServerInfoReturnsExpectedArray(): void
    {
        $info = $this->server->getServerInfo();
        $this->assertIsArray($info);
        $this->assertSame('phpMyFAQ MCP Server', $info['name']);
        $this->assertSame('0.1.0-dev', $info['version']);
        $this->assertArrayHasKey('capabilities', $info);
        $this->assertTrue($info['capabilities']['tools']);
        $this->assertArrayHasKey('tools', $info);
        $this->assertSame('faq_search', $info['tools'][0]['name']);
    }

    /**
     * @throws Exception
     */
    public function testFaqSearchToolExecutorReturnsValidJsonFormat(): void
    {
        $executor = new FaqSearchToolExecutor($this->configMock, $this->searchMock, $this->faqMock);

        // Mock search results
        $searchResults = [
            (object) [
                'id' => 1,
                'lang' => 'en',
                'question' => 'Test question?',
                'answer' => 'Test answer',
                'category_id' => 1,
                'score' => 0.95,
            ],
        ];

        $this->searchMock->method('search')->willReturn($searchResults);
        $this->searchMock->expects($this->once())->method('setCategory');

        $toolCall = new ToolCall('test-id', 'faq_search', ['query' => 'test']);
        $result = $executor->call($toolCall);

        $this->assertSame('application/json', $result->mimeType);

        $jsonData = json_decode($result->result, true);
        $this->assertIsArray($jsonData);
        $this->assertArrayHasKey('results', $jsonData);
        $this->assertArrayHasKey('total_found', $jsonData);
        $this->assertIsArray($jsonData['results']);
        $this->assertIsInt($jsonData['total_found']);
    }

    public function testFaqSearchToolExecutorHandlesEmptyQuery(): void
    {
        $executor = new FaqSearchToolExecutor($this->configMock, $this->searchMock, $this->faqMock);

        $toolCall = new ToolCall('test-id', 'faq_search', ['query' => '']);
        $result = $executor->call($toolCall);

        $this->assertSame('application/json', $result->mimeType);
        $this->assertStringContainsString('Search query cannot be empty', $result->result);
    }

    public function testFaqSearchToolExecutorHandlesNoResults(): void
    {
        $executor = new FaqSearchToolExecutor($this->configMock, $this->searchMock, $this->faqMock);

        $this->searchMock->method('search')->willReturn([]);
        $this->searchMock->expects($this->once())->method('setCategory');

        $toolCall = new ToolCall('test-id', 'faq_search', ['query' => 'nonexistent']);
        $result = $executor->call($toolCall);

        $this->assertSame('application/json', $result->mimeType);

        $jsonData = json_decode($result->result, true);
        $this->assertArrayHasKey('results', $jsonData);
        $this->assertArrayHasKey('total_found', $jsonData);
        $this->assertEmpty($jsonData['results']);
        $this->assertSame(0, $jsonData['total_found']);
    }
}
