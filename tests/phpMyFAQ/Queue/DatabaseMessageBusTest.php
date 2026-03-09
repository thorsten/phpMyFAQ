<?php

namespace phpMyFAQ\Queue;

use phpMyFAQ\Queue\Message\SendMailMessage;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseMessageBus::class)]
#[UsesClass(SendMailMessage::class)]
class DatabaseMessageBusTest extends TestCase
{
    public function testDispatchEnqueuesSerializedMessage(): void
    {
        $transport = $this->createMock(DatabaseTransport::class);
        $transport
            ->expects($this->once())
            ->method('enqueue')
            ->with(
                $this->callback(static function (string $body): bool {
                    $decoded = json_decode($body, true);

                    return (
                        is_array($decoded)
                        && ($decoded['class'] ?? '')
                        === SendMailMessage::class
                        && ($decoded['payload']['recipient'] ?? '')
                        === 'team@example.org'
                    );
                }),
                ['x-request-id' => 'abc'],
                'mail',
            )
            ->willReturn(1001);

        $bus = new DatabaseMessageBus($transport);
        $id = $bus->dispatch(new SendMailMessage('team@example.org', 'Subject', 'Body'), 'mail', [
            'x-request-id' => 'abc',
        ]);

        $this->assertSame(1001, $id);
    }

    public function testDispatchSerializesPlainObjectPayload(): void
    {
        $transport = $this->createMock(DatabaseTransport::class);
        $transport
            ->expects($this->once())
            ->method('enqueue')
            ->with(
                $this->callback(static function (string $body): bool {
                    $decoded = json_decode($body, true);

                    return (
                        is_array($decoded)
                        && ($decoded['class'] ?? '')
                        === \stdClass::class
                        && ($decoded['payload']['foo'] ?? '')
                        === 'bar'
                    );
                }),
                [],
                'default',
            )
            ->willReturn(1002);

        $message = new \stdClass();
        $message->foo = 'bar';

        $bus = new DatabaseMessageBus($transport);

        $this->assertSame(1002, $bus->dispatch($message));
    }
}
