<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[CoversClass(ChatSseController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ChatSseControllerTest extends ApiControllerTestCase
{
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
}
