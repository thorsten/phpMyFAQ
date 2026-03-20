<?php

namespace phpMyFAQ\Queue\Transport;

use DateTimeImmutable;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(DatabaseTransport::class)]
#[UsesClass(Database::class)]
class DatabaseTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Database::setTablePrefix('');
    }

    /**
     * @throws \JsonException
     */
    public function testEnqueueCreatesJobAndReturnsId(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('lastInsertId')->willReturn(42);
        $db->method('escape')->willReturnArgument(0);
        $db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('INSERT INTO pmf_faqjobs'))
            ->willReturn(true);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);
        $id = $transport->enqueue('{"class":"Test"}', ['foo' => 'bar'], 'default', new DateTimeImmutable());

        $this->assertSame(42, $id);
    }

    public function testReserveReturnsDueJobAndDecodesHeaders(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $db->method('query')->willReturnCallback(static function (string $query): mixed {
            if (str_starts_with($query, 'SELECT')) {
                return 'result-set';
            }

            if (str_starts_with($query, 'UPDATE')) {
                return true;
            }

            return false;
        });
        $db->method('affectedRows')->willReturn(1);
        $db
            ->expects($this->atLeastOnce())
            ->method('fetchArray')
            ->with('result-set')
            ->willReturnOnConsecutiveCalls([
                'id' => 7,
                'queue' => 'default',
                'body' => '{"class":"X"}',
                'headers' => '{"attempt":1}',
            ], false);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);
        $job = $transport->reserve('default');

        $this->assertNotNull($job);
        $this->assertSame(7, $job['id']);
        $this->assertSame(['attempt' => 1], $job['headers']);
    }

    public function testAcknowledgeDeletesJob(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->once())->method('query')->with('DELETE FROM pmf_faqjobs WHERE id = 99')->willReturn(true);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);
        $this->assertTrue($transport->acknowledge(99));
    }

    public function testEnqueueThrowsWhenInsertFails(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $db->method('error')->willReturn('insert failed');
        $db->method('query')->willReturn(false);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to enqueue job: insert failed');
        $transport->enqueue('body');
    }

    public function testReserveThrowsWhenSelectFails(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db->method('error')->willReturn('select failed');
        $db->method('query')->willReturn(false);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to fetch queued jobs: select failed');
        $transport->reserve('default');
    }

    public function testReserveSkipsJobsThatCannotBeMarkedDelivered(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $db->method('query')->willReturnCallback(static function (string $query): mixed {
            if (str_starts_with($query, 'SELECT')) {
                return 'result-set';
            }

            if (str_starts_with($query, 'UPDATE') && str_contains($query, 'id = 1')) {
                return false;
            }

            if (str_starts_with($query, 'UPDATE') && str_contains($query, 'id = 2')) {
                return true;
            }

            return false;
        });
        $db->method('affectedRows')->willReturn(1);
        $db->method('fetchArray')->willReturnOnConsecutiveCalls(
            ['id' => 1, 'queue' => 'default', 'body' => 'body-1', 'headers' => ''],
            ['id' => 2, 'queue' => 'default', 'body' => 'body-2', 'headers' => 'not-json'],
            false,
        );

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);
        $job = $transport->reserve('default');

        $this->assertNotNull($job);
        $this->assertSame(2, $job['id']);
        $this->assertSame([], $job['headers']);
    }

    public function testReserveSkipsJobsWithAffectedRowsMismatch(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $db->method('query')->willReturn(true);
        $db->method('affectedRows')->willReturnOnConsecutiveCalls(0, 1);
        $db->method('fetchArray')->willReturnOnConsecutiveCalls(
            ['id' => 3, 'queue' => 'default', 'body' => 'body-3', 'headers' => '{"a":1}'],
            ['id' => 4, 'queue' => 'default', 'body' => 'body-4', 'headers' => '{"b":2}'],
            false,
        );

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);
        $job = $transport->reserve('default');

        $this->assertNotNull($job);
        $this->assertSame(4, $job['id']);
        $this->assertSame(['b' => 2], $job['headers']);
    }

    public function testReleaseUpdatesJobWithoutHeaders(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(static fn(string $query): bool => !str_contains($query, 'headers =')))
            ->willReturn(true);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);

        $this->assertTrue($transport->release(12, new DateTimeImmutable('2026-03-09 12:00:00')));
    }

    public function testReleaseUpdatesJobWithHeaders(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(static fn(string $query): bool => str_contains($query, 'headers =')))
            ->willReturn(true);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $transport = new DatabaseTransport($configuration);

        $this->assertTrue($transport->release(13, new DateTimeImmutable('2026-03-09 12:00:00'), ['attempts' => 2]));
    }
}
