<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Setup\Migration\MigrationResult;
use phpMyFAQ\Setup\Migration\Operations\OperationInterface;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AllowMockObjectsWithoutExpectations]
class UpdateRunnerTest extends TestCase
{
    private function createRunner(): UpdateRunner
    {
        return new UpdateRunner($this->createMock(Configuration::class), new System());
    }

    public function testClassNamespaceAndConstruction(): void
    {
        $runner = $this->createRunner();

        $this->assertInstanceOf(UpdateRunner::class, $runner);

        $reflection = new ReflectionClass($runner);
        $this->assertEquals('phpMyFAQ\\Setup', $reflection->getNamespaceName());
    }

    public function testVersionPropertyExists(): void
    {
        $runner = $this->createRunner();

        $reflection = new ReflectionClass($runner);
        $this->assertTrue($reflection->hasProperty('version'));

        $property = $reflection->getProperty('version');
        $this->assertTrue($property->isPrivate());
        $type = $property->getType();
        $this->assertNotNull($type);
        $this->assertEquals('string', $type->getName());
    }

    public function testAllTaskMethodsExistAndArePrivate(): void
    {
        $runner = $this->createRunner();

        $reflection = new ReflectionClass($runner);

        $expectedMethods = [
            'taskHealthCheck',
            'taskUpdateCheck',
            'taskDownloadPackage',
            'taskExtractPackage',
            'taskCreateTemporaryBackup',
            'taskInstallPackage',
            'taskUpdateDatabase',
            'taskCleanup',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), sprintf('Method %s should exist', $methodName));
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPrivate(), sprintf('Method %s should be private', $methodName));

            $parameters = $method->getParameters();
            $this->assertCount(1, $parameters, sprintf('Method %s should have exactly one parameter', $methodName));
            $this->assertEquals(
                'symfonyStyle',
                $parameters[0]->getName(),
                sprintf('Parameter name of %s should be io', $methodName),
            );

            $returnType = $method->getReturnType();
            $this->assertNotNull($returnType, sprintf('Method %s should have a return type', $methodName));
            $this->assertEquals('int', $returnType->getName(), sprintf('Method %s should return int', $methodName));
        }
    }

    public function testRunMethodSignature(): void
    {
        $runner = $this->createRunner();

        $reflection = new ReflectionClass($runner);
        $this->assertTrue($reflection->hasMethod('run'));

        $method = $reflection->getMethod('run');
        $this->assertTrue($method->isPublic());

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('symfonyStyle', $parameters[0]->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }

    public function testFormatValueCoversDifferentTypes(): void
    {
        $runner = $this->createRunner();
        $reflection = new ReflectionClass($runner);
        $method = $reflection->getMethod('formatValue');

        $this->assertSame('true', $method->invoke($runner, true));
        $this->assertSame('false', $method->invoke($runner, false));
        $this->assertSame('null', $method->invoke($runner, null));
        $this->assertSame("'value'", $method->invoke($runner, 'value'));
        $this->assertSame('123', $method->invoke($runner, 123));
    }

    public function testTruncateStringShortensLongInput(): void
    {
        $runner = $this->createRunner();
        $reflection = new ReflectionClass($runner);
        $method = $reflection->getMethod('truncateString');

        $this->assertSame('short text', $method->invoke($runner, ' short   text ', 20));
        $this->assertSame('abcdefg...', $method->invoke($runner, 'abcdefghijklmno', 10));
    }

    public function testShortenPathRemovesRootPrefixAndTruncatesLongPath(): void
    {
        $runner = $this->createRunner();
        $reflection = new ReflectionClass($runner);
        $method = $reflection->getMethod('shortenPath');

        $this->assertSame('/content/core/config/database.php', $method->invoke(
            $runner,
            PMF_ROOT_DIR . '/content/core/config/database.php',
        ));

        $longPath = PMF_ROOT_DIR . str_repeat('/very-long-segment', 5) . '/file.txt';
        $result = $method->invoke($runner, $longPath);
        $this->assertStringStartsWith('...', $result);
        $this->assertStringEndsWith('/file.txt', $result);
    }

    public function testDisplayMigrationResultsNotesWhenNoResultsExist(): void
    {
        $runner = $this->createRunner();
        $reflection = new ReflectionClass($runner);
        $method = $reflection->getMethod('displayMigrationResults');
        $style = $this->createMock(SymfonyStyle::class);

        $style->expects($this->once())->method('note')->with('No migrations were applied.');

        $method->invoke($runner, $style, []);
    }

    public function testDisplayMigrationResultsRendersTable(): void
    {
        $runner = $this->createRunner();
        $reflection = new ReflectionClass($runner);
        $method = $reflection->getMethod('displayMigrationResults');
        $style = $this->createMock(SymfonyStyle::class);

        $result = new MigrationResult('4.2.0-alpha', 'Example migration');
        $operation = $this->createMock(OperationInterface::class);
        $operation->method('getType')->willReturn('sql');
        $operation->method('getDescription')->willReturn('Example operation');
        $result->setExecutionTimeMs(12.34);
        $result->addOperationResult($operation, true);
        $result->addOperationResult($operation, true);
        $result->addOperationResult($operation, true);

        $style->expects($this->once())
            ->method('table')
            ->with(
                ['Version', 'Description', 'Operations', 'Time', 'Status'],
                $this->callback(static fn(array $rows): bool => $rows[0][0] === '4.2.0-alpha'
                    && $rows[0][1] === 'Example migration'
                    && $rows[0][2] === 3),
            );

        $method->invoke($runner, $style, [$result]);
    }
}
