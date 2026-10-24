<?php

namespace phpMyFAQ\Service\McpServer;

use Mcp\Server;
use Mcp\Server\Transport\InMemoryTransport;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Search;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AllowMockObjectsWithoutExpectations]
class McpSdkRuntimeTest extends TestCase
{
    public function testGetServerInfoReturnsInjectedPayload(): void
    {
        $runtime = new McpSdkRuntime(
            new FaqSearchTool(
                $this->createConfigurationMock(),
                $this->createMock(Search::class),
                $this->createMock(Faq::class),
            ),
            [
                'name' => 'phpMyFAQ MCP Server',
                'version' => '0.1.0-dev',
                'description' => 'Model Context Protocol server for phpMyFAQ installations',
                'capabilities' => ['tools' => true],
                'tools' => [['name' => 'faq_search', 'description' => 'Search through phpMyFAQ installations']],
            ],
        );

        $this->assertSame('phpMyFAQ MCP Server', $runtime->getServerInfo()['name']);
    }

    public function testFaqSearchReturnsDecodedJsonPayload(): void
    {
        $configuration = $this->createConfigurationMock();
        $search = $this->createMock(Search::class);
        $faq = $this->createMock(Faq::class);

        $faq->method('setUser');
        $faq->method('setGroups');
        $search->method('setCategory');
        $search->method('search')->willReturn([]);

        $runtime = new McpSdkRuntime(new FaqSearchTool($configuration, $search, $faq), [
            'name' => 'phpMyFAQ MCP Server',
            'version' => '0.1.0-dev',
            'description' => 'Model Context Protocol server for phpMyFAQ installations',
            'capabilities' => ['tools' => true],
            'tools' => [['name' => 'faq_search', 'description' => 'Search through phpMyFAQ installations']],
        ]);

        $result = $runtime->faqSearch('test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('total_found', $result);
    }

    public function testBuildServerReturnsMcpSdkServerInstance(): void
    {
        $runtime = new McpSdkRuntime(
            new FaqSearchTool(
                $this->createConfigurationMock(),
                $this->createMock(Search::class),
                $this->createMock(Faq::class),
            ),
            [
                'name' => 'phpMyFAQ MCP Server',
                'version' => '0.1.0-dev',
                'description' => 'Model Context Protocol server for phpMyFAQ installations',
                'capabilities' => ['tools' => true],
                'tools' => [['name' => 'faq_search', 'description' => 'Search through phpMyFAQ installations']],
            ],
        );

        $method = new ReflectionMethod($runtime, 'buildServer');
        $server = $method->invoke($runtime);

        $this->assertInstanceOf(\Mcp\Server::class, $server);
    }

    public function testRunConsoleMethodAcceptsConsoleInterfaces(): void
    {
        $runtime = new McpSdkRuntime(
            new FaqSearchTool(
                $this->createConfigurationMock(),
                $this->createMock(Search::class),
                $this->createMock(Faq::class),
            ),
            [
                'name' => 'phpMyFAQ MCP Server',
                'version' => '0.1.0-dev',
                'description' => 'Model Context Protocol server for phpMyFAQ installations',
                'capabilities' => ['tools' => true],
                'tools' => [['name' => 'faq_search', 'description' => 'Search through phpMyFAQ installations']],
            ],
        );

        $this->assertTrue(method_exists($runtime, 'runConsole'));
        $this->assertInstanceOf(InputInterface::class, $this->createMock(InputInterface::class));
        $this->assertInstanceOf(OutputInterface::class, $this->createMock(OutputInterface::class));
    }

    public function testImplementsMcpServerRuntimeInterface(): void
    {
        $runtime = new McpSdkRuntime(
            new FaqSearchTool(
                $this->createConfigurationMock(),
                $this->createMock(Search::class),
                $this->createMock(Faq::class),
            ),
            [],
        );

        $this->assertInstanceOf(McpServerRuntimeInterface::class, $runtime);
    }

    public function testGetServerInfoReturnsFullPayload(): void
    {
        $serverInfo = [
            'name' => 'phpMyFAQ MCP Server',
            'version' => '0.1.0-dev',
            'description' => 'Model Context Protocol server for phpMyFAQ installations',
            'capabilities' => ['tools' => true],
            'tools' => [['name' => 'faq_search', 'description' => 'Search through phpMyFAQ installations']],
        ];

        $runtime = new McpSdkRuntime(
            new FaqSearchTool(
                $this->createConfigurationMock(),
                $this->createMock(Search::class),
                $this->createMock(Faq::class),
            ),
            $serverInfo,
        );

        $this->assertSame($serverInfo, $runtime->getServerInfo());
    }

    public function testFaqSearchReturnsStringOnNonJsonContent(): void
    {
        $configuration = $this->createConfigurationMock();
        $search = $this->createMock(Search::class);
        $faq = $this->createMock(Faq::class);

        $search->method('search')->willThrowException(new \Exception('DB error'));

        $runtime = new McpSdkRuntime(new FaqSearchTool($configuration, $search, $faq), [
            'name' => 'phpMyFAQ MCP Server',
            'version' => '0.1.0-dev',
        ]);

        $result = $runtime->faqSearch('test');

        $this->assertIsString($result);
        $this->assertStringContainsString('Error searching FAQ database', $result);
    }

    public function testFaqSearchPassesParametersToTool(): void
    {
        $configuration = $this->createConfigurationMock();
        $search = $this->createMock(Search::class);
        $faq = $this->createMock(Faq::class);

        $search->expects($this->once())->method('setCategoryId')->with(3);
        $search->expects($this->once())->method('search')->with('install', true)->willReturn([]);

        $runtime = new McpSdkRuntime(new FaqSearchTool($configuration, $search, $faq), [
            'name' => 'phpMyFAQ MCP Server',
            'version' => '0.1.0-dev',
        ]);

        $result = $runtime->faqSearch('install', category_id: 3, limit: 5, all_languages: true);

        $this->assertIsArray($result);
        $this->assertSame(0, $result['total_found']);
    }

    public function testToolCallReturnsSearchResultsThatMatchTheOutputSchema(): void
    {
        $search = $this->createMock(Search::class);
        $search->method('search')->willReturn([
            (object) [
                'id' => 42,
                'lang' => 'en',
                'question' => 'How do I reset my password?',
                'answer' => 'Use the password reset form.',
                'category_id' => 3,
                'score' => 1.5,
            ],
        ]);

        $runtime = new McpSdkRuntime(
            new FaqSearchTool($this->createConfigurationMock(), $search, $this->createMock(Faq::class)),
            ['name' => 'phpMyFAQ MCP Server', 'version' => '0.1.0-dev', 'description' => 'phpMyFAQ'],
        );
        $server = new ReflectionMethod($runtime, 'buildServer')->invoke($runtime);
        self::assertInstanceOf(Server::class, $server);

        $transport = new class([
            '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25",'
            . '"capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}',
            '{"jsonrpc":"2.0","method":"notifications/initialized"}',
            '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"faq_search",'
            . '"arguments":{"query":"password"}}}',
        ]) extends InMemoryTransport {
            /** @var list<string> */
            public array $sent = [];

            public function send(string $data, array $context): void
            {
                $this->sent[] = $data;
                parent::send($data, $context);
            }
        };

        $server->run($transport);

        $response = null;
        foreach ($transport->sent as $message) {
            $decoded = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
            foreach (array_is_list($decoded) ? $decoded : [$decoded] as $item) {
                if (($item['id'] ?? null) === 2) {
                    $response = $item;
                }
            }
        }

        self::assertIsArray($response, 'No response to tools/call: ' . implode("\n", $transport->sent));
        self::assertArrayNotHasKey('error', $response);
        self::assertFalse($response['result']['isError'] ?? false);
        self::assertSame(1, $response['result']['structuredContent']['total_found']);
        self::assertSame(42, $response['result']['structuredContent']['results'][0]['id']);
    }

    private function createConfigurationMock(): Configuration
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://example.com/');
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        return $configuration;
    }
}
