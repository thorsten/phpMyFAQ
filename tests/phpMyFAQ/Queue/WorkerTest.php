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

    public function testRunOnceReleasesJobAndThrowsWhenNoHandlerExists(): void
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
            ->with(8, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(true);
        $transport->expects($this->never())->method('acknowledge');

        $worker = new Worker($transport);

        $this->expectException(RuntimeException::class);
        $worker->runOnce();
    }
}
