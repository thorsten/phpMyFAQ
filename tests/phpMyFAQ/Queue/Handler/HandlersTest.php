<?php

namespace phpMyFAQ\Queue\Handler;

use phpMyFAQ\Queue\Message\ExportMessage;
use phpMyFAQ\Queue\Message\IndexFaqMessage;
use phpMyFAQ\Queue\Message\SendMailMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SendMailHandler::class)]
#[CoversClass(IndexFaqHandler::class)]
#[CoversClass(ExportHandler::class)]
#[UsesClass(SendMailMessage::class)]
#[UsesClass(IndexFaqMessage::class)]
#[UsesClass(ExportMessage::class)]
class HandlersTest extends TestCase
{
    public function testSendMailHandlerInvokesCallback(): void
    {
        $captured = null;
        $handler = new SendMailHandler(static function (SendMailMessage $message) use (&$captured): void {
            $captured = $message;
        });

        $message = new SendMailMessage('qa@example.org', 'Subject', 'Body');
        $handler($message);

        $this->assertSame($message, $captured);
    }

    public function testIndexFaqHandlerInvokesCallback(): void
    {
        $captured = null;
        $handler = new IndexFaqHandler(static function (IndexFaqMessage $message) use (&$captured): void {
            $captured = $message;
        });

        $message = new IndexFaqMessage(123, 'en');
        $handler($message);

        $this->assertSame($message, $captured);
    }

    public function testExportHandlerInvokesCallback(): void
    {
        $captured = null;
        $handler = new ExportHandler(static function (ExportMessage $message) use (&$captured): void {
            $captured = $message;
        });

        $message = new ExportMessage('pdf', 1, ['scope' => 'all']);
        $handler($message);

        $this->assertSame($message, $captured);
    }
}
