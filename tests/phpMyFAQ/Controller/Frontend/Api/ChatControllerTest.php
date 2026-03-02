<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Session\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ChatController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ChatControllerTest extends ApiControllerTestCase
{
    private function insertChatMessage(
        int $senderId,
        int $recipientId,
        string $message,
        int $isRead = 0,
        string $createdAt = '2026-03-02 10:00:00',
    ): void {
        $query = sprintf(
            "INSERT INTO faqchat_messages (sender_id, recipient_id, message, is_read, created_at)
             VALUES (%d, %d, '%s', %d, '%s')",
            $senderId,
            $recipientId,
            $this->configuration->getDb()->escape($message),
            $isRead,
            $createdAt,
        );

        $this->configuration->getDb()->query($query);
    }

    private function upsertUserData(int $userId, string $displayName, string $email): void
    {
        $query = sprintf(
            "INSERT OR REPLACE INTO faquserdata (user_id, last_modified, display_name, email, twofactor_enabled, secret, is_visible)
             VALUES (%d, '20260302100000', '%s', '%s', 0, '', 1)",
            $userId,
            $this->configuration->getDb()->escape($displayName),
            $this->configuration->getDb()->escape($email),
        );

        $this->configuration->getDb()->query($query);
    }

    private function createValidSessionToken(
        \Symfony\Component\HttpFoundation\Session\Session $session,
        string $page,
    ): string {
        return Token::getInstance($session)->getTokenString($page);
    }

    public function testGetMessagesReturnsBadRequestForInvalidUserId(): void
    {
        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $request = Request::create('/chat/messages/invalid', 'GET');
        $request->attributes->set('userId', 'invalid');

        $response = $controller->getMessages($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Invalid user ID"}', (string) $response->getContent());
    }

    public function testSearchUsersReturnsEmptyArrayForShortQuery(): void
    {
        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $request = Request::create('/chat/users?q=a', 'GET');

        $response = $controller->searchUsers($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"success":true,"users":[]}', (string) $response->getContent());
    }

    public function testGetConversationsReturnsSuccessWithEmptyList(): void
    {
        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->getConversations(Request::create('/chat/conversations', 'GET'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"success":true,"conversations":[]}', (string) $response->getContent());
    }

    public function testGetUnreadCountReturnsUnreadMessageTotal(): void
    {
        $this->insertChatMessage(2, 1, 'Unread message', 0);

        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->getUnreadCount(Request::create('/chat/unread-count', 'GET'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertSame(1, $payload['count']);
    }

    public function testGetMessagesReturnsConversationAndMarksMessagesAsRead(): void
    {
        $this->insertChatMessage(2, 1, 'Hello from partner', 0, '2026-03-02 10:00:00');
        $this->insertChatMessage(1, 2, 'Reply from user', 1, '2026-03-02 10:05:00');

        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $request = Request::create('/chat/messages/2?limit=10&offset=0', 'GET');
        $request->attributes->set('userId', 2);

        $response = $controller->getMessages($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $row = $this->configuration
            ->getDb()
            ->fetchObject(
                $this->configuration
                    ->getDb()
                    ->query('SELECT is_read FROM faqchat_messages WHERE sender_id = 2 AND recipient_id = 1'),
            );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertCount(2, $payload['messages']);
        self::assertSame('Hello from partner', $payload['messages'][0]['message']);
        self::assertSame(1, (int) $row->is_read);
    }

    public function testSendReturnsCreatedMessageForValidRequest(): void
    {
        $controller = new ChatController();
        $session = $this->createSession();
        $csrfToken = $this->createValidSessionToken($session, 'send-chat-message');
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $request = Request::create('/chat/send', 'POST', content: json_encode([
            'recipientId' => 2,
            'message' => 'Test chat message',
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->send($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertSame('Test chat message', $payload['message']['message']);
        self::assertArrayHasKey('csrfToken', $payload);
    }

    public function testMarkAsReadReturnsSuccessForExistingMessage(): void
    {
        $this->insertChatMessage(2, 1, 'Unread message', 0, '2026-03-02 10:00:00');

        $controller = new ChatController();
        $session = $this->createSession();
        $csrfToken = $this->createValidSessionToken($session, 'mark-chat-read');
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $messageIdRow = $this->configuration
            ->getDb()
            ->fetchObject(
                $this->configuration
                    ->getDb()
                    ->query('SELECT id FROM faqchat_messages WHERE sender_id = 2 AND recipient_id = 1 LIMIT 1'),
            );

        $request = Request::create('/chat/read/' . $messageIdRow->id, 'POST', content: json_encode([
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR));
        $request->attributes->set('messageId', (int) $messageIdRow->id);

        $response = $controller->markAsRead($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
    }

    public function testSearchUsersReturnsMatchingUsers(): void
    {
        $this->upsertUserData(2, 'Beta User', 'beta@example.com');
        $this->upsertUserData(3, 'Gamma User', 'gamma@example.com');

        $controller = new ChatController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $request = Request::create('/chat/users?q=Beta', 'GET');

        $response = $controller->searchUsers($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertCount(1, $payload['users']);
        self::assertSame('Beta User', $payload['users'][0]['displayName']);
    }
}
