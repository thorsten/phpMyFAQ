<?php

namespace phpMyFAQ\Bootstrap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(SearchClientFactory::class)]
class SearchClientFactoryTest extends TestCase
{
    public function testWaitForHealthyReturnsImmediatelyOnSuccess(): void
    {
        $response = new MockResponse('{"status":"green"}', ['http_code' => 200]);
        $httpClient = new MockHttpClient([$response]);

        // Should return without exception and within the timeout
        SearchClientFactory::waitForHealthy('http://localhost:9200', 2, $httpClient);

        $this->assertEquals(1, $httpClient->getRequestsCount());
    }

    public function testWaitForHealthyRetriesOnFailure(): void
    {
        $responses = [
            new MockResponse('', ['http_code' => 503]),
            new MockResponse('{"status":"yellow"}', ['http_code' => 200]),
        ];
        $httpClient = new MockHttpClient($responses);

        SearchClientFactory::waitForHealthy('http://localhost:9200', 5, $httpClient);

        $this->assertEquals(2, $httpClient->getRequestsCount());
    }

    public function testWaitForHealthyDoesNotThrowOnTimeout(): void
    {
        // All requests will fail, but should not throw
        $httpClient = new MockHttpClient(function () {
            throw new \RuntimeException('Connection refused');
        });

        SearchClientFactory::waitForHealthy('http://localhost:9200', 1, $httpClient);

        $this->assertTrue(true);
    }

    public function testWaitForHealthyTrimsTrailingSlash(): void
    {
        $response = new MockResponse('{"status":"green"}', ['http_code' => 200]);
        $httpClient = new MockHttpClient([$response]);

        SearchClientFactory::waitForHealthy('http://localhost:9200/', 2, $httpClient);

        $this->assertEquals(1, $httpClient->getRequestsCount());
    }
}
