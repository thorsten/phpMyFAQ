<?php

namespace phpMyFAQ\Queue\Transport;

use DateTimeImmutable;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseTransport::class)]
#[UsesClass(Database::class)]
class DatabaseTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Database::setTablePrefix('');
    }

    public function testEnqueueCreatesJobAndReturnsId(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db->method('nextId')->willReturn(42);
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
            ->method('fetchArray')
            ->with('result-set')
            ->willReturnOnConsecutiveCalls([
                'id' => 7,
                'queue' => 'default',
                'body' => '{"class":"X"}',
                'headers' => '{"attempt":1}',
                'available_at' => date('Y-m-d H:i:s', time() - 5),
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
}
