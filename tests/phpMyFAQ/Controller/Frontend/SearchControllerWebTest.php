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
    }

    public function testTagRouteRedirectsToSearch(): void
    {
        $response = $this->requestPublic('GET', '/tags/12/example-tag.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('/search.html?tagging_id=12', $response->headers->get('Location'));
    }

    public function testPaginatedTagRouteRedirectsToSearchWithPage(): void
    {
        $response = $this->requestPublic('GET', '/tags/12/3/example-tag.html');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame('/search.html?tagging_id=12&seite=3', $response->headers->get('Location'));
    }
}
