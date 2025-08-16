<?php

namespace phpMyFAQ\Service\McpServer;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Search;
use Symfony\AI\McpSdk\Server\JsonRpcHandler;

class PhpMyFaqMcpServerTest extends TestCase
{
    private PhpMyFaqMcpServer $server;

    protected function setUp(): void
    {
        $configMock = $this->createMock(Configuration::class);
        $searchMock = $this->createMock(Search::class);
        $faqMock = $this->createMock(Faq::class);

        $configMock->method('getLogger')->willReturn($this->createMock(Logger::class));

        $this->server = new PhpMyFaqMcpServer(
            $configMock, $searchMock, $faqMock
        );
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
}
