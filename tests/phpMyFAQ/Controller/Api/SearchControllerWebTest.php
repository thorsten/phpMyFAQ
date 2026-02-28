<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SearchController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractApiController::class)]
#[UsesClass(PaginatedResponseOptions::class)]
final class SearchControllerWebTest extends ControllerWebTestCase
{
    public function testSearchEndpointReturnsJsonForQuery(): void
    {
        $response = $this->requestApi('GET', '/v3.2/search?q=test');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testPopularSearchEndpointReturnsJson(): void
    {
        $response = $this->requestApi('GET', '/v3.2/searches/popular');

        self::assertContains($response->getStatusCode(), [200, 404]);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
