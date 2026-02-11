<?php

namespace phpMyFAQ\Queue\Handler;

use phpMyFAQ\Configuration;
use phpMyFAQ\Queue\Message\ExportMessage;
use phpMyFAQ\Queue\Message\IndexFaqMessage;
use phpMyFAQ\Queue\Message\SendMailMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(SendMailHandler::class)]
#[CoversClass(IndexFaqHandler::class)]
#[CoversClass(ExportHandler::class)]
#[UsesClass(SendMailMessage::class)]
#[UsesClass(IndexFaqMessage::class)]
#[UsesClass(ExportMessage::class)]
class HandlersTest extends TestCase
{
    public function testSendMailHandlerAcceptsConfiguration(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $handler = new SendMailHandler($configuration);

        $this->assertInstanceOf(SendMailHandler::class, $handler);
    }

    public function testIndexFaqHandlerThrowsWhenElasticsearchNotConfigured(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('isElasticsearchActive')->willReturn(false);

        $handler = new IndexFaqHandler($configuration);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Elasticsearch is not configured');
        $handler(new IndexFaqMessage(1, 'en'));
    }

    public function testExportHandlerAcceptsConfiguration(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $handler = new ExportHandler($configuration);

        $this->assertInstanceOf(ExportHandler::class, $handler);
    }
}
