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
