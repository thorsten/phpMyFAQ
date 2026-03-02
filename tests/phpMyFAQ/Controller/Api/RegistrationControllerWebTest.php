<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(RegistrationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class RegistrationControllerWebTest extends ControllerWebTestCase
{
    public function testCreateWithMissingFieldsReturnsBadRequestJson(): void
    {
        $this->overrideConfigurationValues([
            'api.enableAccess' => true,
            'api.apiClientToken' => 'test-token',
        ], 'api');

        $response = $this->requestApiJson(
            'POST',
            '/v3.2/register',
            [
                'username' => '',
                'fullname' => '',
                'email' => '',
                'is-visible' => false,
            ],
            [
                'HTTP_X_PMF_TOKEN' => 'test-token',
            ],
        );

        self::assertResponseStatusCodeSame(400, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        self::assertStringContainsString('"registered":false', (string) $response->getContent());
    }

    public function testCreateWithDisallowedDomainReturnsConflictJson(): void
    {
        $this->overrideConfigurationValues([
            'api.enableAccess' => true,
            'api.apiClientToken' => 'test-token',
            'security.domainWhiteListForRegistrations' => 'allowed.example',
        ], 'api');

        $response = $this->requestApiJson(
            'POST',
            '/v3.2/register',
            [
                'username' => 'ada',
                'fullname' => 'Ada Lovelace',
                'email' => 'ada@example.com',
                'is-visible' => false,
            ],
            [
                'HTTP_X_PMF_TOKEN' => 'test-token',
            ],
        );

        self::assertResponseStatusCodeSame(409, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        self::assertStringContainsString('The domain is not allowed.', (string) $response->getContent());
    }
}
