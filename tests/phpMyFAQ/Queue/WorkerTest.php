<?php

namespace phpMyFAQ\Queue;

use DateTimeImmutable;
use phpMyFAQ\Queue\Message\SendMailMessage;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Worker::class)]
#[UsesClass(SendMailMessage::class)]
class WorkerTest extends TestCase
{
    public function testRunOnceReturnsFalseWhenNoJobExists(): void
    {
        $transport = $this->createMock(DatabaseTransport::class);
        $transport->method('reserve')->willReturn(null);

        $worker = new Worker($transport);

        $this->assertFalse($worker->runOnce());
    }

    public function testRunOnceProcessesRegisteredHandlerAndAcknowledgesJob(): void
    {
        $transport = $this->createMock(DatabaseTransport::class);
        $transport
            ->method('reserve')
            ->willReturn([
                'id' => 5,
                'queue' => 'default',
                'body' => json_encode([
                    'class' => SendMailMessage::class,
                    'payload' => [
                        'recipient' => 'user@example.org',
                        'subject' => 'Hello',
                        'body' => 'World',
                    ],
                ], JSON_THROW_ON_ERROR),
                'headers' => [],
            ]);
        $transport->expects($this->once())->method('acknowledge')->with(5)->willReturn(true);
        $transport->expects($this->never())->method('release');

        $receivedMessage = null;
        $worker = new Worker($transport);
        $worker->registerHandler(SendMailMessage::class, static function (SendMailMessage $message) use (
            &$receivedMessage,
        ): void {
            $receivedMessage = $message;
        });

        $this->assertTrue($worker->runOnce());
        $this->assertInstanceOf(SendMailMessage::class, $receivedMessage);
        $this->assertSame('user@example.org', $receivedMessage->recipient);
    }

    public function testRunOnceReleasesJobWithIncrementedAttemptsWhenNoHandlerExists(): void
    {
        $transport = $this->createMock(DatabaseTransport::class);
        $transport
            ->method('reserve')
            ->willReturn([
                'id' => 8,
                'queue' => 'default',
                'body' => json_encode([
                    'class' => SendMailMessage::class,
                    'payload' => [
                        'recipient' => 'user@example.org',
                        'subject' => 'Hello',
                        'body' => 'World',
                    ],
                ], JSON_THROW_ON_ERROR),
                'headers' => [],
            ]);
        $transport
            ->expects($this->once())
            ->method('release')
            ->with(8, $this->isInstanceOf(DateTimeImmutable::class), $this->equalTo(['attempts' => 1]))
            ->willReturn(true);
        $transport->expects($this->never())->method('acknowledge');

        $worker = new Worker($transport);

        $this->assertTrue($worker->runOnce());
    }

    public function testRunOnceReleasesWithIncrementedAttemptsOnHandlerFailure(): void
    {
        $transport = $this->createMock(DatabaseTransport::class);
        $transport
            ->method('reserve')
            ->willReturn([
                'id' => 10,
                'queue' => 'default',
                'body' => json_encode([
                    'class' => SendMailMessage::class,
                    'payload' => [
                        'recipient' => 'user@example.org',
                        'subject' => 'Hello',
                        'body' => 'World',
                    ],
                ], JSON_THROW_ON_ERROR),
                'headers' => ['attempts' => 1],
            ]);
        $transport
            ->expects($this->once())
            ->method('release')
            ->with(10, $this->isInstanceOf(DateTimeImmutable::class), $this->equalTo(['attempts' => 2]))
            ->willReturn(true);
        $transport->expects($this->never())->method('acknowledge');

        $worker = new Worker($transport);
        $worker->registerHandler(SendMailMessage::class, static function (): void {
            throw new RuntimeException('Sending failed');
        });

        $this->assertTrue($worker->runOnce());
    }

    public function testRunContinuesAfterReserveThrows(): void
    {
        $this->expectOutputRegex('/Queue worker error in run\(\)/');

        $transport = $this->createMock(DatabaseTransport::class);
        $transport->method('reserve')->willReturnOnConsecutiveCalls(
            $this->throwException(new RuntimeException('DB connection lost')),
            [
                'id' => 20,
                'queue' => 'default',
                'body' => json_encode([
                    'class' => SendMailMessage::class,
                    'payload' => [
                        'recipient' => 'user@example.org',
                        'subject' => 'Hi',
                        'body' => 'Test',
                    ],
                ], JSON_THROW_ON_ERROR),
                'headers' => [],
            ],
            null,
        );
        $transport->expects($this->once())->method('acknowledge')->with(20)->willReturn(true);

        $worker = new Worker($transport);
        $worker->registerHandler(SendMailMessage::class, static function (): void {});

        $processed = $worker->run(3);
        $this->assertSame(1, $processed);
    }

    public function testRunOnceDeadLettersJobWhenMaxRetriesExceeded(): void
    {
        $transport = $this->createMock(DatabaseTransport::class);
        $transport
            ->method('reserve')
            ->willReturn([
                'id' => 12,
                'queue' => 'default',
                'body' => json_encode([
                    'class' => SendMailMessage::class,
                    'payload' => [
                        'recipient' => 'user@example.org',
                        'subject' => 'Hello',
                        'body' => 'World',
                    ],
                ], JSON_THROW_ON_ERROR),
                'headers' => ['attempts' => 2],
            ]);
        $transport->expects($this->once())->method('acknowledge')->with(12)->willReturn(true);
        $transport->expects($this->never())->method('release');

        $worker = new Worker($transport);
        $worker->registerHandler(SendMailMessage::class, static function (): void {
            throw new RuntimeException('Sending failed');
        });

        $this->assertTrue($worker->runOnce());
    }
}
