<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SearchController::class)]
#[UsesNamespace('phpMyFAQ')]
final class SearchControllerWebTest extends ControllerWebTestCase
{
    public function testSearchPageRendersAdvancedSearchView(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
        ]);

        $response = $this->requestPublic('GET', '/search.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<h2 class="mb-4 border-bottom">Advanced search</h2>', $response);
        self::assertResponseContains('id="pmf-main-search"', $response);
    }

    public function testSearchPageRendersTaggedEntriesView(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
        ]);

        $response = $this->requestPublic('GET', '/search.html?tagging_id=12');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<h2 class="mb-4 border-bottom">Tagged entries</h2>', $response);
        self::assertResponseContains('<h4 class="fst-italic">Tags</h4>', $response);
        self::assertStringNotContainsString('id="pmf-main-search"', (string) $response->getContent());
    }

    public function testSearchPageChecksAllLanguagesToggleWhenRequested(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
        ]);

        $response = $this->requestPublic('GET', '/search.html?pmf-all-languages=all');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-all-languages" value="all"', $response);
        self::assertResponseContains('checked', $response);
    }

    public function testAdvancedSearchPageShowsPopularSearchesSidebar(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
        ]);

        $response = $this->requestPublic('GET', '/search.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<h4 class="fst-italic">Most popular searches</h4>', $response);
        self::assertStringNotContainsString('Add Search Word', (string) $response->getContent());
    }

    public function testTagRouteRedirectsToSearch(): void
    {
        $response = $this->requestPublic('GET', '/tags/12/example-tag.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('/search.html?tagging_id=12', $response->headers->get('Location'));
    }

    public function testTagRouteFallsBackToZeroForInvalidTagId(): void
    {
        $response = $this->requestPublic('GET', '/tags/not-a-number/example-tag.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('/search.html?tagging_id=0', $response->headers->get('Location'));
    }

    public function testPaginatedTagRouteRedirectsToSearchWithPage(): void
    {
        $response = $this->requestPublic('GET', '/tags/12/3/example-tag.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('/search.html?tagging_id=12&seite=3', $response->headers->get('Location'));
    }

    public function testPaginatedTagRouteFallsBackToFirstPageForInvalidPage(): void
    {
        $response = $this->requestPublic('GET', '/tags/12/not-a-number/example-tag.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('/search.html?tagging_id=12&seite=1', $response->headers->get('Location'));
    }

    public function testPaginatedTagRouteFallsBackForInvalidTagAndPage(): void
    {
        $response = $this->requestPublic('GET', '/tags/not-a-number/not-a-number/example-tag.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('/search.html?tagging_id=0&seite=1', $response->headers->get('Location'));
    }
}
