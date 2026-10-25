<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class ResourceServerTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                // nosemgrep: php.lang.security.unlink-use.unlink-use - test-created temp file path
                @unlink($file);
            }
        }
    }

    public function testAuthenticateReturnsNullWithoutBearerHeader(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $server = new ResourceServer($configuration);

        $this->assertNull($server->authenticate(new Request()));
    }

    public function testAuthenticateUsesConfiguredValidator(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $server = new ResourceServer($configuration);
        $server->setTokenValidator(static fn(Request $request): ?int => 123);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer oauth-access-token']);
        $this->assertSame(123, $server->authenticate($request));
    }

    public function testAuthenticateReturnsNullWhenOauth2IsDisabled(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())->method('get')->with('oauth2.enable')->willReturn('false');
        $server = new ResourceServer($configuration);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer oauth-access-token']);

        $this->assertNull($server->authenticate($request));
    }

    public function testAuthenticateReturnsNullWhenPublicKeyPathIsMissing(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.enable',        'true'],
                ['oauth2.publicKeyPath', ''],
            ]);
        $server = new ResourceServer($configuration);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer oauth-access-token']);

        $this->assertNull($server->authenticate($request));
    }

    public function testPrivateHelpersNormalizeConfigurationAndConvertRequests(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.enable',        '1'],
                ['oauth2.publicKeyPath', '  /tmp/public.pem  '],
            ]);

        $server = new ResourceServer($configuration);
        $reflection = new ReflectionClass($server);

        $isEnabled = $reflection->getMethod('isEnabled');
        $this->assertTrue($isEnabled->invoke($server));

        $getConfigString = $reflection->getMethod('getConfigString');
        $this->assertSame('/tmp/public.pem', $getConfigString->invoke($server, 'oauth2.publicKeyPath'));

        $toPsr7Request = $reflection->getMethod('toPsr7Request');
        $request = Request::create(
            'https://localhost/api?foo=bar',
            'POST',
            ['scope' => 'read'],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer oauth-access-token'],
            '{"body":"value"}',
        );

        $psrRequest = $toPsr7Request->invoke($server, $request);

        $this->assertSame('Bearer oauth-access-token', $psrRequest->getHeaderLine('authorization'));
        $this->assertSame('bar', $psrRequest->getQueryParams()['foo']);
        $this->assertSame('read', $psrRequest->getParsedBody()['scope']);
        $this->assertSame('{"body":"value"}', (string) $psrRequest->getBody());
    }

    public function testAuthenticateReturnsNullForEmptyAuthorizationHeader(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $server = new ResourceServer($configuration);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => '']);

        $this->assertNull($server->authenticate($request));
    }

    public function testAuthenticateReturnsNullForBasicAuthHeader(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $server = new ResourceServer($configuration);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Basic dXNlcjpwYXNz']);

        $this->assertNull($server->authenticate($request));
    }

    public function testIsEnabledRecognizesAllTrueValues(): void
    {
        $reflection = new ReflectionClass(ResourceServer::class);
        $isEnabled = $reflection->getMethod('isEnabled');

        foreach ([true, 'true', 1, '1'] as $value) {
            $configuration = $this->createMock(Configuration::class);
            $configuration->method('get')->willReturn($value);

            $server = new ResourceServer($configuration);

            $this->assertTrue($isEnabled->invoke($server), sprintf('Failed for %s', var_export($value, true)));
        }
    }

    public function testGetConfigStringReturnsEmptyForNonStringValues(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->willReturn(123);

        $server = new ResourceServer($configuration);
        $reflection = new ReflectionClass($server);
        $getConfigString = $reflection->getMethod('getConfigString');

        $this->assertSame('', $getConfigString->invoke($server, 'oauth2.publicKeyPath'));
    }

    public function testAuthenticateReturnsNullWhenValidationFailsWithConfiguredServer(): void
    {
        [, $publicKeyPath] = $this->getKeyPairPaths();

        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.enable',        'true'],
                ['oauth2.publicKeyPath', $publicKeyPath],
            ]);

        $server = new ResourceServer($configuration);
        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer invalid-oauth-token']);

        $this->assertNull($server->authenticate($request));
    }

    public function testAuthenticateReturnsTheUserIdOfAValidAccessToken(): void
    {
        [$privateKeyPath, $publicKeyPath] = $this->getKeyPairPaths();
        $server = $this->createConfiguredResourceServer($publicKeyPath, revoked: 0);

        $token = $this->signAccessToken($privateKeyPath, ['sub' => '42']);
        $request = new Request(server: ['HTTP_HOST' => 'localhost', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertSame(42, $server->authenticate($request));
    }

    public function testAuthenticateReturnsNullForAValidAccessTokenWithoutAUser(): void
    {
        [$privateKeyPath, $publicKeyPath] = $this->getKeyPairPaths();
        $server = $this->createConfiguredResourceServer($publicKeyPath, revoked: 0);

        $token = $this->signAccessToken($privateKeyPath, []);
        $request = new Request(server: ['HTTP_HOST' => 'localhost', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertNull($server->authenticate($request));
    }

    public function testAuthenticateReturnsNullForARevokedAccessToken(): void
    {
        [$privateKeyPath, $publicKeyPath] = $this->getKeyPairPaths();
        $server = $this->createConfiguredResourceServer($publicKeyPath, revoked: 1);

        $token = $this->signAccessToken($privateKeyPath, ['sub' => '42']);
        $request = new Request(server: ['HTTP_HOST' => 'localhost', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertNull($server->authenticate($request));
    }

    private function createConfiguredResourceServer(string $publicKeyPath, int $revoked): ResourceServer
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $db->method('query')->willReturn(true);
        $db->method('fetchObject')->willReturn((object) ['revoked' => $revoked]);

        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oauth2.enable',        'true'],
                ['oauth2.publicKeyPath', $publicKeyPath],
            ]);
        $configuration->method('getDb')->willReturn($db);

        return new ResourceServer($configuration);
    }

    /**
     * @param array<string, mixed> $additionalClaims
     */
    private function signAccessToken(string $privateKeyPath, array $additionalClaims): string
    {
        $now = time();
        $claims = $additionalClaims
        + [
            'jti' => 'access-token-id',
            'aud' => ['phpmyfaq-client'],
            'iat' => $now - 10,
            'nbf' => $now - 10,
            'exp' => $now + 3600,
            'scopes' => ['faq.read'],
        ];

        $encode = static fn(string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $signingInput = $encode(json_encode(['typ' => 'JWT', 'alg' => 'RS256'])) . '.' . $encode(json_encode($claims));

        $signature = '';
        openssl_sign($signingInput, $signature, (string) file_get_contents($privateKeyPath), OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . $encode($signature);
    }

    private function getKeyPairPaths(): array
    {
        $privateKeyPath = tempnam(sys_get_temp_dir(), 'pmf-oauth-private-');
        $publicKeyPath = tempnam(sys_get_temp_dir(), 'pmf-oauth-public-');
        copy(__DIR__ . '/../../../fixtures/oauth2/private.key', $privateKeyPath);
        copy(__DIR__ . '/../../../fixtures/oauth2/public.key', $publicKeyPath);
        chmod($privateKeyPath, 0600);
        chmod($publicKeyPath, 0600);

        $this->temporaryFiles[] = $privateKeyPath;
        $this->temporaryFiles[] = $publicKeyPath;

        return [$privateKeyPath, $publicKeyPath];
    }
}
