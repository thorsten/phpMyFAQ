<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(AuthenticationController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractFrontController::class)]
#[UsesClass(\phpMyFAQ\Auth\AuthDatabase::class)]
#[UsesClass(\phpMyFAQ\Auth::class)]
#[UsesClass(\phpMyFAQ\Configuration::class)]
#[UsesClass(\phpMyFAQ\Configuration\ConfigurationRepository::class)]
#[UsesClass(\phpMyFAQ\Configuration\LayoutSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\HybridConfigurationStore::class)]
#[UsesClass(\phpMyFAQ\Configuration\UrlSettings::class)]
#[UsesClass(\phpMyFAQ\Controller\ContainerControllerResolver::class)]
#[UsesClass(\phpMyFAQ\Database\PdoSqlite::class)]
#[UsesClass(\phpMyFAQ\Encryption::class)]
#[UsesClass(\phpMyFAQ\Environment::class)]
#[UsesClass(\phpMyFAQ\EventListener\ApiExceptionListener::class)]
#[UsesClass(\phpMyFAQ\EventListener\ControllerContainerListener::class)]
#[UsesClass(\phpMyFAQ\EventListener\LanguageListener::class)]
#[UsesClass(\phpMyFAQ\EventListener\RouterListener::class)]
#[UsesClass(\phpMyFAQ\EventListener\WebExceptionListener::class)]
#[UsesClass(\phpMyFAQ\Filter::class)]
#[UsesClass(\phpMyFAQ\Form\FormsServiceProvider::class)]
#[UsesClass(\phpMyFAQ\Kernel::class)]
#[UsesClass(\phpMyFAQ\Language::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageCodes::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageDetector::class)]
#[UsesClass(\phpMyFAQ\Permission::class)]
#[UsesClass(\phpMyFAQ\Permission\BasicPermission::class)]
#[UsesClass(\phpMyFAQ\Permission\BasicPermissionRepository::class)]
#[UsesClass(\phpMyFAQ\Routing\AttributeRouteLoader::class)]
#[UsesClass(\phpMyFAQ\Routing\RouteCollectionBuilder::class)]
#[UsesClass(\phpMyFAQ\Seo::class)]
#[UsesClass(\phpMyFAQ\Seo\SeoRepository::class)]
#[UsesClass(\phpMyFAQ\Session\SessionWrapper::class)]
#[UsesClass(\phpMyFAQ\Session\Token::class)]
#[UsesClass(\phpMyFAQ\Strings::class)]
#[UsesClass(\phpMyFAQ\Translation::class)]
#[UsesClass(\phpMyFAQ\Twig\TwigWrapper::class)]
#[UsesClass(\phpMyFAQ\User::class)]
#[UsesClass(\phpMyFAQ\User\CurrentUser::class)]
#[UsesClass(\phpMyFAQ\User\TwoFactor::class)]
#[UsesClass(\phpMyFAQ\User\UserData::class)]
#[UsesClass(\phpMyFAQ\User\UserSession::class)]
final class AuthenticationControllerWebTest extends ControllerWebTestCase
{
    public function testLoginPageShowsRegistrationAndPasskeyActionsWhenEnabled(): void
    {
        $this->getConfiguration()->getAll();
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'security.enableRegistration' => true,
            'security.enableWebAuthnSupport' => true,
        ]);

        $response = $this->requestPublic('GET', '/login');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('href="user/register"', $response);
        self::assertResponseContains('./services/webauthn', $response);
    }

    public function testLoginRedirectsToAuthenticateWhenSsoUserIsPresent(): void
    {
        $this->getConfiguration()->getAll();
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'security.ssoSupport' => true,
        ]);

        $response = $this->requestPublic('GET', '/login', [], ['REMOTE_USER' => 'test-user']);

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('./authenticate', $response->headers->get('Location'));
    }

    public function testLoginPageHidesRegistrationAndPasskeyActionsWhenDisabled(): void
    {
        $this->getConfiguration()->getAll();
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'security.enableRegistration' => false,
            'security.enableWebAuthnSupport' => false,
        ]);

        $response = $this->requestPublic('GET', '/login');

        self::assertResponseIsSuccessful($response);
        self::assertStringNotContainsString('href="user/register"', $response->getContent());
        self::assertStringNotContainsString('./services/webauthn', $response->getContent());
    }

    public function testAuthenticateWithoutCredentialsRedirectsToLogin(): void
    {
        $response = $this->requestPublic('POST', '/authenticate');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertRedirectLocationContains('login', $response);
    }

    public function testFailedAuthenticateShowsErrorOnNextLoginPage(): void
    {
        $authenticateResponse = $this->requestPublic('POST', '/authenticate');

        self::assertResponseStatusCodeSame(302, $authenticateResponse);
        self::assertRedirectLocationContains('login', $authenticateResponse);

        $response = $this->requestPublic('GET', '/login');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="phpmyfaq-login"', $response);
    }

    public function testForgotPasswordPageRenders(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/forgot-password');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-password-form"', $response);
        self::assertResponseContains('id="username"', $response);
    }

    public function testTwoFactorPageRendersWithUserIdFromQuery(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/token', ['user-id' => '42']);

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="user-id" name="user-id" value="42"', $response);
        self::assertResponseContains('id="btnLogin"', $response);
    }

    public function testInvalidTwoFactorTokenRedirectsBackToTokenPage(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('POST', '/check', [
            'token' => '123456',
            'user-id' => '0',
        ]);

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('./token?user-id=0', $response->headers->get('Location'));
    }

    public function testTwoFactorCheckWithUnknownTokenRedirectsBackToTokenPage(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('POST', '/check', [
            'token' => '123456',
            'user-id' => '1',
        ]);

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('./token?user-id=1', $response->headers->get('Location'));
    }

    public function testLogoutWithInvalidCsrfRedirectsHome(): void
    {
        $response = $this->requestPublic('GET', '/logout', ['csrf' => 'invalid']);

        self::assertResponseStatusCodeSame(302, $response);
        self::assertNotNull($response->headers->get('Location'));
        self::assertStringEndsWith('/', (string) $response->headers->get('Location'));
    }
}
