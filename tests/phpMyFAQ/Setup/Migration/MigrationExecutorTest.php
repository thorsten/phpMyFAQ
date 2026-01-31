<?php

namespace phpMyFAQ\Setup\Migration;

use ArgumentCountError;
use Error;
use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Filesystem\Filesystem;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use TypeError;

#[AllowMockObjectsWithoutExpectations]
class MigrationExecutorTest extends TestCase
{
    private MigrationTracker $tracker;
    private MigrationExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $database = $this->createMock(DatabaseDriver::class);
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($database);

        $this->tracker = $this->createMock(MigrationTracker::class);
        $filesystem = $this->createMock(Filesystem::class);

        $this->executor = new MigrationExecutor($configuration, $this->tracker, $filesystem);
    }

    public function testSetDryRunReturnsSelf(): void
    {
        $result = $this->executor->setDryRun(true);

        $this->assertSame($this->executor, $result);
    }

    public function testIsDryRunDefaultsFalse(): void
    {
        $this->assertFalse($this->executor->isDryRun());
    }

    public function testSetAndGetDryRun(): void
    {
        $this->executor->setDryRun(true);
        $this->assertTrue($this->executor->isDryRun());

        $this->executor->setDryRun(false);
        $this->assertFalse($this->executor->isDryRun());
    }

    public function testGetResultsDefaultsEmpty(): void
    {
        $results = $this->executor->getResults();

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testClearResultsReturnsSelf(): void
    {
        $result = $this->executor->clearResults();

        $this->assertSame($this->executor, $result);
    }

    public function testExecuteMigrationReturnsMigrationResult(): void
    {
        $migration = $this->createMockMigration('4.0.0', 'Test migration');

        $this->executor->setDryRun(true);
        $result = $this->executor->executeMigration($migration);

        $this->assertInstanceOf(MigrationResult::class, $result);
        $this->assertEquals('4.0.0', $result->getVersion());
        $this->assertEquals('Test migration', $result->getDescription());
    }

    public function testExecuteMigrationInDryRunMode(): void
    {
        $migration = $this->createMockMigration('4.0.0', 'Test');

        $this->executor->setDryRun(true);
        $result = $this->executor->executeMigration($migration);

        $this->assertTrue($result->isDryRun());
        $this->assertTrue($result->isSuccess());
    }

    public function testExecuteMigrationTracksWhenNotDryRun(): void
    {
        $migration = $this->createMockMigration('4.0.0', 'Test');

        $this->tracker
            ->expects($this->once())
            ->method('recordMigration')
            ->with($this->equalTo('4.0.0'), $this->anything(), $this->anything(), $this->equalTo('Test'));

        $this->executor->setDryRun(false);
        $this->executor->executeMigration($migration);
    }

    public function testExecuteMigrationDoesNotTrackInDryRun(): void
    {
        $migration = $this->createMockMigration('4.0.0', 'Test');

        $this->tracker->expects($this->never())->method('recordMigration');

        $this->executor->setDryRun(true);
        $this->executor->executeMigration($migration);
    }

    public function testExecuteMigrationStoresResult(): void
    {
        $migration = $this->createMockMigration('4.0.0', 'Test');

        $this->executor->setDryRun(true);
        $this->executor->executeMigration($migration);

        $results = $this->executor->getResults();
        $this->assertCount(1, $results);
    }

    public function testExecuteMigrationsExecutesAllInDryRun(): void
    {
        $migration1 = $this->createMockMigration('4.0.0', 'First');
        $migration2 = $this->createMockMigration('4.1.0', 'Second');

        $this->executor->setDryRun(true);
        $results = $this->executor->executeMigrations([$migration1, $migration2]);

        $this->assertCount(2, $results);
        $this->assertEquals('4.0.0', $results[0]->getVersion());
        $this->assertEquals('4.1.0', $results[1]->getVersion());
    }

    public function testClearResultsRemovesStoredResults(): void
    {
        $migration = $this->createMockMigration('4.0.0', 'Test');

        $this->executor->setDryRun(true);
        $this->executor->executeMigration($migration);
        $this->assertCount(1, $this->executor->getResults());

        $this->executor->clearResults();
        $this->assertEmpty($this->executor->getResults());
    }

    public function testCollectOperationsReturnsStructuredData(): void
    {
        $migration = $this->createMockMigration('4.0.0', 'Test');

        $collected = $this->executor->collectOperations(['4.0.0' => $migration]);

        $this->assertArrayHasKey('4.0.0', $collected);
        $this->assertArrayHasKey('migration', $collected['4.0.0']);
        $this->assertArrayHasKey('operations', $collected['4.0.0']);
        $this->assertSame($migration, $collected['4.0.0']['migration']);
    }

    public function testGenerateDryRunReportStructure(): void
    {
        $migration = $this->createMockMigration('4.0.0', 'Test migration');

        $report = $this->executor->generateDryRunReport(['4.0.0' => $migration]);

        $this->assertArrayHasKey('migrations', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('4.0.0', $report['migrations']);
        $this->assertArrayHasKey('description', $report['migrations']['4.0.0']);
        $this->assertArrayHasKey('operationCount', $report['migrations']['4.0.0']);
        $this->assertArrayHasKey('operationsByType', $report['migrations']['4.0.0']);
        $this->assertArrayHasKey('operations', $report['migrations']['4.0.0']);
    }

    public function testGenerateDryRunReportSummary(): void
    {
        $migration1 = $this->createMockMigration('4.0.0', 'First');
        $migration2 = $this->createMockMigration('4.1.0', 'Second');

        $report = $this->executor->generateDryRunReport([
            '4.0.0' => $migration1,
            '4.1.0' => $migration2,
        ]);

        $this->assertEquals(2, $report['summary']['migrationCount']);
        $this->assertArrayHasKey('totalOperations', $report['summary']);
        $this->assertArrayHasKey('operationsByType', $report['summary']);
    }

    public function testFormatDryRunReportReturnsString(): void
    {
        $report = [
            'migrations' => [
                '4.0.0' => [
                    'description' => 'Test migration',
                    'operationCount' => 2,
                    'operationsByType' => ['sql' => 2],
                    'operations' => [
                        ['type' => 'sql', 'description' => 'SQL 1', 'query' => 'SELECT 1'],
                        ['type' => 'sql', 'description' => 'SQL 2', 'query' => 'SELECT 2'],
                    ],
                ],
            ],
            'summary' => [
                'migrationCount' => 1,
                'totalOperations' => 2,
                'operationsByType' => ['sql' => 2],
            ],
        ];

        $formatted = $this->executor->formatDryRunReport($report);

        $this->assertIsString($formatted);
        $this->assertStringContainsString('Migration Dry-Run Report', $formatted);
        $this->assertStringContainsString('4.0.0', $formatted);
        $this->assertStringContainsString('Test migration', $formatted);
        $this->assertStringContainsString('SQL Operations', $formatted);
        $this->assertStringContainsString('Summary', $formatted);
    }

    public function testFormatDryRunReportWithConfigOperations(): void
    {
        $report = [
            'migrations' => [
                '4.0.0' => [
                    'description' => 'Test',
                    'operationCount' => 1,
                    'operationsByType' => ['config_add' => 1],
                    'operations' => [
                        ['type' => 'config_add', 'description' => 'Add config'],
                    ],
                ],
            ],
            'summary' => [
                'migrationCount' => 1,
                'totalOperations' => 1,
                'operationsByType' => ['config_add' => 1],
            ],
        ];

        $formatted = $this->executor->formatDryRunReport($report);

        $this->assertStringContainsString('Configuration Changes', $formatted);
    }

    public function testFormatDryRunReportWithFileOperations(): void
    {
        $report = [
            'migrations' => [
                '4.0.0' => [
                    'description' => 'Test',
                    'operationCount' => 1,
                    'operationsByType' => ['file_copy' => 1],
                    'operations' => [
                        ['type' => 'file_copy', 'description' => 'Copy file'],
                    ],
                ],
            ],
            'summary' => [
                'migrationCount' => 1,
                'totalOperations' => 1,
                'operationsByType' => ['file_copy' => 1],
            ],
        ];

        $formatted = $this->executor->formatDryRunReport($report);

        $this->assertStringContainsString('File Operations', $formatted);
    }

    public function testFormatDryRunReportWithPermissionOperations(): void
    {
        $report = [
            'migrations' => [
                '4.0.0' => [
                    'description' => 'Test',
                    'operationCount' => 1,
                    'operationsByType' => ['permission_grant' => 1],
                    'operations' => [
                        ['type' => 'permission_grant', 'description' => 'Grant permission'],
                    ],
                ],
            ],
            'summary' => [
                'migrationCount' => 1,
                'totalOperations' => 1,
                'operationsByType' => ['permission_grant' => 1],
            ],
        ];

        $formatted = $this->executor->formatDryRunReport($report);

        $this->assertStringContainsString('Permission Changes', $formatted);
    }

    public function testExecuteMigrationCatchesException(): void
    {
        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('getVersion')->willReturn('4.0.0');
        $migration->method('getDescription')->willReturn('Test');
        $migration->method('up')->willThrowException(new Exception('Test exception'));

        $this->tracker->expects($this->never())->method('recordMigration');

        $this->executor->setDryRun(false);
        $result = $this->executor->executeMigration($migration);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Test exception', $result->getErrorMessage());
    }

    public function testExecuteMigrationCatchesError(): void
    {
        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('getVersion')->willReturn('4.0.0');
        $migration->method('getDescription')->willReturn('Test');
        $migration->method('up')->willThrowException(new TypeError('Type error occurred'));

        $this->tracker->expects($this->never())->method('recordMigration');

        $this->executor->setDryRun(false);
        $result = $this->executor->executeMigration($migration);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Type error occurred', $result->getErrorMessage());
    }

    public function testExecuteMigrationCatchesThrowable(): void
    {
        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('getVersion')->willReturn('4.0.0');
        $migration->method('getDescription')->willReturn('Test');
        $migration->method('up')->willThrowException(new Error('Fatal error'));

        $this->tracker->expects($this->never())->method('recordMigration');

        $this->executor->setDryRun(false);
        $result = $this->executor->executeMigration($migration);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Fatal error', $result->getErrorMessage());
    }

    public function testExecuteMigrationDoesNotTrackOnException(): void
    {
        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('getVersion')->willReturn('4.0.0');
        $migration->method('getDescription')->willReturn('Test');
        $migration->method('up')->willThrowException(new \RuntimeException('Runtime error'));

        $this->tracker->expects($this->never())->method('recordMigration');

        $this->executor->setDryRun(false);
        $this->executor->executeMigration($migration);
    }

    public function testExecuteMigrationDoesNotTrackOnError(): void
    {
        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('getVersion')->willReturn('4.0.0');
        $migration->method('getDescription')->willReturn('Test');
        $migration->method('up')->willThrowException(new ArgumentCountError('Argument count error'));

        $this->tracker->expects($this->never())->method('recordMigration');

        $this->executor->setDryRun(false);
        $this->executor->executeMigration($migration);
    }

    private function createMockMigration(string $version, string $description): MigrationInterface
    {
        $migration = $this->createMock(MigrationInterface::class);
        $migration->method('getVersion')->willReturn($version);
        $migration->method('getDescription')->willReturn($description);
        $migration
            ->method('up')
            ->willReturnCallback(function (OperationRecorder $recorder) {
                // Empty migration - no operations
            });

        return $migration;
    }
}
