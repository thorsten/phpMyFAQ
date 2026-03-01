<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(UserController::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserControllerValidationTest extends ApiControllerTestCase
{
    private function createController(): UserController
    {
        return new UserController(
            $this->createStub(StopWords::class),
            $this->createStub(Mail::class),
        );
    }

    public function testExportUserDataReturnsUnauthorizedForInvalidCsrfToken(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $request = new Request([], [
            'pmf-csrf-token' => 'invalid',
            'userid' => 1,
        ]);

        $response = $controller->exportUserData($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testUpdateDataReturnsBadRequestForUserIdMismatchWithValidCsrf(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('ucp');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('ucp'), 0, 10))] = $csrfToken;
        $currentUser = $this->createAuthenticatedUserMock(1);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 2,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'password123',
            'faqpassword_confirm' => 'password123',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"error":"User ID mismatch!"}',
            (string) $response->getContent(),
        );
    }
}
