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

    public function testCategoryPageRendersSearchBandNotHero(): void
    {
        $response = $this->requestPublic('GET', '/show-categories.html');
        $content = (string) $response->getContent();

        self::assertResponseStatusCodeSame(200, $response);
        self::assertStringContainsString('class="pmf-search-band', $content);
        self::assertStringNotContainsString('class="pmf-hero', $content);
        self::assertSame(1, substr_count($content, 'id="pmf-search-autocomplete"'));
    }
}
