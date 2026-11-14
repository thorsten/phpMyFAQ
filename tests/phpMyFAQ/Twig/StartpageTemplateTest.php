<?php

/**
 * Test case for the default start page template
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Twig\Extensions\TagNameTwigExtension;
use PHPUnit\Framework\TestCase;
use stdClass;
use Twig\Extension\AttributeExtension;
use Twig\TwigFilter;

class StartpageTemplateTest extends TestCase
{
    /**
     * A FAQ question is stored with literal quote characters (the admin API
     * entity-decodes it before saving). Rendering it unescaped into the
     * title="" attribute of the "Top FAQs" list let an editor break out of
     * the attribute and plant an event handler on the public start page.
     *
     * @throws Exception
     */
    public function testTopTenTitleAttributeIsEscaped(): void
    {
        $topten = new stdClass();
        $topten->title = 'x" onmouseover="alert(1)';
        $topten->preview = 'x" onmouseover="alert(1)';
        $topten->url = 'index.php?action=faq&id=1';
        $topten->visits = '1 view';

        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
        $twigWrapper->addExtension(new AttributeExtension(TagNameTwigExtension::class));
        $template = $twigWrapper->loadTemplate('startpage.twig');

        $html = $template->renderBlock('content', [
            'topRecordsList' => [$topten],
            'latestRecordsList' => [],
            'trendingRecordsList' => [],
            'newsList' => [],
            'tagsList' => [],
            'startPageCategories' => false,
            'stickyRecordsList' => [],
        ]);

        static::assertStringNotContainsString('onmouseover="alert(1)"', $html);
        static::assertStringContainsString('title="x&quot; onmouseover=&quot;alert(1)"', $html);
    }

    /**
     * @throws Exception
     */
    public function testHeroShowsItsOwnCopyWithoutAnImageByDefault(): void
    {
        $html = $this->renderContent([
            'pageHeader' => 'My FAQ',
            'msgHeroHeadline' => 'How can we help you?',
            'msgHeroSubline' => 'Search the knowledge base or browse by category.',
            'heroImage' => '',
        ]);

        static::assertStringContainsString('<h1 class="pmf-hero-site pmf-start-page">My FAQ</h1>', $html);
        static::assertStringContainsString('<p class="pmf-display-1 pmf-hero-headline">How can we help you?</p>', $html);
        static::assertStringContainsString(
            '<p class="pmf-hero-subline">Search the knowledge base or browse by category.</p>',
            $html,
        );
        static::assertStringContainsString('<div class="pmf-hero-inner">', $html);
        static::assertStringNotContainsString('pmf-hero-media', $html);
    }

    /**
     * @throws Exception
     */
    public function testHeroShowsTheConfiguredImageInASplitLayout(): void
    {
        $html = $this->renderContent(['heroImage' => 'content/user/images/hero.png']);

        static::assertStringContainsString('<div class="pmf-hero-inner pmf-hero-inner-split">', $html);
        static::assertStringContainsString(
            '<img class="pmf-hero-image" src="content/user/images/hero.png" alt="">',
            $html,
        );
    }

    /**
     * @throws Exception
     */
    public function testHeroLinksAtMostEightPopularTagsToTheSearch(): void
    {
        $html = $this->renderContent(['msgTags' => 'Tags', 'tagsList' => array_fill(1, 9, 1)]);

        static::assertSame(8, substr_count($html, 'class="pmf-hero-chip"'));
        static::assertStringContainsString(
            '<a class="pmf-hero-chip" href="./search.html?tagging_id=8">tag-8</a>',
            $html,
        );
        static::assertStringNotContainsString('tagging_id=9', $html);
    }

    /**
     * @throws Exception
     */
    public function testHeroOmitsTheTagChipsWithoutTags(): void
    {
        static::assertStringNotContainsString('pmf-hero-chips', $this->renderContent(['tagsList' => []]));
    }

    /**
     * @throws Exception
     */
    public function testNumberOfFaqsIsShownOnce(): void
    {
        $html = $this->renderContent(['writeNumberOfArticles' => 'There are 42 FAQs online']);

        static::assertSame(1, substr_count($html, 'There are 42 FAQs online'));
        static::assertStringContainsString('<span class="pmf-hero-pill">There are 42 FAQs online</span>', $html);
    }

    /**
     * @throws Exception
     */
    public function testCategoryTilesShowTheirFaqCountInAThreeColumnGrid(): void
    {
        $html = $this->renderContent([
            'startPageCategories' => true,
            'startPageCategoryDecks' => [
                [
                    'id' => 1,
                    'url' => './category/1/billing.html',
                    'name' => 'Billing',
                    'description' => 'Invoices and payments',
                    'image' => '',
                    'faqCountLabel' => '3 FAQs',
                ],
            ],
        ]);

        static::assertStringContainsString('row-cols-lg-3', $html);
        static::assertStringNotContainsString('row-cols-xl-4', $html);
        static::assertStringContainsString('<span class="pmf-category-tile-count">3 FAQs</span>', $html);
    }

    /**
     * @throws Exception
     */
    public function testFaqListsShareOnePanelWithATabPerNonEmptyList(): void
    {
        $record = new stdClass();
        $record->title = 'How do I reset my password?';
        $record->preview = 'Use the reset link.';
        $record->url = 'index.php?action=faq&id=1';
        $record->visits = '1 view';
        $record->date = '2026-11-14';

        $html = $this->renderContent([
            'topRecordsList' => [$record],
            'latestRecordsList' => [$record],
            'trendingRecordsList' => [],
        ]);

        static::assertSame(1, substr_count($html, 'class="pmf-faq-list-section'));
        static::assertSame(2, substr_count($html, 'role="tab"'));
        static::assertSame(2, substr_count($html, 'role="tabpanel"'));
        static::assertStringContainsString('data-bs-target="#pmf-faq-list-pane-top"', $html);
        static::assertStringContainsString('id="pmf-faq-list-pane-latest"', $html);
        static::assertStringNotContainsString('pmf-faq-list-pane-trending', $html);
    }

    /**
     * Renders the content block with the tagName filter replaced, so no test needs a database.
     *
     * @param array<string, mixed> $context
     * @throws Exception
     */
    private function renderContent(array $context): string
    {
        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
        $twigWrapper->addFilter(new TwigFilter('tagName', static fn(int $tagId): string => 'tag-' . $tagId));

        return $twigWrapper->loadTemplate('startpage.twig')->renderBlock('content', [
            'topRecordsList' => [],
            'latestRecordsList' => [],
            'trendingRecordsList' => [],
            'newsList' => [],
            'tagsList' => [],
            'startPageCategories' => false,
            'stickyRecordsList' => [],
            ...$context,
        ]);
    }
}
