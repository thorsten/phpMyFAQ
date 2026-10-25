<?php

namespace phpMyFAQ\Command;

use phpMyFAQ\Service\McpServer\McpServerRuntimeInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

#[AllowMockObjectsWithoutExpectations]
class McpServerCommandTest extends TestCase
{
    private McpServerRuntimeInterface $serverMock;
    private McpServerCommand $command;

    protected function setUp(): void
    {
        $this->serverMock = $this->createMock(McpServerRuntimeInterface::class);
        $this->command = new McpServerCommand($this->serverMock);
    }

    private function callExecute($input, $output)
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('execute');
        return $method->invoke($this->command, $input, $output);
    }

    public function testExecuteWithInfoOptionShowsServerInfo(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->method('getOption')->willReturn(true);

        $this->serverMock
            ->expects($this->once())
            ->method('getServerInfo')
            ->willReturn([
                'name' => 'phpMyFAQ MCP Server',
                'version' => '0.1.0-dev',
                'description' => 'Test server',
                'capabilities' => ['tools' => true],
                'tools' => [['name' => 'faq_search', 'description' => 'Search tool']],
            ]);

        $result = $this->callExecute($input, $output);
        $this->assertSame(0, $result);
    }

    public function testExecuteWithInfoOptionSkipsToolEntriesThatAreNotArrays(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturn(true);
        $output = new StreamOutput(fopen('php://memory', 'w+'));

        $this->serverMock->method('getServerInfo')->willReturn([
            'name' => 'phpMyFAQ MCP Server',
            'version' => '0.1.0-dev',
            'description' => 'Test server',
            'capabilities' => ['tools' => true],
            'tools' => ['not-a-tool', ['name' => 'faq_search', 'description' => 'Search tool']],
        ]);

        $this->assertSame(0, $this->callExecute($input, $output));

        $rendered = $this->readStream($output->getStream());
        $this->assertStringContainsString('faq_search', $rendered);
        $this->assertStringNotContainsString('not-a-tool', $rendered);
    }

    public function testExecuteRunsServerSuccessfully(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->method('getOption')->willReturn(false);

        $this->serverMock->expects($this->once())->method('runConsole')->with($input, $output);

        $result = $this->callExecute($input, $output);
        $this->assertSame(0, $result);
    }

    public function testExecuteWritesBannerToStderrSoStdoutStaysProtocolOnly(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturn(false);
        $output = $this->createConsoleOutput();

        $result = $this->callExecute($input, $output);

        $this->assertSame(0, $result);
        $this->assertSame('', $this->readStream($output->getStream()));
        $this->assertStringContainsString('phpMyFAQ MCP Server', $this->readStream($output->getErrorOutput()->getStream()));
    }

    public function testExecuteWritesStartupErrorToStderr(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturn(false);
        $this->serverMock->method('runConsole')->willThrowException(new \Exception('fail'));
        $output = $this->createConsoleOutput();

        $result = $this->callExecute($input, $output);

        $this->assertSame(1, $result);
        $this->assertSame('', $this->readStream($output->getStream()));
        $this->assertStringContainsString('Failed to start MCP server: fail', $this->readStream($output->getErrorOutput()->getStream()));
    }

    private function createConsoleOutput(): StreamOutput&ConsoleOutputInterface
    {
        return new class(fopen('php://memory', 'w+')) extends StreamOutput implements ConsoleOutputInterface {
            private StreamOutput $stderr;

            public function __construct($stream)
            {
                parent::__construct($stream);
                $this->stderr = new StreamOutput(fopen('php://memory', 'w+'));
            }

            public function getErrorOutput(): StreamOutput
            {
                return $this->stderr;
            }

            public function setErrorOutput(OutputInterface $error): void
            {
            }

            public function section(): \Symfony\Component\Console\Output\ConsoleSectionOutput
            {
                throw new \LogicException('Not supported in tests');
            }
        };
    }

    private function readStream($stream): string
    {
        rewind($stream);
        return (string) stream_get_contents($stream);
    }

    public function testExecuteHandlesException(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $input->method('getOption')->willReturn(false);

        $this->serverMock->method('runConsole')->willThrowException(new \Exception('fail'));

        $result = $this->callExecute($input, $output);
        $this->assertSame(1, $result);
    }
}
