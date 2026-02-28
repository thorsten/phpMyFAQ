<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(AccessibilityController::class)]
#[CoversClass(CookiePolicyController::class)]
#[CoversClass(ImprintController::class)]
#[CoversClass(PrivacyController::class)]
#[CoversClass(TermsController::class)]
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
#[UsesClass(\phpMyFAQ\Controller\ContainerControllerResolver::class)]
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
#[UsesClass(\phpMyFAQ\Strings::class)]
#[UsesClass(\phpMyFAQ\Translation::class)]
#[UsesClass(\phpMyFAQ\Twig\TwigWrapper::class)]
#[UsesClass(\phpMyFAQ\User::class)]
#[UsesClass(\phpMyFAQ\User\CurrentUser::class)]
#[UsesClass(\phpMyFAQ\User\UserData::class)]
#[UsesClass(\phpMyFAQ\User\UserSession::class)]
final class StaticPageControllerWebTest extends ControllerWebTestCase
{
    /**
     * @param array<string, string> $configOverride
     */
    #[DataProvider('staticPageRedirectProvider')]
    public function testStaticPageRedirectsToConfiguredUrl(
        string $path,
        array $configOverride,
        string $expectedTarget,
    ): void {
        $this->overrideConfigurationValues($configOverride);

        $response = $this->requestPublic('GET', $path);

        self::assertResponseStatusCodeSame(302, $response);
        self::assertRedirectLocationContains($expectedTarget, $response);
    }

    /**
     * @return iterable<string, array{path: string, configOverride: array<string, string>, expectedTarget: string}>
     */
    public static function staticPageRedirectProvider(): iterable
    {
        yield 'accessibility' => [
            'path' => '/accessibility.html',
            'configOverride' => ['main.accessibilityStatementURL' => 'https://example.com/accessibility'],
            'expectedTarget' => 'https://example.com/accessibility',
        ];

        yield 'cookies' => [
            'path' => '/cookies.html',
            'configOverride' => ['main.cookiePolicyURL' => 'https://example.com/cookies'],
            'expectedTarget' => 'https://example.com/cookies',
        ];

        yield 'imprint' => [
            'path' => '/imprint.html',
            'configOverride' => ['main.imprintURL' => 'https://example.com/imprint'],
            'expectedTarget' => 'https://example.com/imprint',
        ];

        yield 'privacy' => [
            'path' => '/privacy.html',
            'configOverride' => ['main.privacyURL' => 'https://example.com/privacy'],
            'expectedTarget' => 'https://example.com/privacy',
        ];

        yield 'terms' => [
            'path' => '/terms.html',
            'configOverride' => ['main.termsURL' => 'https://example.com/terms'],
            'expectedTarget' => 'https://example.com/terms',
        ];
    }
}
