<?php

namespace phpMyFAQ;

use DateTimeImmutable;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\ChatMessage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class ChatTest extends TestCase
{
    private Configuration $configuration;

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

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.referenceURL', 'https://example.com');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    public function testSendEmptyMessageReturnsNull(): void
    {
        $chat = new Chat($this->configuration);

        $message = $chat->sendMessage(1, 2, '');
        $this->assertNull($message);

        $message = $chat->sendMessage(1, 2, '   ');
        $this->assertNull($message);
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
        $this->assertEquals('Test message', $array['message']);
        $this->assertFalse($array['isRead']);
    }

    public function testMessagesToArray(): void
    {
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
        $this->assertEquals('Message 1', $array[0]['message']);
        $this->assertEquals('Message 2', $array[1]['message']);
    }

    public function testGetUnreadCountReturnsInteger(): void
    {
        $chat = new Chat($this->configuration);

        // For a non-existent user, unread count should be 0
        $count = $chat->getUnreadCount(99999);
        $this->assertIsInt($count);
        $this->assertEquals(0, $count);
    }

    public function testGetConversationListReturnsArray(): void
    {
        $chat = new Chat($this->configuration);

        // For a non-existent user, conversation list should be empty
        $conversations = $chat->getConversationList(99999);
        $this->assertIsArray($conversations);
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

    public function testChatClassIsReadonly(): void
    {
        $chat = new Chat($this->configuration);
        $reflection = new \ReflectionClass($chat);

        $this->assertTrue($reflection->isReadOnly());
    }
}
