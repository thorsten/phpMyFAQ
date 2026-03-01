<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(RegistrationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class RegistrationControllerValidationTest extends ApiControllerTestCase
{
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
}
