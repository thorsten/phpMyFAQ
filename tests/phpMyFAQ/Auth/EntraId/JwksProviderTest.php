<?php

namespace phpMyFAQ\Auth\EntraId;

use Firebase\JWT\Key;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AllowMockObjectsWithoutExpectations]
class JwksProviderTest extends TestCase
{
    private static string $publicKey;
    private static string $modulus;
    private static string $exponent;

    private string $cacheDir;

    public static function setUpBeforeClass(): void
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $details = openssl_pkey_get_details($resource);

        self::$publicKey = $details['key'];
        self::$modulus = self::base64UrlEncode($details['rsa']['n']);
        self::$exponent = self::base64UrlEncode($details['rsa']['e']);
    }

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/phpmyfaq-jwks-test-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->cacheDir);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function jwksJson(array $overrides = []): string
    {
        $key = array_merge([
            'kty' => 'RSA',
            'kid' => 'test-kid',
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => self::$modulus,
            'e' => self::$exponent,
        ], $overrides);

        return json_encode(['keys' => [$key]], JSON_THROW_ON_ERROR);
    }

    private function mockResponse(string $body, int $status = 200): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($body);
        $response->method('getStatusCode')->willReturn($status);
        return $response;
    }

    public function testFetchesAndParsesJwks(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://login.microsoftonline.com/my-tenant/discovery/v2.0/keys')
            ->willReturn($this->mockResponse($this->jwksJson()));

        $provider = new JwksProvider($httpClient, $this->cacheDir);
        $keys = $provider->getKeys('my-tenant');

        $this->assertArrayHasKey('test-kid', $keys);
        $this->assertInstanceOf(Key::class, $keys['test-kid']);
        $this->assertSame('RS256', $keys['test-kid']->getAlgorithm());
    }

    public function testCachesJwksAcrossCalls(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($this->mockResponse($this->jwksJson()));

        $provider = new JwksProvider($httpClient, $this->cacheDir);
        $provider->getKeys('tenant-a');

        $keys = $provider->getKeys('tenant-a');
        $this->assertArrayHasKey('test-kid', $keys);
    }

    public function testCachePersistsAcrossInstances(): void
    {
        $httpClient1 = $this->createMock(HttpClientInterface::class);
        $httpClient1
            ->expects($this->once())
            ->method('request')
            ->willReturn($this->mockResponse($this->jwksJson()));

        $provider1 = new JwksProvider($httpClient1, $this->cacheDir);
        $provider1->getKeys('tenant-a');

        $httpClient2 = $this->createMock(HttpClientInterface::class);
        $httpClient2->expects($this->never())->method('request');

        $provider2 = new JwksProvider($httpClient2, $this->cacheDir);
        $keys = $provider2->getKeys('tenant-a');

        $this->assertArrayHasKey('test-kid', $keys);
    }

    public function testRefetchesWhenCacheExpired(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturn($this->mockResponse($this->jwksJson()));

        $provider = new JwksProvider($httpClient, $this->cacheDir);
        $provider->getKeys('tenant-a');

        $cacheFile = $this->cacheDir . '/jwks-' . sha1('tenant-a') . '.json';
        $this->assertFileExists($cacheFile);
        touch($cacheFile, time() - 90_000);

        $provider->getKeys('tenant-a');
    }

    public function testSeparateTenantsCachedSeparately(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturn($this->mockResponse($this->jwksJson()));

        $provider = new JwksProvider($httpClient, $this->cacheDir);
        $provider->getKeys('tenant-a');
        $provider->getKeys('tenant-b');
    }

    public function testUrlEncodesTenantId(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://login.microsoftonline.com/weird%2Ftenant/discovery/v2.0/keys')
            ->willReturn($this->mockResponse($this->jwksJson()));

        $provider = new JwksProvider($httpClient, $this->cacheDir);
        $provider->getKeys('weird/tenant');
    }

    public function testThrowsOnNon200Response(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturn($this->mockResponse('Server Error', 503));

        $provider = new JwksProvider($httpClient, $this->cacheDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 503');
        $provider->getKeys('tenant-a');
    }

    public function testThrowsOnMalformedJson(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturn($this->mockResponse('not-json'));

        $provider = new JwksProvider($httpClient, $this->cacheDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Malformed JWKS');
        $provider->getKeys('tenant-a');
    }

    public function testThrowsWhenKeysFieldMissing(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturn($this->mockResponse(json_encode(['foo' => 'bar'])));

        $provider = new JwksProvider($httpClient, $this->cacheDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Malformed JWKS');
        $provider->getKeys('tenant-a');
    }

    public function testIgnoresInvalidCacheContentAndRefetches(): void
    {
        mkdir($this->cacheDir, 0o775, true);
        $cacheFile = $this->cacheDir . '/jwks-' . sha1('tenant-a') . '.json';
        file_put_contents($cacheFile, 'not-json');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($this->mockResponse($this->jwksJson()));

        $provider = new JwksProvider($httpClient, $this->cacheDir);
        $keys = $provider->getKeys('tenant-a');

        $this->assertArrayHasKey('test-kid', $keys);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
