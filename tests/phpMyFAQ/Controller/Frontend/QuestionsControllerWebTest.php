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
        self::assertResponseContains('<h1 id="page-header" class="h2 pmf-display-2 mb-0">Open questions</h1>', $response);
        self::assertResponseContains('Currently there are no pending questions.', $response);
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

    /**
     * Deactivated form inputs contribute no label to the rendered form.
     */
    public function testAskQuestionPageOmitsDeactivatedFormInputs(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'records.allowQuestionsForGuests' => 1,
        ]);
        $db = $this->getConfiguration()->getDb();
        self::assertNotFalse($db->query(
            'INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)'
            . " VALUES (9001, 'en', 0, 'Ask category', '', 1, -1, 1, '', 1)",
        ));
        self::assertNotFalse($db->query('INSERT INTO faqcategory_group (category_id, group_id) VALUES (9001, -1)'));
        self::assertNotFalse($db->query('INSERT INTO faqcategory_user (category_id, user_id) VALUES (9001, -1)'));
        self::assertNotFalse($db->query('UPDATE faqforms SET input_active = 0 WHERE form_id = 1 AND input_id = 3'));

        try {
            $response = $this->requestPublic('GET', '/add-question.html');
        } finally {
            $db->query('UPDATE faqforms SET input_active = 1 WHERE form_id = 1 AND input_id = 3');
            $db->query('DELETE FROM faqcategory_user WHERE category_id = 9001');
            $db->query('DELETE FROM faqcategory_group WHERE category_id = 9001');
            $db->query('DELETE FROM faqcategories WHERE id = 9001');
        }

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Your email address', $response);
        self::assertStringNotContainsString('Your name', (string) $response->getContent());
    }
}
