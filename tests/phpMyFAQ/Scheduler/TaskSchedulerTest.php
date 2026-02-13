<?php

namespace phpMyFAQ\Scheduler;

use Monolog\Logger;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Backup\BackupExportResult;
use phpMyFAQ\Administration\Session;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Faq\Statistics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(TaskScheduler::class)]
#[UsesClass(BackupExportResult::class)]
class TaskSchedulerTest extends TestCase
{
    public function testRunExecutesTasksAndReturnsSummary(): void
    {
        $configuration = $this->createConfigurationStub([
            'session.scheduler.retentionSeconds' => 3600,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
        ]);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('deleteSessions')->with(0, $this->greaterThan(0))->willReturn(true);
        $session->expects($this->once())->method('getNumberOfSessions')->willReturn(12);

        $statistics = $this->createMock(Statistics::class);
        $statistics->expects($this->once())->method('totalFaqs')->willReturn(99);

        $backup = $this->createStub(Backup::class);
        $backup->method('export')->willReturn(new BackupExportResult('phpmyfaq-data.sql', '-- sql --'));

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics);
        $result = $scheduler->run();

        $this->assertTrue($result['sessionCleanup']['success']);
        $this->assertTrue($result['searchOptimization']['skipped']);
        $this->assertFalse($result['searchOptimization']['success']);
        $this->assertSame(99, $result['statisticsAggregation']['totalFaqs']);
        $this->assertSame(12, $result['statisticsAggregation']['totalSessions']);
        $this->assertTrue($result['backupCreation']['success']);
        $this->assertSame('phpmyfaq-data.sql', $result['backupCreation']['fileName']);
    }

    public function testRunIsolatesTaskFailuresAndContinuesRemaining(): void
    {
        $configuration = $this->createConfigurationStub([
            'session.scheduler.retentionSeconds' => 3600,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
        ]);

        $logger = $this->createStub(Logger::class);
        $configuration->method('getLogger')->willReturn($logger);

        $session = $this->createMock(Session::class);
        $session->method('deleteSessions')->willThrowException(new RuntimeException('DB connection lost'));
        $session->method('getNumberOfSessions')->willReturn(5);

        $statistics = $this->createMock(Statistics::class);
        $statistics->expects($this->once())->method('totalFaqs')->willReturn(42);

        $backup = $this->createStub(Backup::class);
        $backup->method('export')->willReturn(new BackupExportResult('phpmyfaq-data.sql', '-- sql --'));

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics);
        $result = $scheduler->run();

        $this->assertIsArray($result['sessionCleanup']);
        $this->assertFalse($result['sessionCleanup']['success']);
        $this->assertIsArray($result['searchOptimization']);
        $this->assertTrue($result['searchOptimization']['skipped']);
        $this->assertSame(42, $result['statisticsAggregation']['totalFaqs']);
        $this->assertTrue($result['backupCreation']['success']);
    }

    public function testCleanupSessionsReturnsFailureWhenDeleteSessionsThrows(): void
    {
        $configuration = $this->createConfigurationStub([
            'session.scheduler.retentionSeconds' => 7200,
        ]);

        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('session cleanup threw an exception'), $this->callback(
                static fn(array $context): bool => isset($context['message'], $context['trace'], $context['cutoffTimestamp'], $context['retentionSeconds'])
            ));
        $logger->expects($this->once())->method('warning');
        $configuration->method('getLogger')->willReturn($logger);

        $session = $this->createStub(Session::class);
        $session->method('deleteSessions')->willThrowException(new RuntimeException('DB gone'));

        $backup = $this->createStub(Backup::class);
        $statistics = $this->createStub(Statistics::class);

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics);
        $result = $scheduler->cleanupSessions();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('cutoffTimestamp', $result);
        $this->assertSame(7200, $result['retentionSeconds']);
    }

    public function testOptimizeSearchIndexHandlesElasticsearchFailure(): void
    {
        $configuration = $this->createConfigurationStub([
            'search.enableElasticsearch' => true,
            'search.enableOpenSearch' => false,
        ]);
        $configuration->method('getElasticsearch')->willThrowException(new RuntimeException('Missing client'));

        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Elasticsearch optimization failed'), $this->arrayHasKey('message'));
        $configuration->method('getLogger')->willReturn($logger);

        $session = $this->createStub(Session::class);
        $backup = $this->createStub(Backup::class);
        $statistics = $this->createStub(Statistics::class);

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics);
        $result = $scheduler->optimizeSearchIndex();

        $this->assertFalse($result['success']);
        $this->assertFalse($result['skipped']);
        $this->assertFalse($result['elasticsearch']);
        $this->assertNull($result['opensearch']);
    }

    public function testAggregateStatisticsReturnsFailureWhenExceptionThrown(): void
    {
        $configuration = $this->createConfigurationStub([]);

        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('statistics aggregation failed'), $this->arrayHasKey('message'));
        $configuration->method('getLogger')->willReturn($logger);

        $session = $this->createStub(Session::class);
        $backup = $this->createStub(Backup::class);
        $statistics = $this->createStub(Statistics::class);
        $statistics->method('totalFaqs')->willThrowException(new RuntimeException('Query failed'));

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics);
        $result = $scheduler->aggregateStatistics();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('generatedAt', $result);
        $this->assertNull($result['totalFaqs']);
        $this->assertNull($result['totalSessions']);
        $this->assertSame('Query failed', $result['error']);
    }

    public function testCreateBackupReturnsFailureWhenExportThrows(): void
    {
        $configuration = $this->createConfigurationStub([]);

        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('backup creation failed'), $this->arrayHasKey('message'));
        $configuration->method('getLogger')->willReturn($logger);

        $session = $this->createStub(Session::class);
        $statistics = $this->createStub(Statistics::class);
        $backup = $this->createStub(Backup::class);
        $backup->method('export')->willThrowException(new RuntimeException('Cannot export backup'));

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics);
        $result = $scheduler->createBackup();

        $this->assertFalse($result['success']);
        $this->assertNull($result['fileName']);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createConfigurationStub(array $values): Configuration
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $values[$item] ?? null);

        return $configuration;
    }
}
