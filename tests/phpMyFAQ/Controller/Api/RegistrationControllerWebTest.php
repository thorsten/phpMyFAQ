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
        $configuration = $this->getConfiguration('api');
        $configuration->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');
        $token = (string) $configuration->get('api.apiClientToken');

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
                'HTTP_X_PMF_TOKEN' => $token,
            ],
        );

        self::assertContains($response->getStatusCode(), [400, 401]);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        if ($response->getStatusCode() === 400) {
            self::assertStringContainsString('"registered":false', (string) $response->getContent());
        }
    }

    public function testCreateWithDisallowedDomainReturnsConflictJson(): void
    {
        $configuration = $this->getConfiguration('api');
        $configuration->getAll();
        $this->overrideConfigurationValues([
            'api.enableAccess' => true,
            'security.domainWhiteListForRegistrations' => 'allowed.example',
        ], 'api');
        $token = (string) $configuration->get('api.apiClientToken');

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
                'HTTP_X_PMF_TOKEN' => $token,
            ],
        );

        self::assertContains($response->getStatusCode(), [401, 409]);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        if ($response->getStatusCode() === 409) {
            self::assertStringContainsString('The domain is not allowed.', (string) $response->getContent());
        }
    }
}
