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
        $this->overrideConfigurationValues([
            'api.enableAccess' => true,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
        ], 'api');

        $response = $this->requestApi('GET', '/v4.0/search?q=test');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testPopularSearchEndpointReturnsJson(): void
    {
        $this->overrideConfigurationValues([
            'api.enableAccess' => true,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
        ], 'api');

        $response = $this->requestApi('GET', '/v4.0/searches/popular');

        self::assertContains($response->getStatusCode(), [200, 404]);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
