<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiTest extends TestCase
{
    private Api $api;
    private Configuration $configuration;
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->api = new Api($this->configuration);
        $this->api->setHttpClient($this->httpClient);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws DecodingExceptionInterface
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testGetVersionsSuccess(): void
    {
        $versions = [
            'stable' => '5.0.0',
            'development' => '5.1.0',
            'nightly' => '5.2.0',
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $response->method('toArray')->willReturn($versions);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.phpmyfaq.de/versions')
            ->willReturn($response);

        $this->configuration->method('getVersion')->willReturn('5.0.0');

        $expected = [
            'installed' => '5.0.0',
            'stable' => '5.0.0',
            'development' => '5.1.0',
            'nightly' => '5.2.0'
        ];

        $this->assertEquals($expected, $this->api->getVersions());
    }

    public function testIsVerifiedSuccess(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"hash1": "abc", "hash2": "def"}');
        $response->method('getStatusCode')->willReturn(Response::HTTP_OK);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->assertTrue($this->api->isVerified());
    }

    public function testIsVerifiedFailure(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException('JsonException');
        $this->api->isVerified();
    }

    public function testIsVerifiedTransportException(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new TransportException());

        $this->expectException('Symfony\Component\HttpClient\Exception\TransportException');
        $this->api->isVerified();
    }
}
