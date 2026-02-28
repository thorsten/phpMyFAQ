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
    public function testUpdatePasswordReturnsConflictWhenUsernameIsMissing(): void
    {
        $response = $this->requestWithContext('api', 'PUT', '/user/password/update', [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode(['email' => 'user@example.org'], JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(409, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        self::assertResponseContains('Missing username', $response);
    }
}
