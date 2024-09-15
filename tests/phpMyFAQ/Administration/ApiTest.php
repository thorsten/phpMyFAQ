<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\System;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->api = new Api($this->configuration, new System());
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

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */
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

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
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

    /**
     * @throws \JsonException|Exception
     */
    public function testGetVerificationIssues(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $mockSystem = $this->getMockBuilder(System::class)
            ->onlyMethods(['createHashes'])
            ->getMock();

        $mockSystem->expects($this->once())
            ->method('createHashes')
            ->willReturn(json_encode([
                'hash1' => 'abc123',
                'hash2' => 'def456',
                'hash3' => 'ghi789'
            ]));

        $api = new Api($this->configuration, $mockSystem);

        $api->setRemoteHashes(json_encode([
            'hash1' => 'abc123',
            'hash3' => 'ghi789'
        ]));

        $result = $api->getVerificationIssues();

        $expected = ['hash2' => 'def456'];
        $this->assertEquals($expected, $result);
    }
}
