<?php

namespace phpMyFAQ\Queue\Message;

use PHPUnit\Framework\TestCase;

class MessagesTest extends TestCase
{
    public function testExportMessageRoundTrip(): void
    {
        $message = new ExportMessage('json', 5, ['categoryId' => 2, 'downwards' => false]);

        $this->assertSame(
            [
                'format' => 'json',
                'userId' => 5,
                'options' => ['categoryId' => 2, 'downwards' => false],
            ],
            $message->toArray(),
        );

        $restored = ExportMessage::fromArray($message->toArray());

        $this->assertSame('json', $restored->format);
        $this->assertSame(5, $restored->userId);
        $this->assertSame(['categoryId' => 2, 'downwards' => false], $restored->options);
    }

    public function testExportMessageFromArrayAppliesDefaults(): void
    {
        $message = ExportMessage::fromArray([
            'format' => 'pdf',
            'userId' => '9',
            'options' => 'invalid',
        ]);

        $this->assertSame('pdf', $message->format);
        $this->assertSame(9, $message->userId);
        $this->assertSame([], $message->options);
    }

    public function testIndexFaqMessageRoundTrip(): void
    {
        $message = new IndexFaqMessage(17, 'de');

        $this->assertSame(
            [
                'faqId' => 17,
                'language' => 'de',
            ],
            $message->toArray(),
        );

        $restored = IndexFaqMessage::fromArray($message->toArray());

        $this->assertSame(17, $restored->faqId);
        $this->assertSame('de', $restored->language);
    }

    public function testIndexFaqMessageFromArrayAppliesDefaults(): void
    {
        $message = IndexFaqMessage::fromArray([]);

        $this->assertSame(0, $message->faqId);
        $this->assertSame('', $message->language);
    }

    public function testSendMailMessageRoundTrip(): void
    {
        $message = new SendMailMessage('user@example.org', 'Subject', 'Body', ['priority' => 1]);

        $this->assertSame(
            [
                'recipient' => 'user@example.org',
                'subject' => 'Subject',
                'body' => 'Body',
                'metadata' => ['priority' => 1],
            ],
            $message->toArray(),
        );

        $restored = SendMailMessage::fromArray($message->toArray());

        $this->assertSame('user@example.org', $restored->recipient);
        $this->assertSame('Subject', $restored->subject);
        $this->assertSame('Body', $restored->body);
        $this->assertSame(['priority' => 1], $restored->metadata);
    }

    public function testSendMailMessageFromArrayAppliesDefaults(): void
    {
        $message = SendMailMessage::fromArray([
            'recipient' => 123,
            'subject' => null,
            'body' => null,
            'metadata' => 'invalid',
        ]);

        $this->assertSame('123', $message->recipient);
        $this->assertSame('', $message->subject);
        $this->assertSame('', $message->body);
        $this->assertSame([], $message->metadata);
    }
}
