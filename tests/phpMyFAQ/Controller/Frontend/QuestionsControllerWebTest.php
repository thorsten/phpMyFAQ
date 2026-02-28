<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(QuestionsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractFrontController::class)]
#[UsesClass(\phpMyFAQ\Auth\AuthDatabase::class)]
#[UsesClass(\phpMyFAQ\Auth::class)]
#[UsesClass(\phpMyFAQ\Category::class)]
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
#[UsesClass(\phpMyFAQ\Helper\QuestionHelper::class)]
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
final class QuestionsControllerWebTest extends ControllerWebTestCase
{
    public function testOpenQuestionsPageIsReachable(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/open-questions.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<h2 id="page-header" class="mb-4 border-bottom">Open questions</h2>', $response);
        self::assertResponseContains('Currently there are no pending questions.', $response);
    }

    public function testAskQuestionRedirectsToLoginWhenGuestQuestionsAreDisabled(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'records.allowQuestionsForGuests' => 0,
        ]);

        $response = $this->requestPublic('GET', '/add-question.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertRedirectLocationContains('login', $response);
    }

    public function testAskQuestionPageRendersWhenGuestQuestionsAreEnabled(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'records.allowQuestionsForGuests' => 1,
        ]);

        $response = $this->requestPublic('GET', '/add-question.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('missing configured categories', $response);
    }
}
