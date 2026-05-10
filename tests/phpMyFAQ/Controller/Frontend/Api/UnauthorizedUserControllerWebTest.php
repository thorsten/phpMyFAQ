<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(UnauthorizedUserController::class)]
#[UsesNamespace('phpMyFAQ')]
final class UnauthorizedUserControllerWebTest extends ControllerWebTestCase
{
    public function testRequestResetReturnsGenericSuccessForUnknownUser(): void
    {
        $response = $this->requestWithContext(
            'api',
            'PUT',
            '/user/password/update',
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode(
                ['username' => 'no-such-user', 'email' => 'no-such@example.org'],
                JSON_THROW_ON_ERROR,
            ),
        );

        self::assertResponseStatusCodeSame(200, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testResetEndpointRejectsRequestWithoutToken(): void
    {
        $response = $this->requestWithContext(
            'api',
            'POST',
            '/user/password/reset',
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode(
                ['password' => 'NewSecret123', 'password_repeat' => 'NewSecret123'],
                JSON_THROW_ON_ERROR,
            ),
        );

        self::assertResponseStatusCodeSame(400, $response);
    }
}
