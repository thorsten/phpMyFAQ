<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2;

use League\OAuth2\Server\AuthorizationServer as LeagueAuthorizationServer;
use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class AuthorizationServerTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function testIssueTokenUsesConfiguredIssuer(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $server = new AuthorizationServer($configuration);

        $server->setTokenIssuer(static fn(Request $request): array => [
            'body' => ['access_token' => 'token-123', 'token_type' => 'Bearer'],
            'status' => 200,
            'headers' => ['Cache-Control' => 'no-store'],
        ]);

        $result = $server->issueToken(new Request());

        $this->assertSame(200, $result['status']);
        $this->assertSame('token-123', $result['body']['access_token']);
        $this->assertSame('no-store', $result['headers']['Cache-Control']);
    }

    public function testIssueTokenThrowsWhenOauth2IsDisabled(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())->method('get')->with('oauth2.enable')->willReturn('false');
        $server = new AuthorizationServer($configuration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OAuth2 authorization server is disabled');

        $server->issueToken(Request::create('https://localhost/token', 'POST'));
    }

    public function testIssueTokenThrowsWhenKeyConfigurationIsMissing(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.enable',         'true'],
                ['oauth2.privateKeyPath', ''],
                ['oauth2.encryptionKey',  ''],
            ]);
        $server = new AuthorizationServer($configuration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OAuth2 token issuance failed: OAuth2 keys are not configured.');

        $server->issueToken(Request::create('https://localhost/token', 'POST'));
    }

    public function testCompleteAuthorizationWrapsConfigurationFailure(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.privateKeyPath', ''],
                ['oauth2.encryptionKey',  ''],
            ]);
        $server = new AuthorizationServer($configuration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OAuth2 authorization failed: OAuth2 keys are not configured.');

        $server->completeAuthorization(Request::create('https://localhost/authorize', 'GET'), '123', true);
    }

    public function testPrivateHelpersHandleWhitespaceAndInvalidIntervals(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $logger = $this->createMock(\Monolog\Logger::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.publicKeyPath', '  /tmp/key.pem  '],
            ]);
        $configuration->method('getLogger')->willReturn($logger);
        $logger->expects($this->once())->method('notice')->with($this->stringContains('Invalid OAuth interval'));

        $server = new AuthorizationServer($configuration);
        $reflection = new ReflectionClass($server);

        $getConfigString = $reflection->getMethod('getConfigString');
        $this->assertSame('/tmp/key.pem', $getConfigString->invoke($server, 'oauth2.publicKeyPath'));

        $parseInterval = $reflection->getMethod('parseInterval');
        $interval = $parseInterval->invoke($server, 'not-an-interval', 'PT1H');
        $this->assertInstanceOf(\DateInterval::class, $interval);
        $this->assertSame('PT1H', $interval->format('PT%hH'));
    }

    public function testToPsr7RequestCopiesHeadersBodyAndParameters(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $server = new AuthorizationServer($configuration);
        $reflection = new ReflectionClass($server);
        $method = $reflection->getMethod('toPsr7Request');

        $request = Request::create(
            'https://localhost/token?foo=bar',
            'POST',
            ['grant_type' => 'client_credentials'],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer secret'],
            '{"scope":"read"}',
        );

        $psrRequest = $method->invoke($server, $request);

        $this->assertSame('POST', $psrRequest->getMethod());
        $this->assertSame('Bearer secret', $psrRequest->getHeaderLine('authorization'));
        $this->assertSame('bar', $psrRequest->getQueryParams()['foo']);
        $this->assertSame('client_credentials', $psrRequest->getParsedBody()['grant_type']);
        $this->assertSame('{"scope":"read"}', (string) $psrRequest->getBody());
    }

    public function testBuildLeagueAuthorizationServerReturnsInstanceWithConfiguredKeys(): void
    {
        [$privateKeyPath] = $this->getKeyPairPaths();

        $configuration = $this->createMock(Configuration::class);
        $logger = $this->createMock(\Monolog\Logger::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.privateKeyPath',  $privateKeyPath],
                ['oauth2.encryptionKey',   'encryption-key-123456'],
                ['oauth2.accessTokenTTL',  'PT1H'],
                ['oauth2.refreshTokenTTL', 'P1M'],
                ['oauth2.authCodeTTL',     'PT10M'],
            ]);
        $configuration->method('getLogger')->willReturn($logger);

        $server = new AuthorizationServer($configuration);
        $reflection = new ReflectionClass($server);
        $method = $reflection->getMethod('buildLeagueAuthorizationServer');

        $result = $method->invoke($server);

        $this->assertInstanceOf(LeagueAuthorizationServer::class, $result);
    }

    public function testIssueTokenReturnsOauthErrorPayloadForInvalidRequest(): void
    {
        [$privateKeyPath] = $this->getKeyPairPaths();

        $configuration = $this->createMock(Configuration::class);
        $logger = $this->createMock(\Monolog\Logger::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.enable',          'true'],
                ['oauth2.privateKeyPath',  $privateKeyPath],
                ['oauth2.encryptionKey',   'encryption-key-123456'],
                ['oauth2.accessTokenTTL',  'PT1H'],
                ['oauth2.refreshTokenTTL', 'P1M'],
                ['oauth2.authCodeTTL',     'PT10M'],
            ]);
        $configuration->method('getLogger')->willReturn($logger);

        $server = new AuthorizationServer($configuration);
        $result = $server->issueToken(Request::create('https://localhost/token', 'POST'));

        $this->assertIsArray($result['body']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertGreaterThanOrEqual(400, $result['status']);
    }

    public function testCompleteAuthorizationReturnsOauthErrorPayloadForInvalidRequest(): void
    {
        [$privateKeyPath] = $this->getKeyPairPaths();

        $configuration = $this->createMock(Configuration::class);
        $logger = $this->createMock(\Monolog\Logger::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.privateKeyPath',  $privateKeyPath],
                ['oauth2.encryptionKey',   'encryption-key-123456'],
                ['oauth2.accessTokenTTL',  'PT1H'],
                ['oauth2.refreshTokenTTL', 'P1M'],
                ['oauth2.authCodeTTL',     'PT10M'],
            ]);
        $configuration->method('getLogger')->willReturn($logger);

        $server = new AuthorizationServer($configuration);
        $result = $server->completeAuthorization(Request::create('https://localhost/authorize', 'GET'), '123', true);

        $this->assertIsArray($result['body']);
        $this->assertArrayHasKey('error', $result['body']);
        $this->assertGreaterThanOrEqual(400, $result['status']);
    }

    private function getKeyPairPaths(): array
    {
        $privateKeyPath = tempnam(sys_get_temp_dir(), 'pmf-oauth-private-');
        $publicKeyPath = tempnam(sys_get_temp_dir(), 'pmf-oauth-public-');
        copy(__DIR__ . '/../../../../.docker/cert-key.pem', $privateKeyPath);
        copy(__DIR__ . '/../../../../.docker/cert.pem', $publicKeyPath);
        chmod($privateKeyPath, 0600);
        chmod($publicKeyPath, 0600);

        $this->temporaryFiles[] = $privateKeyPath;
        $this->temporaryFiles[] = $publicKeyPath;

        return [$privateKeyPath, $publicKeyPath];
    }
}
