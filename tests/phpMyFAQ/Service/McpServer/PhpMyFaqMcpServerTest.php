<?php

namespace phpMyFAQ\Service\McpServer;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Search;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AllowMockObjectsWithoutExpectations]
class PhpMyFaqMcpServerTest extends TestCase
{
    private PhpMyFaqMcpServer $server;
    private Configuration $configMock;
    private McpServerRuntimeInterface $runtimeMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configuration::class);
        $languageMock = $this->createMock(Language::class);
        $searchMock = $this->createMock(Search::class);
        $faqMock = $this->createMock(Faq::class);
        $this->runtimeMock = $this->createMock(McpServerRuntimeInterface::class);

        $loggerMock = $this->createMock(Logger::class);
        $this->configMock->method('getLogger')->willReturn($loggerMock);
        $this->configMock->method('setLanguage');

        $this->configMock
            ->method('get')
            ->willReturnMap([
                ['main.languageDetection', true],
                ['main.language',          'en'],
            ]);

        $this->server = new PhpMyFaqMcpServer(
            $this->configMock,
            $languageMock,
            $searchMock,
            $faqMock,
            $this->runtimeMock,
        );
    }

    public function testServerImplementsRuntimeInterface(): void
    {
        $this->assertInstanceOf(McpServerRuntimeInterface::class, $this->server);
    }

    public function testGetServerInfoReturnsExpectedArray(): void
    {
        $this->runtimeMock
            ->expects($this->once())
            ->method('getServerInfo')
            ->willReturn([
                'name' => 'phpMyFAQ MCP Server',
                'version' => '0.1.0-dev',
                'description' => 'Model Context Protocol server for phpMyFAQ installations',
                'capabilities' => ['tools' => true],
                'tools' => [['name' => 'faq_search', 'description' => 'Search through phpMyFAQ installations']],
            ]);

        $info = $this->server->getServerInfo();
        $this->assertIsArray($info);
        $this->assertSame('phpMyFAQ MCP Server', $info['name']);
        $this->assertSame('0.1.0-dev', $info['version']);
        $this->assertArrayHasKey('capabilities', $info);
        $this->assertTrue($info['capabilities']['tools']);
        $this->assertArrayHasKey('tools', $info);
        $this->assertSame('faq_search', $info['tools'][0]['name']);
    }

    public function testRunConsoleDelegatesToRuntime(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->runtimeMock
            ->expects($this->once())
            ->method('runConsole')
            ->with($input, $output);

        $this->server->runConsole($input, $output);
    }

    public function testConstructorWithLanguageDetectionDisabled(): void
    {
        $configMock = $this->createMock(Configuration::class);
        $languageMock = $this->createMock(Language::class);
        $searchMock = $this->createMock(Search::class);
        $faqMock = $this->createMock(Faq::class);
        $runtimeMock = $this->createMock(McpServerRuntimeInterface::class);

        $configMock->method('getLogger')->willReturn($this->createMock(Logger::class));
        $configMock->method('setLanguage');
        $configMock
            ->method('get')
            ->willReturnMap([
                ['main.languageDetection', false],
                ['main.language',          'de'],
            ]);

        $languageMock->expects($this->once())->method('setLanguageFromConfiguration')->with('de');
        $languageMock->expects($this->never())->method('setLanguageWithDetection');

        $server = new PhpMyFaqMcpServer($configMock, $languageMock, $searchMock, $faqMock, $runtimeMock);

        $this->assertInstanceOf(McpServerRuntimeInterface::class, $server);
    }

    public function testConstructorWithLanguageDetectionEnabled(): void
    {
        $configMock = $this->createMock(Configuration::class);
        $languageMock = $this->createMock(Language::class);
        $searchMock = $this->createMock(Search::class);
        $faqMock = $this->createMock(Faq::class);
        $runtimeMock = $this->createMock(McpServerRuntimeInterface::class);

        $configMock->method('getLogger')->willReturn($this->createMock(Logger::class));
        $configMock->method('setLanguage');
        $configMock
            ->method('get')
            ->willReturnMap([
                ['main.languageDetection', true],
                ['main.language',          'en'],
            ]);

        $languageMock->expects($this->once())->method('setLanguageWithDetection')->with('en');
        $languageMock->expects($this->never())->method('setLanguageFromConfiguration');

        $server = new PhpMyFaqMcpServer($configMock, $languageMock, $searchMock, $faqMock, $runtimeMock);

        $this->assertInstanceOf(McpServerRuntimeInterface::class, $server);
    }
}
