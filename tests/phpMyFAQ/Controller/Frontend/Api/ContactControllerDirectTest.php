<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail;
use phpMyFAQ\StopWords;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ContactController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ContactControllerDirectTest extends ApiControllerTestCase
{
    public function testCreateReturnsSuccessForLoggedInUserWithDeliverableMessage(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.administrationMail' => 'admin@example.com']);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $mailer = $this->createMock(Mail::class);
        $mailer->expects($this->once())->method('setReplyTo')->with('test@example.com', 'Test User');
        $mailer->expects($this->once())->method('addTo')->with('admin@example.com');
        $mailer->expects($this->once())->method('send');

        $controller = new ContactController($stopWords, $mailer);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/contact', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'How can I reach support?',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    public function testCreateReturnsBadRequestWhenStopWordCheckFails(): void
    {
        $this->configuration->getAll();

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(false);

        $controller = new ContactController($stopWords, $this->createStub(Mail::class));
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/contact', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'Blocked text',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenMailerThrowsException(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.administrationMail' => 'admin@example.com']);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $mailer = $this->createMock(Mail::class);
        $mailer->expects($this->once())->method('setReplyTo')->with('test@example.com', 'Test User');
        $mailer->expects($this->once())->method('addTo')->with('admin@example.com');
        $mailer->expects($this->once())
            ->method('send')
            ->willThrowException(new Exception('SMTP failed'));

        $controller = new ContactController($stopWords, $mailer);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/contact', 'POST', content: json_encode([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'question' => 'How can I reach support?',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('SMTP failed', $payload['error']);
    }
}
