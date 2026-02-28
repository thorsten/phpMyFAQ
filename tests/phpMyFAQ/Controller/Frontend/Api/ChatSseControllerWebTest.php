<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ChatSseController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ChatSseControllerWebTest extends ControllerWebTestCase
{
    public function testStreamEndpointReturnsUnauthorizedWhenAnonymous(): void
    {
        $response = $this->requestApi('GET', '/chat/stream');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
