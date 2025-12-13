<?php

namespace phpMyFAQ\Command;

use PHPUnit\Framework\TestCase;
use phpMyFAQ\Service\McpServer\PhpMyFaqMcpServer;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class McpServerCommandTest extends TestCase
{
    private PhpMyFaqMcpServer $serverMock;
    private McpServerCommand $command;

    protected function setUp(): void
    {
        $this->serverMock = $this->createMock(PhpMyFaqMcpServer::class);
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
        $input = $this->createStub(InputInterface::class);
        $output = $this->createStub(OutputInterface::class);

        $input->method('getOption')->with('info')->willReturn(true);

        $this->serverMock->expects($this->once())->method('getServerInfo')->willReturn([
            'name' => 'phpMyFAQ MCP Server',
            'version' => '0.1.0-dev',
            'description' => 'Test server',
            'capabilities' => ['tools' => true],
            'tools' => [['name' => 'faq_search', 'description' => 'Search tool']]
        ]);

        $result = $this->callExecute($input, $output);
        $this->assertSame(0, $result);
    }

    public function testExecuteRunsServerSuccessfully(): void
    {
        $input = $this->createStub(InputInterface::class);
        $output = $this->createStub(OutputInterface::class);

        $input->method('getOption')->with('info')->willReturn(false);

        $this->serverMock->expects($this->once())->method('runConsole')->with($input, $output);

        $result = $this->callExecute($input, $output);
        $this->assertSame(0, $result);
    }

    public function testExecuteHandlesException(): void
    {
        $input = $this->createStub(InputInterface::class);
        $output = $this->createStub(OutputInterface::class);

        $input->method('getOption')->with('info')->willReturn(false);

        $this->serverMock->method('runConsole')->willThrowException(new \Exception('fail'));

        $result = $this->callExecute($input, $output);
        $this->assertSame(1, $result);
    }
}
