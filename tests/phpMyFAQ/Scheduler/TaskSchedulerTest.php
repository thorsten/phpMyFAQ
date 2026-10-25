<?php

namespace phpMyFAQ\Scheduler;

use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\Namespaces\IndicesNamespace;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Backup\BackupExportResult;
use phpMyFAQ\Administration\Session;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\Faq\Statistics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

#[CoversClass(TaskScheduler::class)]
#[UsesClass(BackupExportResult::class)]
class TaskSchedulerTest extends TestCase
{
    private string $backupDirectory;

    protected function setUp(): void
    {
        $this->backupDirectory = sys_get_temp_dir() . '/pmf-scheduler-backups-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->backupDirectory . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->backupDirectory)) {
            rmdir($this->backupDirectory);
        }
    }

    public function testCreateBackupWritesBackupContentToBackupDirectory(): void
    {
        $backup = $this->createStub(Backup::class);
        $backup->method('export')->willReturn(new BackupExportResult('phpmyfaq-data.sql', '-- sql --'));

        $scheduler = new TaskScheduler(
            $this->createConfigurationStub([]),
            $this->createStub(Session::class),
            $backup,
            $this->createStub(Statistics::class),
            $this->backupDirectory,
        );
        $result = $scheduler->createBackup();

        $backupFile = $this->backupDirectory . '/phpmyfaq-data.sql';
        $this->assertTrue($result['success']);
        $this->assertSame($backupFile, $result['filePath']);
        $this->assertSame('-- sql --', file_get_contents($backupFile));
        $this->assertSame(0o600, fileperms($backupFile) & 0o777);
    }

    public function testCreateBackupReturnsFailureWhenBackupDirectoryIsNotWritable(): void
    {
        file_put_contents($this->backupDirectory, 'not a directory');

        $backup = $this->createStub(Backup::class);
        $backup->method('export')->willReturn(new BackupExportResult('phpmyfaq-data.sql', '-- sql --'));

        $configuration = $this->createConfigurationStub([]);
        $configuration->method('getLogger')->willReturn($this->createStub(Logger::class));

        $scheduler = new TaskScheduler(
            $configuration,
            $this->createStub(Session::class),
            $backup,
            $this->createStub(Statistics::class),
            $this->backupDirectory,
        );
        $result = $scheduler->createBackup();

        unlink($this->backupDirectory);
        $this->assertFalse($result['success']);
        $this->assertNull($result['fileName']);
    }

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

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics, $this->backupDirectory);
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

        $session = $this->createStub(Session::class);
        $session->method('deleteSessions')->willThrowException(new RuntimeException('DB connection lost'));
        $session->method('getNumberOfSessions')->willReturn(5);

        $statistics = $this->createMock(Statistics::class);
        $statistics->expects($this->once())->method('totalFaqs')->willReturn(42);

        $backup = $this->createStub(Backup::class);
        $backup->method('export')->willReturn(new BackupExportResult('phpmyfaq-data.sql', '-- sql --'));

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics, $this->backupDirectory);
        $result = $scheduler->run();

        $this->assertIsArray($result['sessionCleanup']);
        $this->assertFalse($result['sessionCleanup']['success']);
        $this->assertIsArray($result['searchOptimization']);
        $this->assertTrue($result['searchOptimization']['skipped']);
        $this->assertSame(42, $result['statisticsAggregation']['totalFaqs']);
        $this->assertTrue($result['backupCreation']['success']);
    }

    public function testRunCatchesThrownTaskExceptionsAndReturnsNullResults(): void
    {
        $configuration = $this->createConfigurationStub([]);

        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->exactly(4))
            ->method('error')
            ->with(
                $this->callback(
                    static fn(string $message): bool => (
                        str_contains($message, 'Scheduled ') && str_contains($message, 'threw an exception')
                    ),
                ),
                $this->callback(static fn(array $context): bool => isset($context['message'], $context['trace'])),
            );
        $configuration->method('getLogger')->willReturn($logger);

        $session = $this->createStub(Session::class);
        $backup = $this->createStub(Backup::class);
        $statistics = $this->createStub(Statistics::class);

        $scheduler = new class($configuration, $session, $backup, $statistics) extends TaskScheduler {
            public function cleanupSessions(): array
            {
                throw new RuntimeException('cleanup boom');
            }

            public function optimizeSearchIndex(): array
            {
                throw new RuntimeException('optimize boom');
            }

            public function aggregateStatistics(): array
            {
                throw new RuntimeException('aggregate boom');
            }

            public function createBackup(): array
            {
                throw new RuntimeException('backup boom');
            }
        };

        $result = $scheduler->run();

        $this->assertNull($result['sessionCleanup']);
        $this->assertNull($result['searchOptimization']);
        $this->assertNull($result['statisticsAggregation']);
        $this->assertNull($result['backupCreation']);
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
            ->with(
                $this->stringContains('session cleanup threw an exception'),
                $this->callback(
                    static fn(array $context): bool => isset(
                        $context['message'],
                        $context['trace'],
                        $context['cutoffTimestamp'],
                        $context['retentionSeconds'],
                    ),
                ),
            );
        $logger->expects($this->once())->method('warning');
        $configuration->method('getLogger')->willReturn($logger);

        $session = $this->createStub(Session::class);
        $session->method('deleteSessions')->willThrowException(new RuntimeException('DB gone'));

        $backup = $this->createStub(Backup::class);
        $statistics = $this->createStub(Statistics::class);

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics, $this->backupDirectory);
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

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics, $this->backupDirectory);
        $result = $scheduler->optimizeSearchIndex();

        $this->assertFalse($result['success']);
        $this->assertFalse($result['skipped']);
        $this->assertFalse($result['elasticsearch']);
        $this->assertNull($result['opensearch']);
    }

    public function testOptimizeSearchIndexForceMergesElasticsearchIndex(): void
    {
        $httpClient = new class implements ClientInterface {
            /** @var list<RequestInterface> */
            public array $requests = [];

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->requests[] = $request;

                return new Response(
                    200,
                    ['X-Elastic-Product' => 'Elasticsearch', 'Content-Type' => 'application/json'],
                    '{"_shards":{"total":1,"successful":1,"failed":0}}',
                );
            }
        };
        $elasticsearch = ClientBuilder::create()
            ->setHosts(['http://localhost:9200'])
            ->setHttpClient($httpClient)
            ->build();

        $elasticsearchConfiguration = $this->createStub(ElasticsearchConfiguration::class);
        $elasticsearchConfiguration->method('getIndex')->willReturn('phpmyfaq-test');

        $configuration = $this->createConfigurationStub([
            'search.enableElasticsearch' => true,
            'search.enableOpenSearch' => false,
        ]);
        $configuration->method('getElasticsearch')->willReturn($elasticsearch);
        $configuration->method('getElasticsearchConfig')->willReturn($elasticsearchConfiguration);

        $scheduler = new TaskScheduler(
            $configuration,
            $this->createStub(Session::class),
            $this->createStub(Backup::class),
            $this->createStub(Statistics::class),
            $this->backupDirectory,
        );
        $result = $scheduler->optimizeSearchIndex();

        $this->assertTrue($result['success']);
        $this->assertTrue($result['elasticsearch']);
        $this->assertNull($result['opensearch']);
        $this->assertCount(1, $httpClient->requests);
        $this->assertSame('/phpmyfaq-test/_forcemerge', $httpClient->requests[0]->getUri()->getPath());
        $this->assertSame('max_num_segments=1', $httpClient->requests[0]->getUri()->getQuery());
    }

    public function testOptimizeSearchIndexForceMergesOpenSearchIndex(): void
    {
        $indices = $this->createMock(IndicesNamespace::class);
        $indices
            ->expects($this->once())
            ->method('forcemerge')
            ->with(['index' => 'phpmyfaq-test', 'max_num_segments' => 1])
            ->willReturn([]);

        $openSearch = $this->createStub(OpenSearchClient::class);
        $openSearch->method('indices')->willReturn($indices);

        $openSearchConfiguration = $this->createStub(OpenSearchConfiguration::class);
        $openSearchConfiguration->method('getIndex')->willReturn('phpmyfaq-test');

        $configuration = $this->createConfigurationStub([
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => true,
        ]);
        $configuration->method('getOpenSearch')->willReturn($openSearch);
        $configuration->method('getOpenSearchConfig')->willReturn($openSearchConfiguration);

        $scheduler = new TaskScheduler(
            $configuration,
            $this->createStub(Session::class),
            $this->createStub(Backup::class),
            $this->createStub(Statistics::class),
            $this->backupDirectory,
        );
        $result = $scheduler->optimizeSearchIndex();

        $this->assertTrue($result['success']);
        $this->assertNull($result['elasticsearch']);
        $this->assertTrue($result['opensearch']);
    }

    public function testOptimizeSearchIndexHandlesOpenSearchFailure(): void
    {
        $configuration = $this->createConfigurationStub([
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => true,
        ]);
        $configuration->method('getOpenSearch')->willThrowException(new RuntimeException('Missing client'));

        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('OpenSearch optimization failed'), $this->arrayHasKey('message'));
        $configuration->method('getLogger')->willReturn($logger);

        $scheduler = new TaskScheduler(
            $configuration,
            $this->createStub(Session::class),
            $this->createStub(Backup::class),
            $this->createStub(Statistics::class),
            $this->backupDirectory,
        );
        $result = $scheduler->optimizeSearchIndex();

        $this->assertFalse($result['success']);
        $this->assertNull($result['elasticsearch']);
        $this->assertFalse($result['opensearch']);
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

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics, $this->backupDirectory);
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

        $scheduler = new TaskScheduler($configuration, $session, $backup, $statistics, $this->backupDirectory);
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
