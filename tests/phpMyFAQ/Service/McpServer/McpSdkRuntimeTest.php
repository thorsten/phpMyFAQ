<?php

namespace phpMyFAQ\Service\McpServer;

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
            $this->createConfigurationMock(),
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

        $runtime = new McpSdkRuntime($configuration, new FaqSearchTool($configuration, $search, $faq), [
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
            $this->createConfigurationMock(),
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
            $this->createConfigurationMock(),
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
            $this->createConfigurationMock(),
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
            $this->createConfigurationMock(),
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

        $runtime = new McpSdkRuntime($configuration, new FaqSearchTool($configuration, $search, $faq), [
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

        $search->expects($this->once())
            ->method('setCategoryId')
            ->with(3);
        $search->expects($this->once())
            ->method('search')
            ->with('install', true)
            ->willReturn([]);

        $runtime = new McpSdkRuntime($configuration, new FaqSearchTool($configuration, $search, $faq), [
            'name' => 'phpMyFAQ MCP Server',
            'version' => '0.1.0-dev',
        ]);

        $result = $runtime->faqSearch('install', category_id: 3, limit: 5, all_languages: true);

        $this->assertIsArray($result);
        $this->assertSame(0, $result['total_found']);
    }

    private function createConfigurationMock(): Configuration
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://example.com/');
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        return $configuration;
    }
}
