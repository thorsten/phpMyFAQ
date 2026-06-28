<?php

namespace phpMyFAQ\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Class ChatMessageTest
 */
class ChatMessageTest extends TestCase
{
    private ChatMessage $chatMessage;

    protected function setUp(): void
    {
        $this->chatMessage = new ChatMessage();
    }

    /**
     * Test ChatMessage entity instantiation
     */
    public function testChatMessageInstantiation(): void
    {
        $this->assertInstanceOf(ChatMessage::class, $this->chatMessage);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->chatMessage->setId($id);

        $this->assertInstanceOf(ChatMessage::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->chatMessage->getId());
    }

    /**
     * Test senderId getter and setter
     */
    public function testSenderIdGetterAndSetter(): void
    {
        $senderId = 10;
        $result = $this->chatMessage->setSenderId($senderId);

        $this->assertInstanceOf(ChatMessage::class, $result); // Test fluent interface
        $this->assertEquals($senderId, $this->chatMessage->getSenderId());
    }

    /**
     * Test recipientId getter and setter
     */
    public function testRecipientIdGetterAndSetter(): void
    {
        $recipientId = 20;
        $result = $this->chatMessage->setRecipientId($recipientId);

        $this->assertInstanceOf(ChatMessage::class, $result); // Test fluent interface
        $this->assertEquals($recipientId, $this->chatMessage->getRecipientId());
    }

    /**
     * Test message getter and setter
     */
    public function testMessageGetterAndSetter(): void
    {
        $message = 'Hello, this is a chat message.';
        $result = $this->chatMessage->setMessage($message);

        $this->assertInstanceOf(ChatMessage::class, $result); // Test fluent interface
        $this->assertEquals($message, $this->chatMessage->getMessage());
    }

    /**
     * Test isRead getter and setter
     */
    public function testIsReadGetterAndSetter(): void
    {
        $result = $this->chatMessage->setIsRead(true);

        $this->assertInstanceOf(ChatMessage::class, $result); // Test fluent interface
        $this->assertTrue($this->chatMessage->isRead());

        $this->chatMessage->setIsRead(false);
        $this->assertFalse($this->chatMessage->isRead());
    }

    /**
     * Test createdAt getter and setter
     */
    public function testCreatedAtGetterAndSetter(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-19 12:00:00');
        $result = $this->chatMessage->setCreatedAt($createdAt);

        $this->assertInstanceOf(ChatMessage::class, $result); // Test fluent interface
        $this->assertSame($createdAt, $this->chatMessage->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->chatMessage->getCreatedAt());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-19 09:30:00');

        $result = $this->chatMessage
            ->setId(1)
            ->setSenderId(100)
            ->setRecipientId(200)
            ->setMessage('Chained message')
            ->setIsRead(true)
            ->setCreatedAt($createdAt);

        $this->assertInstanceOf(ChatMessage::class, $result);
        $this->assertEquals(1, $this->chatMessage->getId());
        $this->assertEquals(100, $this->chatMessage->getSenderId());
        $this->assertEquals(200, $this->chatMessage->getRecipientId());
        $this->assertEquals('Chained message', $this->chatMessage->getMessage());
        $this->assertTrue($this->chatMessage->isRead());
        $this->assertSame($createdAt, $this->chatMessage->getCreatedAt());
    }

    /**
     * Test zero values
     */
    public function testZeroValues(): void
    {
        $this->chatMessage->setId(0)->setSenderId(0)->setRecipientId(0);

        $this->assertEquals(0, $this->chatMessage->getId());
        $this->assertEquals(0, $this->chatMessage->getSenderId());
        $this->assertEquals(0, $this->chatMessage->getRecipientId());
    }

    /**
     * Test negative values
     */
    public function testNegativeValues(): void
    {
        $this->chatMessage->setId(-1)->setSenderId(-10)->setRecipientId(-20);

        $this->assertEquals(-1, $this->chatMessage->getId());
        $this->assertEquals(-10, $this->chatMessage->getSenderId());
        $this->assertEquals(-20, $this->chatMessage->getRecipientId());
    }

    /**
     * Test integer boundaries
     */
    public function testIntegerBoundaries(): void
    {
        $this->chatMessage->setId(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $this->chatMessage->getId());

        $this->chatMessage->setId(PHP_INT_MIN);
        $this->assertEquals(PHP_INT_MIN, $this->chatMessage->getId());
    }

    /**
     * Test empty string message
     */
    public function testEmptyStringMessage(): void
    {
        $this->chatMessage->setMessage('');
        $this->assertEquals('', $this->chatMessage->getMessage());
    }

    /**
     * Test message with special characters
     */
    public function testMessageWithSpecialCharacters(): void
    {
        $message = "Multi\nline message with äöü, emojis 🚀 and <html> & \"quotes\"";
        $this->chatMessage->setMessage($message);
        $this->assertEquals($message, $this->chatMessage->getMessage());
    }

    /**
     * Test isRead defaults can be toggled both ways
     */
    public function testIsReadToggling(): void
    {
        $this->chatMessage->setIsRead(false);
        $this->assertFalse($this->chatMessage->isRead());

        $this->chatMessage->setIsRead(true);
        $this->assertTrue($this->chatMessage->isRead());
    }

    /**
     * Test different DateTimeImmutable objects
     */
    public function testDifferentDateTimes(): void
    {
        $dates = [
            new DateTimeImmutable('2026-01-01 00:00:00'),
            new DateTimeImmutable('2026-12-31 23:59:59'),
            new DateTimeImmutable('1970-01-01 00:00:00'),
        ];

        foreach ($dates as $date) {
            $this->chatMessage->setCreatedAt($date);
            $this->assertSame($date, $this->chatMessage->getCreatedAt());
        }
    }

    /**
     * Test object state independence between instances
     */
    public function testObjectStateIndependence(): void
    {
        $first = new ChatMessage();
        $first->setId(1)->setSenderId(10)->setMessage('first');

        $second = new ChatMessage();
        $second->setId(2)->setSenderId(20)->setMessage('second');

        $this->assertEquals(1, $first->getId());
        $this->assertEquals(10, $first->getSenderId());
        $this->assertEquals('first', $first->getMessage());

        $this->assertEquals(2, $second->getId());
        $this->assertEquals(20, $second->getSenderId());
        $this->assertEquals('second', $second->getMessage());
    }
}
