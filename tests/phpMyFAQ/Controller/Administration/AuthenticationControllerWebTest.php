<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(AuthenticationController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class AuthenticationControllerWebTest extends ControllerWebTestCase
{
    public function testLoginShowsKeycloakAffordanceWhenEnabled(): void
    {
        $this->overrideConfigurationValues([
            'keycloak.enable' => true,
        ], 'admin');

        $response = $this->requestAdminGuest('GET', '/login');

        self::assertResponseIsSuccessful($response);
        self::assertMatchesRegularExpression(
            '/href="[^"]*auth\/keycloak\/authorize"/',
            (string) $response->getContent(),
        );
        $this->assertResponseContains('Sign in with Keycloak', $response);
    }

    public function testLoginHidesKeycloakAffordanceWhenDisabled(): void
    {
        $this->overrideConfigurationValues([
            'keycloak.enable' => false,
        ], 'admin');

        $response = $this->requestAdminGuest('GET', '/login');

        self::assertResponseIsSuccessful($response);
        self::assertDoesNotMatchRegularExpression(
            '/href="[^"]*auth\/keycloak\/authorize"/',
            (string) $response->getContent(),
        );
        self::assertStringNotContainsString('Sign in with Keycloak', (string) $response->getContent());
    }
}
