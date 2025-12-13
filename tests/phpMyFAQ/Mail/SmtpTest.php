<?php

namespace phpMyFAQ\Mail;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Mail\Smtp;
use ReflectionClass;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AllowMockObjectsWithoutExpectations]
class SmtpTest extends TestCase
{
    private Smtp $smtp;
    private MailerInterface $mockMailer;

    protected function setUp(): void
    {
        $this->smtp = new Smtp();
        $this->mockMailer = $this->createMock(MailerInterface::class);

        // Use reflection to inject the mock mailer
        $reflection = new ReflectionClass($this->smtp);
        $mailerProperty = $reflection->getProperty('mailer');
        $mailerProperty->setValue($this->smtp, $this->mockMailer);

        $userProperty = $reflection->getProperty('user');
        $userProperty->setValue($this->smtp, 'test@example.com');
    }

    public function testSetAuthConfigBasic(): void
    {
        $this->smtp->setAuthConfig('smtp.example.com', 'user@example.com', 'password');

        $this->assertInstanceOf(Smtp::class, $this->smtp);
    }

    public function testSetAuthConfigWithPort(): void
    {
        $this->smtp->setAuthConfig('smtp.example.com', 'user@example.com', 'password', 587);

        $this->assertInstanceOf(Smtp::class, $this->smtp);
    }

    public function testSetAuthConfigWithTlsDisabled(): void
    {
        $this->smtp->setAuthConfig('smtp.example.com', 'user@example.com', 'password', 25, true);

        $this->assertInstanceOf(Smtp::class, $this->smtp);
    }

    public function testSendBasicEmail(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Test Subject',
            'From' => 'sender@example.com',
        ];
        $body = 'This is a test email body.';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return $email->getSubject() === 'Test Subject' &&
                    count($email->getTo()) === 1 &&
                    $email->getTextBody() === 'This is a test email body.' &&
                    $email->getHtmlBody() === 'This is a test email body.';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testSendEmailWithReturnPath(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Test Subject',
            'Return-Path' => '<bounce@example.com>',
        ];
        $body = 'Test body';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $from = $email->getFrom();
                return count($from) === 1 && $from[0]->getAddress() === 'bounce@example.com';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testSendEmailWithCcAndBcc(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Test Subject',
            'Cc' => 'cc@example.com',
            'Bcc' => 'bcc@example.com',
        ];
        $body = 'Test body';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $cc = $email->getCc();
                $bcc = $email->getBcc();
                return $email->getSubject() === 'Test Subject' &&
                    $email->getTextBody() === 'Test body' &&
                    $email->getHtmlBody() === 'Test body' &&
                    count($cc) === 1 && $cc[0]->getAddress() === 'cc@example.com' &&
                    count($bcc) === 1 && $bcc[0]->getAddress() === 'bcc@example.com';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testSendEmailWithReplyTo(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Test Subject',
            'Reply-To' => 'reply@example.com',
        ];
        $body = 'Test body';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $replyTo = $email->getReplyTo();
                return count($replyTo) === 1 && $replyTo[0]->getAddress() === 'reply@example.com';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testSendEmailWithAllHeaders(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Complete Test',
            'Return-Path' => '<bounce@example.com>',
            'Cc' => 'cc@example.com',
            'Bcc' => 'bcc@example.com',
            'Reply-To' => 'reply@example.com',
        ];
        $body = 'Complete test body';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $from = $email->getFrom();
                $cc = $email->getCc();
                $bcc = $email->getBcc();
                $replyTo = $email->getReplyTo();

                return $email->getSubject() === 'Complete Test' &&
                    $email->getTextBody() === 'Complete test body' &&
                    $email->getHtmlBody() === 'Complete test body' &&
                    count($from) === 1 && $from[0]->getAddress() === 'bounce@example.com' &&
                    count($cc) === 1 && $cc[0]->getAddress() === 'cc@example.com' &&
                    count($bcc) === 1 && $bcc[0]->getAddress() === 'bcc@example.com' &&
                    count($replyTo) === 1 && $replyTo[0]->getAddress() === 'reply@example.com';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testSendEmailUsesUserAsDefaultSender(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Default Sender Test',
        ];
        $body = 'Test body';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $from = $email->getFrom();
                return count($from) === 1 && $from[0]->getAddress() === 'test@example.com';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testSendEmailWithMultipleRecipients(): void
    {
        $recipients = 'test1@example.com,test2@example.com';
        $headers = [
            'Subject' => 'Multiple Recipients',
        ];
        $body = 'Test body';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return $email->getSubject() === 'Multiple Recipients';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testSendEmailThrowsTransportException(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Exception Test',
        ];
        $body = 'Test body';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new TransportException('SMTP connection failed'));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('SMTP connection failed');

        $this->smtp->send($recipients, $headers, $body);
    }

    public function testSendEmailWithEmptySubject(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => '',
        ];
        $body = 'Test body';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return $email->getSubject() === '';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testSendEmailWithSpecialCharacters(): void
    {
        $recipients = 'test@example.com';
        $headers = [
            'Subject' => 'Special Characters: äöü ßÄÖÜ €',
        ];
        $body = 'Body with special characters: 中文 русский';

        $this->mockMailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                return $email->getSubject() === 'Special Characters: äöü ßÄÖÜ €' &&
                    $email->getTextBody() === 'Body with special characters: 中文 русский';
            }));

        $result = $this->smtp->send($recipients, $headers, $body);

        $this->assertEquals(1, $result);
    }

    public function testImplementsMailUserAgentInterface(): void
    {
        $this->assertInstanceOf(\phpMyFAQ\Mail\MailUserAgentInterface::class, $this->smtp);
    }

    public function testSendMethodExists(): void
    {
        $this->assertTrue(method_exists($this->smtp, 'send'));
    }

    public function testSetAuthConfigMethodExists(): void
    {
        $this->assertTrue(method_exists($this->smtp, 'setAuthConfig'));
    }

    public function testSendMethodSignature(): void
    {
        $reflection = new \ReflectionMethod($this->smtp, 'send');

        $this->assertEquals('send', $reflection->getName());
        $this->assertEquals(3, $reflection->getNumberOfParameters());
        $this->assertEquals('int', $reflection->getReturnType()?->getName());
    }
}
