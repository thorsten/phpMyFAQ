<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(RegistrationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class RegistrationControllerValidationTest extends ApiControllerTestCase
{
    public function testCreateThrowsExceptionWhenRealnameIsMissing(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing realname');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'name' => 'testuser',
            'email' => 'testuser@example.com',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenPayloadIsNotAnObject(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid request payload');

        $controller->create(Request::create('/api/register', 'POST', content: '[]'));
    }

    public function testCreateThrowsExceptionWhenUsernameIsMissing(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing username');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'email' => 'testuser@example.com',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsMissing(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty email');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsEmpty(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty email');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => '   ',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsInvalid(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid email address');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => 'not-an-email',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateReturnsBadRequestWhenCaptchaValidationFails(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser@example.com',
            'email' => 'testuser@example.com',
            'captcha' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateThrowsExceptionWhenIsVisibleParameterIsProvided(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('isVisible parameter not allowed');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => 'testuser@example.com',
            'isVisible' => true,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateReturnsBadRequestWhenNormalizedUserDataIsEmpty(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/register', 'POST', content: json_encode([
            'realname' => '   ',
            'name' => '   ',
            'email' => 'testuser@example.com',
            'captcha' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }
}
