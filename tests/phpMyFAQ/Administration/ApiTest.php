<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiTest extends TestCase
{
    private Api $api;
    private Configuration $configuration;
    private System $system;
    private HttpClientInterface $httpClient;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createStub(Configuration::class);
        $this->system = $this->createStub(System::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->api = new Api($this->configuration, $this->system);
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

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $response->method('toArray')->willReturn($versions);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.phpmyfaq.de/versions')
            ->willReturn($response);

        $this->configuration->method('getVersion')->willReturn('5.0.0');

        $expected = [
            'installed' => '5.0.0',
            'stable' => '5.0.0',
            'development' => '5.1.0',
            'nightly' => '5.2.0',
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
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"hash1": "abc", "hash2": "def"}');
        $response->method('getStatusCode')->willReturn(Response::HTTP_OK);

        $this->httpClient
            ->expects($this->once())
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
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException('JsonException');
        $this->api->isVerified();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function testIsVerifiedTransportException(): void
    {
        $this->httpClient
            ->expects($this->once())
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
        $this->configuration = $this->createStub(Configuration::class);
        $mockSystem = $this->createPartialMock(System::class, ['createHashes']);

        $mockSystem
            ->expects($this->once())
            ->method('createHashes')
            ->willReturn(json_encode([
                'hash1' => 'abc123',
                'hash2' => 'def456',
                'hash3' => 'ghi789',
            ]));

        $api = new Api($this->configuration, $mockSystem);

        $api->setRemoteHashes(json_encode([
            'hash1' => 'abc123',
            'hash3' => 'ghi789',
        ]));

        $result = $api->getVerificationIssues();

        $expected = ['hash2' => 'def456'];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testGetVersionsWithHttpError(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(Response::HTTP_NOT_FOUND);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.phpmyfaq.de/versions')
            ->willReturn($response);

        $this->configuration->method('getVersion')->willReturn('4.0.0');

        $expected = [
            'installed' => '4.0.0',
            'stable' => 'n/a',
            'development' => 'n/a',
            'nightly' => 'n/a',
        ];

        $this->assertEquals($expected, $this->api->getVersions());
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetVersionsWithTransportException(): void
    {
        $transportException = new TransportException('Network error');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($transportException);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Network error');

        $this->api->getVersions();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testIsVerifiedWithInvalidJson(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('invalid json content');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(\JsonException::class);
        $this->api->isVerified();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function testIsVerifiedWithEmptyResponse(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(\JsonException::class);
        $this->api->isVerified();
    }

    public function testIsVerifiedWithNonArrayJson(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('"just a string"');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->api->isVerified();

        $this->assertFalse($result);
    }

    public function testIsVerifiedWithServerException(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willThrowException($this->createMock(ServerExceptionInterface::class));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('phpMyFAQ Verification API is not available:');

        $this->api->isVerified();
    }

    public function testGetVerificationIssuesWithNullRemoteHashes(): void
    {
        $this->system->method('createHashes')->willReturn('{"hash1": "abc"}');

        $this->api->setRemoteHashes(null);

        $this->expectException(\JsonException::class);
        $this->api->getVerificationIssues();
    }

    public function testGetVerificationIssuesWithInvalidRemoteHashes(): void
    {
        $this->system->method('createHashes')->willReturn('{"hash1": "abc"}');

        $this->api->setRemoteHashes('invalid json');

        $this->expectException(\JsonException::class);
        $this->api->getVerificationIssues();
    }

    public function testGetVerificationIssuesWithInvalidSystemHashes(): void
    {
        $this->system->method('createHashes')->willReturn('invalid json');

        $this->api->setRemoteHashes('{"hash1": "abc"}');

        $this->expectException(\JsonException::class);
        $this->api->getVerificationIssues();
    }

    public function testGetVerificationIssuesWithIdenticalHashes(): void
    {
        $hashes = '{"hash1": "abc", "hash2": "def"}';

        $this->system->method('createHashes')->willReturn($hashes);
        $this->api->setRemoteHashes($hashes);

        $result = $this->api->getVerificationIssues();

        $this->assertEmpty($result);
    }

    public function testGetVerificationIssuesWithEmptyHashes(): void
    {
        $this->system->method('createHashes')->willReturn('{}');
        $this->api->setRemoteHashes('{}');

        $result = $this->api->getVerificationIssues();

        $this->assertEmpty($result);
    }

    public function testSetHttpClient(): void
    {
        $newHttpClient = $this->createMock(HttpClientInterface::class);

        $this->api->setHttpClient($newHttpClient);

        // Verify the client was set by making a request
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $response->method('toArray')->willReturn(['stable' => '1.0.0', 'development' => '1.1.0', 'nightly' => '1.2.0']);

        $newHttpClient->expects($this->once())->method('request')->willReturn($response);

        $this->configuration->method('getVersion')->willReturn('1.0.0');

        $this->api->getVersions();
    }

    public function testSetRemoteHashesReturnsInstance(): void
    {
        $result = $this->api->setRemoteHashes('{"test": "hash"}');

        $this->assertInstanceOf(Api::class, $result);
        $this->assertSame($this->api, $result);
    }

    public function testSetRemoteHashesWithNull(): void
    {
        $result = $this->api->setRemoteHashes(null);

        $this->assertInstanceOf(Api::class, $result);
    }

    public function testConstructorInitializesHttpClient(): void
    {
        // Test that constructor creates HttpClient with correct configuration
        $api = new Api($this->configuration, $this->system);

        // Use reflection to verify the HttpClient is properly initialized
        $reflection = new ReflectionClass($api);
        $property = $reflection->getProperty('httpClient');
        $httpClient = $property->getValue($api);

        $this->assertInstanceOf(HttpClientInterface::class, $httpClient);
    }

    public function testApiUrlIsCorrect(): void
    {
        // Verify that the API URL is correctly set
        $reflection = new ReflectionClass($this->api);
        $property = $reflection->getProperty('apiUrl');
        $apiUrl = $property->getValue($this->api);

        $this->assertEquals('https://api.phpmyfaq.de/', $apiUrl);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */
    public function testIsVerifiedSetsRemoteHashes(): void
    {
        $expectedHashes = '{"hash1": "abc", "hash2": "def"}';

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($expectedHashes);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->api->isVerified();

        // Verify that remoteHashes was set
        $reflection = new ReflectionClass($this->api);
        $property = $reflection->getProperty('remoteHashes');
        $remoteHashes = $property->getValue($this->api);

        $this->assertEquals($expectedHashes, $remoteHashes);
    }
}
