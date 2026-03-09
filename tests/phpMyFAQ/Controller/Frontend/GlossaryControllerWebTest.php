<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Auth;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Controller\ContainerControllerResolver;
use phpMyFAQ\Encryption;
use phpMyFAQ\Environment;
use phpMyFAQ\EventListener\ApiExceptionListener;
use phpMyFAQ\EventListener\ControllerContainerListener;
use phpMyFAQ\EventListener\LanguageListener;
use phpMyFAQ\EventListener\RouterListener;
use phpMyFAQ\EventListener\WebExceptionListener;
use phpMyFAQ\Filter;
use phpMyFAQ\Form\FormsServiceProvider;
use phpMyFAQ\Functional\ControllerWebTestCase;
use phpMyFAQ\Glossary;
use phpMyFAQ\Kernel;
use phpMyFAQ\Language;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Language\LanguageDetector;
use phpMyFAQ\Pagination;
use phpMyFAQ\Pagination\UrlConfig;
use phpMyFAQ\Permission;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Permission\BasicPermissionRepository;
use phpMyFAQ\Routing\AttributeRouteLoader;
use phpMyFAQ\Routing\RouteCollectionBuilder;
use phpMyFAQ\Seo;
use phpMyFAQ\Seo\SeoRepository;
use phpMyFAQ\Session\SessionWrapper;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserData;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(GlossaryController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractFrontController::class)]
#[UsesClass(AuthDatabase::class)]
#[UsesClass(Auth::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(ConfigurationStorageSettings::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
#[UsesClass(ContainerControllerResolver::class)]
#[UsesClass(Encryption::class)]
#[UsesClass(Environment::class)]
#[UsesClass(ApiExceptionListener::class)]
#[UsesClass(ControllerContainerListener::class)]
#[UsesClass(LanguageListener::class)]
#[UsesClass(RouterListener::class)]
#[UsesClass(WebExceptionListener::class)]
#[UsesClass(Filter::class)]
#[UsesClass(FormsServiceProvider::class)]
#[UsesClass(Glossary::class)]
#[UsesClass(Kernel::class)]
#[UsesClass(Language::class)]
#[UsesClass(LanguageCodes::class)]
#[UsesClass(LanguageDetector::class)]
#[UsesClass(Pagination::class)]
#[UsesClass(UrlConfig::class)]
#[UsesClass(Permission::class)]
#[UsesClass(BasicPermission::class)]
#[UsesClass(BasicPermissionRepository::class)]
#[UsesClass(AttributeRouteLoader::class)]
#[UsesClass(RouteCollectionBuilder::class)]
#[UsesClass(Seo::class)]
#[UsesClass(SeoRepository::class)]
#[UsesClass(SessionWrapper::class)]
#[UsesClass(Strings::class)]
#[UsesClass(Translation::class)]
#[UsesClass(TwigWrapper::class)]
#[UsesClass(User::class)]
#[UsesClass(CurrentUser::class)]
#[UsesClass(UserData::class)]
#[UsesClass(UserSession::class)]
final class GlossaryControllerWebTest extends ControllerWebTestCase
{
    public function testGlossaryPageIsReachable(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/glossary.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<h1 class="mb-4 border-bottom">FAQ Glossary</h1>', $response);
        self::assertResponseContains('<title>FAQ Glossary - ', $response);
    }
}
