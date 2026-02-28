<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ChatController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ChatControllerWebTest extends ControllerWebTestCase
{
    public function testChatRedirectsHomeForAnonymousUser(): void
    {
        $response = $this->requestPublic('GET', '/user/chat');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('https://localhost/', $response->headers->get('Location'));
    }
}
