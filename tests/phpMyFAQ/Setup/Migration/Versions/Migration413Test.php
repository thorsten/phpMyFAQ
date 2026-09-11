<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\Operations\ConfigUpdateOperation;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class Migration413Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new ReflectionClass(Database::class);
        $reflection->getProperty('dbType')->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }

    public function testMetadata(): void
    {
        $migration = new Migration413($this->createStub(Configuration::class));

        $this->assertSame('4.1.3', $migration->getVersion());
        $this->assertSame(['4.1.0-alpha.3'], $migration->getDependencies());
        $this->assertStringContainsString('API client token', $migration->getDescription());
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function emptyTokenProvider(): array
    {
        return [
            'empty string' => [''],
            'null' => [null],
        ];
    }

    /**
     * Installations upgraded with an empty api.apiClientToken would be locked out of the
     * REST API once an empty configured token is rejected, so the update has to re-key them.
     */
    #[DataProvider('emptyTokenProvider')]
    public function testSeedsRandomTokenWhenConfiguredTokenIsEmpty(mixed $storedToken): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->expects($this->once())
            ->method('get')
            ->with('api.apiClientToken')
            ->willReturn($storedToken);

        $recorder = new OperationRecorder($configuration);
        new Migration413($configuration)->up($recorder);

        $operations = $recorder->getOperationsByType('config_update');
        $this->assertCount(1, $operations);
        $this->assertSame(['config_update' => 1], $recorder->getOperationCounts());

        $operation = $operations[0];
        $this->assertInstanceOf(ConfigUpdateOperation::class, $operation);
        $this->assertSame('api.apiClientToken', $operation->getKey());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $operation->getValue());
    }

    public function testKeepsExistingTokenUntouched(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->expects($this->once())
            ->method('get')
            ->with('api.apiClientToken')
            ->willReturn('a3eae7f1e207ae8369be89142567d535');

        $recorder = new OperationRecorder($configuration);
        new Migration413($configuration)->up($recorder);

        $this->assertSame(0, $recorder->count());
    }

    public function testGeneratesAFreshTokenPerRun(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->expects($this->exactly(2))
            ->method('get')
            ->with('api.apiClientToken')
            ->willReturn('');

        $migration = new Migration413($configuration);

        $first = new OperationRecorder($configuration);
        $migration->up($first);
        $second = new OperationRecorder($configuration);
        $migration->up($second);

        $firstOperation = $first->getOperations()[0];
        $secondOperation = $second->getOperations()[0];
        $this->assertInstanceOf(ConfigUpdateOperation::class, $firstOperation);
        $this->assertInstanceOf(ConfigUpdateOperation::class, $secondOperation);
        $this->assertNotSame($firstOperation->getValue(), $secondOperation->getValue());
    }
}
