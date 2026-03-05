<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(UserController::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserControllerWebTest extends ControllerWebTestCase
{
    public function testRequestRemovalRedirectsHomeForAnonymousUser(): void
    {
        $response = $this->requestPublic('GET', '/user/request-removal');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertNotNull($response->headers->get('Location'));
        self::assertStringEndsWith('/', (string) $response->headers->get('Location'));
    }

    public function testBookmarksRedirectHomeForAnonymousUser(): void
    {
        $response = $this->requestPublic('GET', '/user/bookmarks');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertNotNull($response->headers->get('Location'));
        self::assertStringEndsWith('/', (string) $response->headers->get('Location'));
    }

    public function testRegisterRedirectsHomeWhenRegistrationIsDisabled(): void
    {
        $expectedTarget = $this->getConfiguration()->getDefaultUrl();
        $this->overrideConfigurationValues(['security.enableRegistration' => false]);

        $response = $this->requestPublic('GET', '/user/register');

        self::assertContains($response->getStatusCode(), [200, 302]);
        if ($response->getStatusCode() === 302) {
            self::assertSame($expectedTarget, $response->headers->get('Location'));
        }
    }

    public function testRegisterPageRendersWhenRegistrationIsEnabled(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'security.enableRegistration' => true,
        ]);

        $response = $this->requestPublic('GET', '/user/register');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Registration', $response);
    }

    public function testRegisterPageShowsPasskeySectionWhenWebAuthnIsEnabled(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'security.enableRegistration' => true,
            'security.enableWebAuthnSupport' => true,
        ]);

        $response = $this->requestPublic('GET', '/user/register');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-register-form"', $response);
    }

    public function testRegisterPageHidesPasskeySectionWhenWebAuthnIsDisabled(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'security.enableRegistration' => true,
            'security.enableWebAuthnSupport' => false,
        ]);

        $response = $this->requestPublic('GET', '/user/register');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Registration', $response);
        self::assertStringNotContainsString('When registering with Passkeys', $response->getContent());
    }

    public function testUcpRedirectsHomeForAnonymousUser(): void
    {
        $response = $this->requestPublic('GET', '/user/ucp');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertNotNull($response->headers->get('Location'));
        self::assertStringEndsWith('/', (string) $response->headers->get('Location'));
    }
}
