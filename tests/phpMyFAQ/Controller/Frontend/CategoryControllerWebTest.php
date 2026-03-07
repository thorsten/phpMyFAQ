<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(CategoryController::class)]
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
#[UsesClass(\phpMyFAQ\Database\PdoSqlite::class)]
#[UsesClass(\phpMyFAQ\Encryption::class)]
#[UsesClass(\phpMyFAQ\Environment::class)]
#[UsesClass(\phpMyFAQ\EventListener\ApiExceptionListener::class)]
#[UsesClass(\phpMyFAQ\EventListener\ControllerContainerListener::class)]
#[UsesClass(\phpMyFAQ\EventListener\LanguageListener::class)]
#[UsesClass(\phpMyFAQ\EventListener\RouterListener::class)]
#[UsesClass(\phpMyFAQ\EventListener\WebExceptionListener::class)]
#[UsesClass(\phpMyFAQ\Faq::class)]
#[UsesClass(\phpMyFAQ\Filter::class)]
#[UsesClass(\phpMyFAQ\Form\FormsServiceProvider::class)]
#[UsesClass(\phpMyFAQ\Helper\CategoryHelper::class)]
#[UsesClass(\phpMyFAQ\Kernel::class)]
#[UsesClass(\phpMyFAQ\Language::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageCodes::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageDetector::class)]
#[UsesClass(\phpMyFAQ\Language\Plurals::class)]
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
final class CategoryControllerWebTest extends ControllerWebTestCase
{
    private function seedCategory(int $id, string $name, int $parentId = 0): void
    {
        $configuration = $this->getConfiguration('public');
        self::assertInstanceOf(Configuration::class, $configuration);

        $db = $configuration->getDb();
        $db->query(sprintf("DELETE FROM faqcategories WHERE id = %d AND lang = 'en'", $id));
        $db->query(sprintf(
            "INSERT INTO faqcategories
                (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES
                (%d, 'en', %d, '%s', '%s', 1, -1, 1, '', 1)",
            $id,
            $parentId,
            $db->escape($name),
            $db->escape($name . ' description'),
        ));
    }

    private function seedFaqForCategory(int $faqId, int $categoryId, string $question): void
    {
        $configuration = $this->getConfiguration('public');
        self::assertInstanceOf(Configuration::class, $configuration);

        $db = $configuration->getDb();
        $db->query(sprintf("DELETE FROM faqdata WHERE id = %d AND lang = 'en'", $faqId));
        $db->query(sprintf("DELETE FROM faqvisits WHERE id = %d AND lang = 'en'", $faqId));
        $db->query(sprintf(
            "DELETE FROM faqcategoryrelations WHERE category_id = %d AND category_lang = 'en' AND record_id = %d AND record_lang = 'en'",
            $categoryId,
            $faqId,
        ));

        $db->query(sprintf(
            "INSERT INTO faqdata
                (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end, created, notes, sticky_order)
             VALUES
                (%d, 'en', 2000, 0, 'yes', 0, 'test', '%s', 'Answer for %s', 'Unit Test', 'test@example.com', 'y', '20260305120000', '00000000000000', '99991231235959', '2026-03-05 12:00:00', '', 0)",
            $faqId,
            $db->escape($question),
            $db->escape($question),
        ));
        $db->query(sprintf("INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang)
             VALUES (%d, 'en', %d, 'en')", $categoryId, $faqId));
        $db->query(sprintf("INSERT INTO faqvisits (id, lang, visits, last_visit) VALUES (%d, 'en', 0, 0)", $faqId));
    }

    public function testShowAllCategoriesPageIsReachable(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/show-categories.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<h2 class="mb-4 border-bottom">Categories</h2>', $response);
        self::assertResponseContains('<h4 class="fst-italic">All categories</h4>', $response);
    }

    public function testShowCategoryPageShowsNoFaqMessageForSeededRootCategory(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);
        $this->seedCategory(801, 'Root Category');
        $this->seedCategory(802, 'Child Category', 801);
        $this->seedFaqForCategory(9801, 801, 'Root test question');

        $response = $this->requestPublic('GET', '/category/801/root-category.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Root Category', $response);
        self::assertResponseContains('No FAQs available.', $response);
        self::assertResponseContains('show-categories.html', $response);
    }

    public function testShowCategoryPageShowsNoRecordsMessageWhenFaqsAreMissing(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);
        $this->seedCategory(811, 'Empty Parent');
        $this->seedCategory(812, 'Empty Child', 811);

        $response = $this->requestPublic('GET', '/category/811/empty-parent.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('No FAQs available.', $response);
        self::assertResponseContains('Empty Parent', $response);
    }

    public function testShowCategoryPageContainsLinkToParentCategory(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);
        $this->seedCategory(821, 'Parent Category');
        $this->seedCategory(822, 'Child Category', 821);

        $response = $this->requestPublic('GET', '/category/822/child-category.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('one category up', $response);
        self::assertResponseContains('/category/821/.html', $response);
    }
}
