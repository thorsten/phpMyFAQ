<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Chat;
use phpMyFAQ\Entity\ChatMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[CoversClass(ChatSseController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ChatSseControllerTest extends ApiControllerTestCase
{
    private function upsertUserData(int $userId, string $displayName): void
    {
        $query = sprintf(
            "INSERT OR REPLACE INTO faquserdata (user_id, last_modified, display_name, email, twofactor_enabled, secret, is_visible)
             VALUES (%d, '20260302100000', '%s', '%s@example.com', 0, '', 1)",
            $userId,
            $this->configuration->getDb()->escape($displayName),
            strtolower($this->configuration->getDb()->escape($displayName)),
        );

        $this->configuration->getDb()->query($query);
    }

    public function testStreamReturnsSseResponseHeaders(): void
    {
        $controller = new ChatSseController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->stream(Request::create('/chat/stream?lastId=5', 'GET'));

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/event-stream', $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        self::assertSame('keep-alive', $response->headers->get('Connection'));
    }

    public function testStreamCallbackOutputsMessagesAndReconnectEvent(): void
    {
        $this->upsertUserData(2, 'Partner User');

        $message = new ChatMessage()
            ->setId(7)
            ->setSenderId(2)
            ->setRecipientId(1)
            ->setMessage('Hello from SSE')
            ->setIsRead(false)
            ->setCreatedAt(new \DateTimeImmutable('2026-03-02T10:00:00+00:00'));

        $chat = $this->createMock(Chat::class);
        $chat->expects($this->once())->method('getNewMessages')->with(1, 5)->willReturn([$message]);
        $chat
            ->expects($this->once())
            ->method('messagesToArray')
            ->with([$message])
            ->willReturn([[
                'id' => 7,
                'senderId' => 2,
                'senderName' => 'Partner User',
                'recipientId' => 1,
                'message' => 'Hello from SSE',
                'isRead' => false,
                'createdAt' => '2026-03-02T10:00:00+00:00',
            ]]);

        $timeValues = [0, 0, 0, 31];
        $output = '';
        $controller = new ChatSseController(
            static fn(): Chat => $chat,
            static function (int $seconds): void {},
            static function () use (&$timeValues): int {
                return array_shift($timeValues) ?? 31;
            },
            static fn(): bool => false,
            static function (string $header): void {},
            static function (): void {},
            static function (): void {},
            static function (string $chunk) use (&$output): void {
                $output .= $chunk;
            },
            15,
            30,
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->stream(Request::create('/chat/stream?lastId=5', 'GET'));

        $response->getCallback()();

        self::assertStringContainsString('Hello from SSE', $output);
        self::assertStringContainsString('event: reconnect', $output);
        self::assertStringContainsString('"lastId": 7', $output);
    }

    public function testStreamCallbackOutputsReconnectEventWithoutMessages(): void
    {
        $chat = $this->createMock(Chat::class);
        $chat->expects($this->once())->method('getNewMessages')->with(1, 0)->willReturn([]);
        $chat->expects($this->never())->method('messagesToArray');

        $timeValues = [0, 0, 0, 31];
        $output = '';
        $controller = new ChatSseController(
            static fn(): Chat => $chat,
            static function (int $seconds): void {},
            static function () use (&$timeValues): int {
                return array_shift($timeValues) ?? 31;
            },
            static fn(): bool => false,
            static function (string $header): void {},
            static function (): void {},
            static function (): void {},
            static function (string $chunk) use (&$output): void {
                $output .= $chunk;
            },
            15,
            30,
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->stream(Request::create('/chat/stream', 'GET'));

        $response->getCallback()();

        self::assertStringContainsString('event: reconnect', $output);
        self::assertStringContainsString('"lastId": 0', $output);
    }

    public function testStreamCallbackEmitsHeartbeatBeforeConnectionAbort(): void
    {
        $chat = $this->createMock(Chat::class);
        $chat->expects($this->once())->method('getNewMessages')->with(1, 0)->willReturn([]);
        $chat->expects($this->never())->method('messagesToArray');

        $timeValues = [0, 0, 16];
        $output = '';
        $controller = new ChatSseController(
            static fn(): Chat => $chat,
            static function (int $seconds): void {},
            static function () use (&$timeValues): int {
                return array_shift($timeValues) ?? 16;
            },
            static fn(): bool => true,
            static function (string $header): void {},
            static function (): void {},
            static function (): void {},
            static function (string $chunk) use (&$output): void {
                $output .= $chunk;
            },
            15,
            30,
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->stream(Request::create('/chat/stream', 'GET'));

        $response->getCallback()();

        self::assertStringContainsString(': heartbeat', $output);
        self::assertStringNotContainsString('event: reconnect', $output);
    }

    public function testStreamTreatsInvalidLastIdAsZero(): void
    {
        $chat = $this->createMock(Chat::class);
        $chat->expects($this->once())->method('getNewMessages')->with(1, 0)->willReturn([]);
        $chat->expects($this->never())->method('messagesToArray');

        $controller = new ChatSseController(
            static fn(): Chat => $chat,
            static function (int $seconds): void {},
            static fn(): int => 0,
            static fn(): bool => true,
            static function (string $header): void {},
            static function (): void {},
            static function (): void {},
            static function (string $chunk): void {},
            15,
            30,
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->stream(Request::create('/chat/stream?lastId=not-a-number', 'GET'));

        $response->getCallback()();

        self::assertSame(200, $response->getStatusCode());
    }

    public function testStreamCallbackUsesHeaderEmitterAndBufferBranches(): void
    {
        $chat = $this->createMock(Chat::class);
        $chat->expects($this->once())->method('getNewMessages')->with(1, 0)->willReturn([]);
        $chat->expects($this->never())->method('messagesToArray');

        $timeValues = [0, 0, 0, 31];
        $headers = [];
        $flushCalls = 0;
        $output = '';

        $controller = new ChatSseController(
            static fn(): Chat => $chat,
            static function (int $seconds): void {},
            static function () use (&$timeValues): int {
                return array_shift($timeValues) ?? 31;
            },
            static fn(): bool => false,
            static function (string $header) use (&$headers): void {
                $headers[] = $header;
            },
            static function (): void {},
            static function () use (&$flushCalls): void {
                $flushCalls++;
            },
            static function (string $chunk) use (&$output): void {
                $output .= $chunk;
            },
            15,
            30,
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        ob_start();
        try {
            $response = $controller->stream(Request::create('/chat/stream', 'GET'));
            $response->getCallback()();
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        self::assertSame(
            [
                'Content-Type: text/event-stream',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'X-Accel-Buffering: no',
            ],
            $headers,
        );
        self::assertSame(2, $flushCalls);
        self::assertStringContainsString('event: reconnect', $output);
    }

    public function testStreamCallbackSleepsBeforeReconnectWhenRuntimeNotReached(): void
    {
        $chat = $this->createMock(Chat::class);
        $chat->expects($this->exactly(2))->method('getNewMessages')->with(1, 0)->willReturn([], []);
        $chat->expects($this->never())->method('messagesToArray');

        $timeValues = [0, 0, 1, 1, 31, 31, 31];
        $sleepCalls = [];
        $output = '';

        $controller = new ChatSseController(
            static fn(): Chat => $chat,
            static function (int $seconds) use (&$sleepCalls): void {
                $sleepCalls[] = $seconds;
            },
            static function () use (&$timeValues): int {
                return array_shift($timeValues) ?? 31;
            },
            static fn(): bool => false,
            static function (string $header): void {},
            static function (): void {},
            static function (): void {},
            static function (string $chunk) use (&$output): void {
                $output .= $chunk;
            },
            15,
            30,
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->stream(Request::create('/chat/stream', 'GET'));
        $response->getCallback()();

        self::assertSame([2], $sleepCalls);
        self::assertStringContainsString(': heartbeat', $output);
        self::assertStringContainsString('"lastId": 0', $output);
    }

    public function testStreamCallbackSkipsHeartbeatWhenIntervalIsNotReached(): void
    {
        $chat = $this->createMock(Chat::class);
        $chat->expects($this->once())->method('getNewMessages')->with(1, 0)->willReturn([]);
        $chat->expects($this->never())->method('messagesToArray');

        $timeValues = [0, 0, 14, 31];
        $output = '';
        $controller = new ChatSseController(
            static fn(): Chat => $chat,
            static function (int $seconds): void {},
            static function () use (&$timeValues): int {
                return array_shift($timeValues) ?? 31;
            },
            static fn(): bool => false,
            static function (string $header): void {},
            static function (): void {},
            static function (): void {},
            static function (string $chunk) use (&$output): void {
                $output .= $chunk;
            },
            15,
            30,
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->stream(Request::create('/chat/stream', 'GET'));
        $response->getCallback()();

        self::assertStringNotContainsString(': heartbeat', $output);
        self::assertStringContainsString('event: reconnect', $output);
    }

    public function testStreamCallbackReconnectUsesLatestMessageIdAfterMultipleBatches(): void
    {
        $firstMessage = new ChatMessage()
            ->setId(7)
            ->setSenderId(2)
            ->setRecipientId(1)
            ->setMessage('First')
            ->setIsRead(false)
            ->setCreatedAt(new \DateTimeImmutable('2026-03-02T10:00:00+00:00'));

        $secondMessage = new ChatMessage()
            ->setId(9)
            ->setSenderId(2)
            ->setRecipientId(1)
            ->setMessage('Second')
            ->setIsRead(false)
            ->setCreatedAt(new \DateTimeImmutable('2026-03-02T10:00:01+00:00'));

        $chat = $this->createMock(Chat::class);
        $chat
            ->expects($this->exactly(2))
            ->method('getNewMessages')
            ->withAnyParameters()
            ->willReturnCallback(static function (int $userId, int $lastId) use ($firstMessage, $secondMessage): array {
                return match ($lastId) {
                    0 => [$firstMessage],
                    7 => [$secondMessage],
                    default => [],
                };
            });
        $chat
            ->expects($this->exactly(2))
            ->method('messagesToArray')
            ->willReturnCallback(static function (array $messages): array {
                return array_map(static fn(ChatMessage $message): array => [
                    'id' => $message->getId(),
                    'message' => $message->getMessage(),
                ], $messages);
            });

        $timeValues = [0, 0, 1, 1, 31, 31, 31];
        $sleepCalls = [];
        $output = '';

        $controller = new ChatSseController(
            static fn(): Chat => $chat,
            static function (int $seconds) use (&$sleepCalls): void {
                $sleepCalls[] = $seconds;
            },
            static function () use (&$timeValues): int {
                return array_shift($timeValues) ?? 31;
            },
            static fn(): bool => false,
            static function (string $header): void {},
            static function (): void {},
            static function (): void {},
            static function (string $chunk) use (&$output): void {
                $output .= $chunk;
            },
            15,
            30,
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->stream(Request::create('/chat/stream', 'GET'));
        $response->getCallback()();

        self::assertSame([2], $sleepCalls);
        self::assertStringContainsString('"lastId": 9', $output);
        self::assertStringContainsString('First', $output);
        self::assertStringContainsString('Second', $output);
    }
}
