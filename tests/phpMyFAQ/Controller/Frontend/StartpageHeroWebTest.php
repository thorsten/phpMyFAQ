<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(StartpageController::class)]
#[CoversClass(CategoryController::class)]
#[UsesNamespace('phpMyFAQ')]
final class StartpageHeroWebTest extends ControllerWebTestCase
{
    public function testStartPageRendersHeroWithSearchForm(): void
    {
        $response = $this->requestPublic('GET', '/');
        $content = (string) $response->getContent();

        self::assertResponseStatusCodeSame(200, $response);
        self::assertStringContainsString('class="pmf-hero', $content);
        self::assertSame(1, substr_count($content, 'id="pmf-search-autocomplete"'));
        self::assertStringNotContainsString('class="pmf-search-band', $content);
        self::assertStringContainsString('class="pmf-faq-list-section', $content);
        // The fixture database may hold no FAQs: either rows or the empty state must render.
        self::assertMatchesRegularExpression('/class="pmf-faq-list-(item|empty)/', $content);
    }

    public function testStartPageTilesShowTheFaqCountOfTheirCategory(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);
        $this->seedStartPageCategoryWithOneFaq(821, 9821);

        $content = (string) $this->requestPublic('GET', '/')->getContent();

        self::assertStringContainsString('<span class="pmf-category-tile-name h5">Billing</span>', $content);
        self::assertStringContainsString('<span class="pmf-category-tile-count">1 FAQ</span>', $content);
    }

    public function testStartPageShowsTheConfiguredHeroImage(): void
    {
        $this->overrideConfigurationValues(['layout.heroImage' => 'content/user/images/hero.png']);

        $content = (string) $this->requestPublic('GET', '/')->getContent();

        self::assertStringContainsString(
            '<img class="pmf-hero-image" src="content/user/images/hero.png" alt="">',
            $content,
        );
    }

    public function testStartPageHasNoHeroImageByDefault(): void
    {
        $content = (string) $this->requestPublic('GET', '/')->getContent();

        self::assertStringContainsString('class="pmf-display-1 pmf-hero-headline"', $content);
        self::assertStringNotContainsString('class="pmf-hero-image"', $content);
    }

    public function testNavigationPromotesAskingAQuestionWhileVisitorsCanAsk(): void
    {
        $this->overrideConfigurationValues(['main.enableAskQuestions' => true]);

        $content = (string) $this->requestPublic('GET', '/')->getContent();

        self::assertStringContainsString('<a class="pmf-nav-cta" href="./add-question.html">', $content);
        self::assertSame(1, substr_count($content, 'href="./add-question.html"'));
    }

    public function testNavigationKeepsAskingAQuestionAPlainLinkWhileQuestionsAreDisabled(): void
    {
        $this->overrideConfigurationValues(['main.enableAskQuestions' => false]);

        $content = (string) $this->requestPublic('GET', '/')->getContent();

        self::assertStringNotContainsString('class="pmf-nav-cta"', $content);
        self::assertSame(1, substr_count($content, 'href="./add-question.html"'));
    }

    public function testDisplaySettingsMenuHoldsTheThemeAndFontSizeSwitchers(): void
    {
        $this->overrideConfigurationValues(['layout.allowUserLayoutMode' => true, 'layout.forceLayoutMode' => 'auto']);

        $content = (string) $this->requestPublic('GET', '/')->getContent();

        self::assertStringContainsString('aria-label="Display settings"', $content);
        self::assertMatchesRegularExpression(
            '/class="dropdown-menu dropdown-menu-end pmf-display-menu">.*id="theme-light".*id="font-size-increase"/s',
            $content,
        );
    }

    public function testHelpButtonLinksToTheContactPageExceptOnTheContactPageItself(): void
    {
        $startPage = (string) $this->requestPublic('GET', '/')->getContent();
        $contactPage = (string) $this->requestPublic('GET', '/contact.html')->getContent();

        self::assertStringContainsString('<a class="pmf-help-button" href="./contact.html">', $startPage);
        self::assertStringNotContainsString('class="pmf-help-button"', $contactPage);
    }

    public function testCategoryPageRendersSearchBandNotHero(): void
    {
        $response = $this->requestPublic('GET', '/show-categories.html');
        $content = (string) $response->getContent();

        self::assertResponseStatusCodeSame(200, $response);
        self::assertStringContainsString('class="pmf-search-band', $content);
        self::assertStringNotContainsString('class="pmf-hero', $content);
        self::assertSame(1, substr_count($content, 'id="pmf-search-autocomplete"'));
    }

    /**
     * Seeds a start page category that every visitor may see, holding one published FAQ.
     */
    private function seedStartPageCategoryWithOneFaq(int $categoryId, int $faqId): void
    {
        $db = $this->getConfiguration('public')->getDb();

        $db->query(sprintf(
            "INSERT INTO faqcategories
                (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES
                (%d, 'en', 0, 'Billing', 'Invoices and payments', 1, -1, 1, '', 1)",
            $categoryId,
        ));
        $db->query(sprintf('INSERT INTO faqcategory_group (category_id, group_id) VALUES (%d, -1)', $categoryId));
        $db->query(sprintf('INSERT INTO faqcategory_user (category_id, user_id) VALUES (%d, -1)', $categoryId));

        $db->query(sprintf(
            "INSERT INTO faqdata
                (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end, created, notes, sticky_order)
             VALUES
                (%d, 'en', 2000, 0, 'published', 0, 'test', 'How do I pay?', 'By invoice.', 'Unit Test', 'test@example.com', 'y', '20260305120000', '00000000000000', '99991231235959', '2026-03-05 12:00:00', '', 0)",
            $faqId,
        ));
        $db->query(sprintf('INSERT INTO faqdata_group (record_id, group_id) VALUES (%d, -1)', $faqId));
        $db->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, -1)', $faqId));
        $db->query(sprintf("INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang)
             VALUES (%d, 'en', %d, 'en')", $categoryId, $faqId));
        $db->query(sprintf("INSERT INTO faqvisits (id, lang, visits, last_visit) VALUES (%d, 'en', 0, 0)", $faqId));
    }
}
