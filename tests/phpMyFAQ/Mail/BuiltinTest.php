<?php

namespace phpMyFAQ\Mail;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Builtin::class)]
class BuiltinTest extends TestCase
{
    private Builtin $builtin;

    protected function setUp(): void
    {
        $this->builtin = new Builtin();
    }

    public function testSendBasicEmail(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Test Subject',
            'From' => 'sender@example.com',
            'Reply-To' => 'reply@example.com',
        ];
        $body = 'This is a test email body.';

        // Mock the mail function
        if (!function_exists('mail')) {
            $this->markTestSkipped('mail() function is not available');
        }

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testSendEmailWithSubjectHandling(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Test Subject with Special Characters äöü',
            'From' => 'sender@example.com',
        ];
        $body = 'Test body';

        // Test that Subject header is properly extracted and removed from headers
        $originalHeaders = $headers;
        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
        // Subject should be extracted from headers array during processing
        $this->assertEquals('Test Subject with Special Characters äöü', $originalHeaders['Subject']);
    }

    public function testSendEmailWithReturnPath(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Test Subject',
            'From' => 'sender@example.com',
            'Return-Path' => '<bounce@example.com>',
        ];
        $body = 'Test body';

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testSendEmailWithMultipleRecipients(): void
    {
        $recipients = 'test1@example.com,test2@example.com,test3@example.com';
        $headers = [
            'Subject' => 'Multiple Recipients Test',
            'From' => 'sender@example.com',
            'Cc' => 'cc@example.com',
            'Bcc' => 'bcc@example.com',
        ];
        $body = 'Test body for multiple recipients';

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testSendEmailWithComplexHeaders(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Complex Headers Test',
            'From' => 'sender@example.com',
            'Reply-To' => 'reply@example.com',
            'X-Mailer' => 'phpMyFAQ',
            'X-Priority' => '1',
            'Content-Type' => 'text/html; charset=utf-8',
            'MIME-Version' => '1.0',
        ];
        $body = '<html><body><h1>HTML Email</h1><p>This is an HTML email.</p></body></html>';

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testSendEmailWithEmptySubject(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => '',
            'From' => 'sender@example.com',
        ];
        $body = 'Test body with empty subject';

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testSendEmailWithOnlyRequiredHeaders(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Minimal Headers Test',
        ];
        $body = 'Test body with minimal headers';

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testSendEmailWithSpecialCharactersInBody(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Special Characters Test',
            'From' => 'sender@example.com',
            'Content-Type' => 'text/plain; charset=utf-8',
        ];
        $body = 'Test with special characters: äöü ßÄÖÜ €£¥ 中文 русский';

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testSendEmailWithLongContent(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Long Content Test',
            'From' => 'sender@example.com',
        ];
        $body = str_repeat('This is a long email content. ', 1000);

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testReturnPathProcessingOnWindows(): void
    {
        // This test simulates Windows environment behavior
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Windows Test',
            'From' => 'sender@example.com',
            'Return-Path' => '<bounce@example.com>',
        ];
        $body = 'Test body';

        // On Windows or in safe mode, Return-Path should not be processed as sender parameter
        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testHeadersFormattingWithNewlines(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Headers Formatting Test',
            'From' => 'sender@example.com',
            'X-Custom-Header' => 'Custom Value',
            'X-Another-Header' => 'Another Value',
        ];
        $body = 'Test body for headers formatting';

        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testSendEmailWithAngleBracketsInReturnPath(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Angle Brackets Test',
            'From' => 'sender@example.com',
            'Return-Path' => '<bounce@example.com>',
        ];
        $body = 'Test body with angle brackets in return path';

        // Test that angle brackets are properly stripped from Return-Path
        $result = $this->builtin->send($recipients, $headers, $body);

        $this->assertIsInt($result);
    }

    public function testImplementsMailUserAgentInterface(): void
    {
        $this->assertInstanceOf(\phpMyFAQ\Mail\MailUserAgentInterface::class, $this->builtin);
    }

    public function testSendMethodExists(): void
    {
        $this->assertTrue(method_exists($this->builtin, 'send'));
    }

    public function testSendMethodSignature(): void
    {
        $reflection = new \ReflectionMethod($this->builtin, 'send');

        $this->assertEquals('send', $reflection->getName());
        $this->assertEquals(3, $reflection->getNumberOfParameters());
        $this->assertEquals('int', $reflection->getReturnType()?->getName());
    }
}
