<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(LoginController::class)]
#[UsesNamespace('phpMyFAQ')]
final class LoginControllerWebTest extends ControllerWebTestCase
{
    public function testLoginReturnsUnauthorizedProblemWhenApiIsDisabled(): void
    {
        $this->getConfiguration('api')->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => false], 'api');

        $response = $this->requestApiJson('POST', '/v3.2/login', [
            'username' => 'invalid-user',
            'password' => 'invalid-password',
        ]);

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('problem+json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testLoginWithInvalidCredentialsReturnsBadRequestJson(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApiJson('POST', '/v3.2/login', [
            'username' => 'invalid-user',
            'password' => 'invalid-password',
        ]);

        self::assertResponseStatusCodeSame(400, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
