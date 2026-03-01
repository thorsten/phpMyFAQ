<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ChatController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ChatControllerTest extends ApiControllerTestCase
{
    public function testGetMessagesReturnsBadRequestForInvalidUserId(): void
    {
        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $request = Request::create('/chat/messages/invalid', 'GET');
        $request->attributes->set('userId', 'invalid');

        $response = $controller->getMessages($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"error":"Invalid user ID"}',
            (string) $response->getContent(),
        );
    }

    public function testSearchUsersReturnsEmptyArrayForShortQuery(): void
    {
        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $request = Request::create('/chat/users?q=a', 'GET');

        $response = $controller->searchUsers($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"success":true,"users":[]}',
            (string) $response->getContent(),
        );
    }

    public function testGetConversationsReturnsSuccessWithEmptyList(): void
    {
        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->getConversations(Request::create('/chat/conversations', 'GET'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"success":true,"conversations":[]}',
            (string) $response->getContent(),
        );
    }
}
