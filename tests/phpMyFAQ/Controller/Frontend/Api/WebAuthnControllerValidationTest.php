<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(WebAuthnController::class)]
#[UsesNamespace('phpMyFAQ')]
final class WebAuthnControllerValidationTest extends ApiControllerTestCase
{
    public function testPrepareReturnsUnauthorizedForInvalidCsrfTokenWhenRegisteringUnknownUser(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.enableWebAuthnSupport' => '1',
            'security.enableRegistration' => '1',
        ]);

        $controller = new WebAuthnController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/webauthn/prepare', 'POST', content: json_encode([
            'username' => 'nonexistent-' . uniqid('', true) . '@example.com',
            'pmf-csrf-token' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->prepare($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }
}
