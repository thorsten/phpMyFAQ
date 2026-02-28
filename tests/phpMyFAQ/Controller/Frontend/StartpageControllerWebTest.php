<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(StartpageController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractFrontController::class)]
#[UsesClass(\phpMyFAQ\Auth\AuthDatabase::class)]
#[UsesClass(\phpMyFAQ\Auth::class)]
#[UsesClass(\phpMyFAQ\Category\Startpage::class)]
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
#[UsesClass(\phpMyFAQ\Faq::class)]
#[UsesClass(\phpMyFAQ\Faq\QueryHelper::class)]
#[UsesClass(\phpMyFAQ\Faq\Statistics::class)]
#[UsesClass(\phpMyFAQ\Filter::class)]
#[UsesClass(\phpMyFAQ\Form\FormsServiceProvider::class)]
#[UsesClass(\phpMyFAQ\Helper\LanguageHelper::class)]
#[UsesClass(\phpMyFAQ\Kernel::class)]
#[UsesClass(\phpMyFAQ\Language::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageCodes::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageDetector::class)]
#[UsesClass(\phpMyFAQ\Language\Plurals::class)]
#[UsesClass(\phpMyFAQ\News::class)]
#[UsesClass(\phpMyFAQ\News\NewsRepository::class)]
#[UsesClass(\phpMyFAQ\Permission::class)]
#[UsesClass(\phpMyFAQ\Permission\BasicPermission::class)]
#[UsesClass(\phpMyFAQ\Permission\BasicPermissionRepository::class)]
#[UsesClass(\phpMyFAQ\Plugin\PluginManager::class)]
#[UsesClass(\phpMyFAQ\Routing\AttributeRouteLoader::class)]
#[UsesClass(\phpMyFAQ\Routing\RouteCollectionBuilder::class)]
#[UsesClass(\phpMyFAQ\Seo::class)]
#[UsesClass(\phpMyFAQ\Seo\SeoRepository::class)]
#[UsesClass(\phpMyFAQ\Session\SessionWrapper::class)]
#[UsesClass(\phpMyFAQ\Strings::class)]
#[UsesClass(\phpMyFAQ\System::class)]
#[UsesClass(\phpMyFAQ\Tags::class)]
#[UsesClass(\phpMyFAQ\Translation::class)]
#[UsesClass(\phpMyFAQ\Twig\Extensions\TagNameTwigExtension::class)]
#[UsesClass(\phpMyFAQ\Twig\Extensions\TranslateTwigExtension::class)]
#[UsesClass(\phpMyFAQ\Twig\TwigWrapper::class)]
#[UsesClass(\phpMyFAQ\User::class)]
#[UsesClass(\phpMyFAQ\User\CurrentUser::class)]
#[UsesClass(\phpMyFAQ\User\UserData::class)]
#[UsesClass(\phpMyFAQ\User\UserSession::class)]
final class StartpageControllerWebTest extends ControllerWebTestCase
{
    public function testStartpageIsReachable(): void
    {
        $response = $this->requestPublic('GET', '/');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<title>phpMyFAQ', $response);
        self::assertResponseContains('Trending FAQs', $response);
    }
}
