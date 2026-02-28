<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ChatController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ChatControllerWebTest extends ControllerWebTestCase
{
    public function testConversationsEndpointReturnsUnauthorizedWhenAnonymous(): void
    {
        $response = $this->requestApi('GET', '/chat/conversations');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
