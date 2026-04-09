<?php

namespace phpMyFAQ;

use DateTimeImmutable;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Entity\ChatMessage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class ChatTest extends TestCase
{
    private Configuration $configuration;
    private PdoSqlite $dbHandle;
    private string $databaseFile;

    /**
     * @throws Core\Exception
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-chat-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $this->dbHandle = new PdoSqlite();
        $this->dbHandle->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->configuration->set('main.referenceURL', 'https://example.com');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $this->dbHandle->close();
        @unlink($this->databaseFile);

        parent::tearDown();
    }

    public function testSendEmptyMessageReturnsNull(): void
    {
        $chat = new Chat($this->configuration);

        $message = $chat->sendMessage(1, 2, '');
        $this->assertNull($message);

        $message = $chat->sendMessage(1, 2, '   ');
        $this->assertNull($message);
    }

    public function testSendMessagePersistsAndReturnsEntity(): void
    {
        $this->seedChatUser(101, 'sender', 'Sender Name', 'sender@example.com');
        $this->seedChatUser(102, 'recipient', 'Recipient Name', 'recipient@example.com');

        $chat = new Chat($this->configuration);
        $message = $chat->sendMessage(101, 102, "Hello chat's world");

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertSame(101, $message->getSenderId());
        $this->assertSame(102, $message->getRecipientId());
        $this->assertSame("Hello chat's world", $message->getMessage());
        $this->assertFalse($message->isRead());

        $result = $this->dbHandle->query(
            'SELECT sender_id, recipient_id, message, is_read FROM faqchat_messages WHERE sender_id = 101 AND recipient_id = 102',
        );
        $row = $this->dbHandle->fetchArray($result);

        $this->assertIsArray($row);
        $this->assertSame(101, (int) $row['sender_id']);
        $this->assertSame(102, (int) $row['recipient_id']);
        $this->assertSame("Hello chat's world", $row['message']);
        $this->assertSame(0, (int) $row['is_read']);
    }

    public function testSendMessageReturnsNullWhenInsertFails(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $database = $this->createMock(\phpMyFAQ\Database\DatabaseDriver::class);

        $configuration->method('getDb')->willReturn($database);
        $database->method('nextId')->willReturn(99);
        $database->method('escape')->willReturn('hello');
        $database->method('now')->willReturn('CURRENT_TIMESTAMP');
        $database->method('query')->willReturn(false);

        $chat = new Chat($configuration);

        $this->assertNull($chat->sendMessage(1, 2, 'hello'));
    }

    public function testChatMessageEntity(): void
    {
        $message = new ChatMessage();
        $message
            ->setId(1)
            ->setSenderId(10)
            ->setRecipientId(20)
            ->setMessage('Hello, this is a test!')
            ->setIsRead(false)
            ->setCreatedAt(new DateTimeImmutable('2026-01-23 10:00:00'));

        $this->assertEquals(1, $message->getId());
        $this->assertEquals(10, $message->getSenderId());
        $this->assertEquals(20, $message->getRecipientId());
        $this->assertEquals('Hello, this is a test!', $message->getMessage());
        $this->assertFalse($message->isRead());
        $this->assertInstanceOf(DateTimeImmutable::class, $message->getCreatedAt());
        $this->assertEquals('2026-01-23 10:00:00', $message->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testChatMessageEntityWithSetters(): void
    {
        $message = new ChatMessage();

        $result = $message->setId(5);
        $this->assertSame($message, $result);

        $result = $message->setSenderId(100);
        $this->assertSame($message, $result);

        $result = $message->setRecipientId(200);
        $this->assertSame($message, $result);

        $result = $message->setMessage('Test message');
        $this->assertSame($message, $result);

        $result = $message->setIsRead(true);
        $this->assertSame($message, $result);

        $now = new DateTimeImmutable();
        $result = $message->setCreatedAt($now);
        $this->assertSame($message, $result);
    }

    public function testMessageToArray(): void
    {
        $this->seedChatUser(10, 'sender10', 'Sender Ten', 'sender10@example.com');

        $chat = new Chat($this->configuration);

        $message = new ChatMessage();
        $message
            ->setId(1)
            ->setSenderId(10)
            ->setRecipientId(20)
            ->setMessage('Test message')
            ->setIsRead(false)
            ->setCreatedAt(new DateTimeImmutable('2026-01-23 10:00:00'));

        $array = $chat->messageToArray($message);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('senderId', $array);
        $this->assertArrayHasKey('senderName', $array);
        $this->assertArrayHasKey('recipientId', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('isRead', $array);
        $this->assertArrayHasKey('createdAt', $array);

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(10, $array['senderId']);
        $this->assertEquals(20, $array['recipientId']);
        $this->assertSame('Sender Ten', $array['senderName']);
        $this->assertEquals('Test message', $array['message']);
        $this->assertFalse($array['isRead']);
    }

    public function testMessagesToArray(): void
    {
        $this->seedChatUser(10, 'sender10', 'Sender Ten', 'sender10@example.com');
        $this->seedChatUser(20, 'sender20', 'Sender Twenty', 'sender20@example.com');

        $chat = new Chat($this->configuration);

        $message1 = new ChatMessage();
        $message1
            ->setId(1)
            ->setSenderId(10)
            ->setRecipientId(20)
            ->setMessage('Message 1')
            ->setIsRead(false)
            ->setCreatedAt(new DateTimeImmutable());

        $message2 = new ChatMessage();
        $message2
            ->setId(2)
            ->setSenderId(20)
            ->setRecipientId(10)
            ->setMessage('Message 2')
            ->setIsRead(true)
            ->setCreatedAt(new DateTimeImmutable());

        $array = $chat->messagesToArray([$message1, $message2]);

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertSame('Sender Ten', $array[0]['senderName']);
        $this->assertSame('Sender Twenty', $array[1]['senderName']);
        $this->assertEquals('Message 1', $array[0]['message']);
        $this->assertEquals('Message 2', $array[1]['message']);
    }

    public function testMessagesToArrayReturnsEmptyArrayForEmptyInput(): void
    {
        $chat = new Chat($this->configuration);

        $this->assertSame([], $chat->messagesToArray([]));
    }

    public function testGetUnreadCountReturnsInteger(): void
    {
        $chat = new Chat($this->configuration);

        // For a non-existent user, the unread count should be 0
        $count = $chat->getUnreadCount(99999);
        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
    }

    public function testGetConversationListReturnsArray(): void
    {
        $chat = new Chat($this->configuration);

        // For a non-existent user, a conversation list should be empty
        $conversations = $chat->getConversationList(99999);
        $this->assertIsArray($conversations);
    }

    public function testGetConversationListReturnsSortedConversationsWithUnreadCounts(): void
    {
        $this->seedChatUser(201, 'chat-user-201', 'Alice Example', 'alice@example.com');
        $this->seedChatUser(202, 'chat-user-202', 'Bob Example', 'bob@example.com');
        $this->seedChatUser(203, 'chat-user-203', 'Carol Example', 'carol@example.com');
        $this->seedChatMessage(1, 201, 202, 'Older message', 1, '2026-02-01 10:00:00');
        $this->seedChatMessage(2, 202, 201, 'Newest from Bob', 0, '2026-02-01 11:00:00');
        $this->seedChatMessage(3, 203, 201, 'Newest overall', 0, '2026-02-01 12:00:00');

        $chat = new Chat($this->configuration);
        $conversations = $chat->getConversationList(201);

        $this->assertCount(2, $conversations);
        $this->assertSame(203, $conversations[0]['userId']);
        $this->assertSame('Carol Example', $conversations[0]['displayName']);
        $this->assertSame('Newest overall', $conversations[0]['lastMessage']);
        $this->assertSame('2026-02-01 12:00:00', $conversations[0]['lastMessageTime']);
        $this->assertSame(1, $conversations[0]['unreadCount']);

        $this->assertSame(202, $conversations[1]['userId']);
        $this->assertSame('Bob Example', $conversations[1]['displayName']);
        $this->assertSame('Newest from Bob', $conversations[1]['lastMessage']);
        $this->assertSame(1, $conversations[1]['unreadCount']);
    }

    public function testGetConversationListFallsBackToUnknownUserWhenUserDataIsMissing(): void
    {
        $this->seedChatUser(211, 'chat-user-211', 'Primary User', 'primary@example.com');
        $this->seedChatUser(212, 'chat-user-212', 'Partner User', 'partner@example.com');
        $this->dbHandle->query('DELETE FROM faquserdata WHERE user_id = 212');
        $this->seedChatMessage(10, 212, 211, 'Missing profile data', 0, '2026-02-02 10:00:00');

        $chat = new Chat($this->configuration);
        $conversations = $chat->getConversationList(211);

        $this->assertCount(1, $conversations);
        $this->assertSame('Unknown User', $conversations[0]['displayName']);
    }

    public function testGetConversationReturnsArray(): void
    {
        $chat = new Chat($this->configuration);

        // For non-existent users, conversation should be empty
        $conversation = $chat->getConversation(99998, 99999);
        $this->assertIsArray($conversation);
        $this->assertEmpty($conversation);
    }

    public function testGetNewMessagesReturnsArray(): void
    {
        $chat = new Chat($this->configuration);

        // For a non-existent user, new messages should be empty
        $messages = $chat->getNewMessages(99999, 0);
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    public function testGetNewMessagesReturnsMappedMessages(): void
    {
        $this->seedChatUser(301, 'chat-user-301', 'Reader User', 'reader@example.com');
        $this->seedChatUser(302, 'chat-user-302', 'Writer User', 'writer@example.com');
        $this->seedChatMessage(20, 302, 301, 'old message', 0, '2026-02-03 10:00:00');
        $this->seedChatMessage(21, 302, 301, 'new message', 1, '2026-02-03 11:00:00');

        $chat = new Chat($this->configuration);
        $messages = $chat->getNewMessages(301, 20);

        $this->assertCount(1, $messages);
        $this->assertSame(21, $messages[0]->getId());
        $this->assertSame('new message', $messages[0]->getMessage());
        $this->assertTrue($messages[0]->isRead());
    }

    public function testMarkAsReadReturnsBoolean(): void
    {
        $chat = new Chat($this->configuration);

        // Marking a non-existent message should still return boolean
        $result = $chat->markAsRead(99999, 99999);
        $this->assertIsBool($result);
    }

    public function testMarkConversationAsReadReturnsBoolean(): void
    {
        $chat = new Chat($this->configuration);

        // Marking non-existent conversation should still return boolean
        $result = $chat->markConversationAsRead(99998, 99999);
        $this->assertIsBool($result);
    }

    public function testSearchUsersReturnsArray(): void
    {
        $chat = new Chat($this->configuration);

        // Searching for users should return an array
        $users = $chat->searchUsers('nonexistent', 99999);
        $this->assertIsArray($users);
    }

    public function testSearchUsersReturnsMatchingActiveUsersAndRespectsExcludeAndLimit(): void
    {
        $this->seedChatUser(401, 'chat-user-401', 'Alpha Example', 'alpha@example.com', 'active');
        $this->seedChatUser(402, 'chat-user-402', 'Alphabet Soup', 'alphabet@example.com', 'active');
        $this->seedChatUser(403, 'chat-user-403', 'Alpha Blocked', 'blocked@example.com', 'blocked');

        $chat = new Chat($this->configuration);
        $users = $chat->searchUsers('alpha', 401, 1);

        $this->assertCount(1, $users);
        $this->assertSame(402, $users[0]['userId']);
        $this->assertSame('Alphabet Soup', $users[0]['displayName']);
        $this->assertArrayNotHasKey('email', $users[0]);
    }

    public function testChatClassIsReadonly(): void
    {
        $chat = new Chat($this->configuration);
        $reflection = new ReflectionClass($chat);

        $this->assertTrue($reflection->isReadOnly());
    }

    private function seedChatUser(
        int $userId,
        string $login,
        string $displayName,
        string $email,
        string $status = 'active',
    ): void {
        $this->dbHandle->query(sprintf(
            "INSERT INTO faquser (user_id, login, account_status, member_since) VALUES (%d, '%s', '%s', '20260101000000')",
            $userId,
            $this->dbHandle->escape($login),
            $this->dbHandle->escape($status),
        ));

        $this->dbHandle->query(sprintf(
            "INSERT INTO faquserdata (user_id, display_name, email) VALUES (%d, '%s', '%s')",
            $userId,
            $this->dbHandle->escape($displayName),
            $this->dbHandle->escape($email),
        ));
    }

    private function seedChatMessage(
        int $id,
        int $senderId,
        int $recipientId,
        string $message,
        int $isRead,
        string $createdAt,
    ): void {
        $this->dbHandle->query(sprintf(
            "INSERT INTO faqchat_messages (id, sender_id, recipient_id, message, is_read, created_at)
                 VALUES (%d, %d, %d, '%s', %d, '%s')",
            $id,
            $senderId,
            $recipientId,
            $this->dbHandle->escape($message),
            $isRead,
            $this->dbHandle->escape($createdAt),
        ));
    }
}
