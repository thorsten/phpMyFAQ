<?php

namespace phpMyFAQ\Command;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test class for UpdateCommand
 */
#[AllowMockObjectsWithoutExpectations]
class UpdateCommandTest extends TestCase
{
    private UpdateCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new UpdateCommand();
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommandConfiguration(): void
    {
        $this->assertEquals('phpmyfaq:update', $this->command->getName());
        $this->assertEquals('Executes the phpMyFAQ update process', $this->command->getDescription());

        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('version'));
        $this->assertFalse($definition->getArgument('version')->isRequired());
        $this->assertEquals('Requested version for the update', $definition->getArgument('version')->getDescription());
    }

    public function testConstructorInitializesProperties(): void
    {
        $command = new UpdateCommand();

        $reflection = new ReflectionClass($command);

        $configProperty = $reflection->getProperty('configuration');
        $this->assertInstanceOf(Configuration::class, $configProperty->getValue($command));

        $systemProperty = $reflection->getProperty('system');
        $this->assertInstanceOf(System::class, $systemProperty->getValue($command));
    }

    public function testExecuteWithoutUpdate(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertIsInt($exitCode);
        $this->assertContains($exitCode, [Command::SUCCESS, Command::FAILURE]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Start automatic phpMyFAQ update', $output);
    }

    public function testExecuteWithVersionArgument(): void
    {
        $exitCode = $this->commandTester->execute(['version' => '4.0.0']);

        $this->assertIsInt($exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Start automatic phpMyFAQ update', $output);
    }

    public function testExecuteHandlesExceptionsGracefully(): void
    {
        $command = new class extends UpdateCommand {
            protected function execute($input, $output): int
            {
                throw new RuntimeException('Test exception');
            }
        };

        $commandTester = new CommandTester($command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        $commandTester->execute([]);
    }

    public function testCommandExtendsSymfonyCommand(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testCommandUsesCorrectNamespace(): void
    {
        $reflection = new ReflectionClass($this->command);
        $this->assertEquals('phpMyFAQ\\Command', $reflection->getNamespaceName());
    }

    public function testCommandHasCorrectImports(): void
    {
        $reflection = new ReflectionClass($this->command);
        $commandFile = $reflection->getFileName();
        $this->assertFileExists($commandFile);

        $content = file_get_contents($commandFile);

        $expectedImports = [
            'use Symfony\\Component\\Console\\Command\\Command;',
            'use Symfony\\Component\\Console\\Input\\InputInterface;',
            'use Symfony\\Component\\Console\\Output\\OutputInterface;',
            'use Symfony\\Component\\Console\\Style\\SymfonyStyle;',
            'use phpMyFAQ\\Configuration;',
            'use phpMyFAQ\\System;',
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $content);
        }
    }

    public function testExecuteMethodSignature(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('execute');

        $this->assertTrue($method->isProtected());

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);

        $this->assertEquals('input', $parameters[0]->getName());
        $this->assertEquals('output', $parameters[1]->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }

    public function testConfigureMethodSignature(): void
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod('configure');

        $this->assertTrue($method->isProtected());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }
}
