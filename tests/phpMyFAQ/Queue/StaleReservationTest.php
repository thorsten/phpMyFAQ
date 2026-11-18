<?php

namespace phpMyFAQ\Queue;

use DateTimeImmutable;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Json;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Queue\Message\SendMailMessage;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for jobs that stayed reserved forever after the worker ended while handling
 * them. They run against a real SQLite database, because the bug is in what the queries select.
 */
#[CoversClass(DatabaseTransport::class)]
#[CoversClass(Worker::class)]
#[UsesClass(Database::class)]
#[UsesClass(Json::class)]
#[UsesClass(SendMailMessage::class)]
#[UsesClass(Sqlite3::class)]
class StaleReservationTest extends TestCase
{
    private Sqlite3 $db;

    private DatabaseTransport $transport;

    private string $databaseFile;

    private string $logFile;

    private string|false $previousErrorLog;

    protected function setUp(): void
    {
        parent::setUp();

        Database::setTablePrefix('');

        $this->databaseFile = (string) tempnam(sys_get_temp_dir(), 'pmf-queue-stale-');
        $this->db = new Sqlite3();
        $this->db->connect($this->databaseFile, '', '');
        $this->db->query(
            'CREATE TABLE faqjobs (id INTEGER PRIMARY KEY AUTOINCREMENT, queue VARCHAR(100) NOT NULL, '
            . 'body TEXT NOT NULL, headers TEXT NULL, available_at TIMESTAMP NOT NULL, '
            . 'delivered_at TIMESTAMP NULL, created TIMESTAMP NOT NULL)',
        );

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($this->db);
        $this->transport = new DatabaseTransport($configuration);

        // The worker reports failed attempts through error_log(), which is not test output.
        $this->logFile = (string) tempnam(sys_get_temp_dir(), 'pmf-queue-stale-log-');
        $this->previousErrorLog = ini_set('error_log', $this->logFile);
    }

    protected function tearDown(): void
    {
        if ($this->previousErrorLog !== false) {
            ini_set('error_log', $this->previousErrorLog);
        }

        $this->db->close();
        @unlink($this->databaseFile);
        @unlink($this->logFile);

        parent::tearDown();
    }

    public function testInterruptedJobIsRetriedAfterTheReservationTimeout(): void
    {
        $jobId = $this->enqueueMail();
        $this->interruptWorkerWhileHandling($jobId, reservedSecondsAgo: 901);

        $handled = 0;
        $worker = $this->createWorker(static function () use (&$handled): void {
            ++$handled;
        });

        $this->assertSame(1, $worker->run());
        $this->assertSame(1, $handled);
        $this->assertSame(0, $this->countJobs(), 'The retried job has to be removed once it was handled.');
    }

    public function testJobReservedWithinTheTimeoutIsLeftToItsWorker(): void
    {
        $jobId = $this->enqueueMail();
        $this->interruptWorkerWhileHandling($jobId, reservedSecondsAgo: 899);

        $handled = 0;
        $worker = $this->createWorker(static function () use (&$handled): void {
            ++$handled;
        });

        $this->assertSame(0, $worker->run());
        $this->assertSame(0, $handled);
        $this->assertSame(1, $this->countJobs());
    }

    public function testInterruptedRunCountsAsAnAttempt(): void
    {
        $jobId = $this->enqueueMail();
        $this->interruptWorkerWhileHandling($jobId, reservedSecondsAgo: 901);

        $this->assertSame(1, $this->createWorker(static function (): void {})->releaseStaleReservations());

        $job = $this->transport->reserve();
        $this->assertNotNull($job);
        $this->assertSame($jobId, $job['id']);
        $this->assertSame(1, $job['headers']['attempts']);
    }

    public function testJobThatKeepsInterruptingTheWorkerIsDiscardedAtTheMaximumNumberOfAttempts(): void
    {
        $jobId = $this->enqueueMail(['attempts' => 2]);
        $this->interruptWorkerWhileHandling($jobId, reservedSecondsAgo: 901);

        $handled = 0;
        $worker = $this->createWorker(static function () use (&$handled): void {
            ++$handled;
        });

        $this->assertSame(0, $worker->run());
        $this->assertSame(0, $handled);
        $this->assertSame(0, $this->countJobs());

        $log = (string) file_get_contents($this->logFile);
        $this->assertStringContainsString(sprintf('Queue job %d (%s)', $jobId, SendMailMessage::class), $log);
        $this->assertStringContainsString('attempt 3 of 3, job discarded', $log);
    }

    public function testStaleReleaseLeavesAJobAloneThatWasReservedAgain(): void
    {
        $jobId = $this->enqueueMail();
        $this->assertNotNull($this->transport->reserve());

        // Another worker found the reservation stale, released it and reserved the job again
        // before this one got to release it: the cutoff is older than the new reservation.
        $staleCutoff = new DateTimeImmutable('-900 seconds');

        $this->assertFalse($this->transport->releaseStaleReservation($jobId, $staleCutoff, ['attempts' => 1]));
        $this->assertNull($this->transport->reserve(), 'The job must stay reserved by the other worker.');
    }

    /**
     * The Sqlite3 driver ends a result with an empty array instead of null, which reserve() took
     * for another row and looped on forever.
     */
    public function testReserveReturnsNullOnAnEmptyQueue(): void
    {
        $this->assertNull($this->transport->reserve());
    }

    public function testReservationTimeIsComparableWithTheStaleCutoff(): void
    {
        $this->enqueueMail();
        $this->assertNotNull($this->transport->reserve());

        $this->assertSame([], $this->transport->findStaleReservations(new DateTimeImmutable('-60 seconds')));
        $this->assertCount(1, $this->transport->findStaleReservations(new DateTimeImmutable('+60 seconds')));
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function enqueueMail(array $headers = []): int
    {
        $body = json_encode([
            'class' => SendMailMessage::class,
            'payload' => ['recipient' => 'user@example.org', 'subject' => 'Hello', 'body' => 'World'],
        ], JSON_THROW_ON_ERROR);

        return $this->transport->enqueue($body, $headers, 'mail', new DateTimeImmutable('-1 hour'));
    }

    /**
     * Leaves the job the way a worker does that reserved it and then ended inside the handler.
     */
    private function interruptWorkerWhileHandling(int $jobId, int $reservedSecondsAgo): void
    {
        $this->db->query(sprintf(
            "UPDATE faqjobs SET delivered_at = '%s' WHERE id = %d",
            new DateTimeImmutable(sprintf('-%d seconds', $reservedSecondsAgo))->format('Y-m-d H:i:s'),
            $jobId,
        ));
    }

    private function createWorker(callable $handler): Worker
    {
        $worker = new Worker($this->transport, reservationTimeout: 900);
        $worker->registerHandler(SendMailMessage::class, $handler);

        return $worker;
    }

    private function countJobs(): int
    {
        $row = $this->db->fetchArray($this->db->query('SELECT COUNT(*) AS jobs FROM faqjobs'));

        return (int) ($row['jobs'] ?? -1);
    }
}
