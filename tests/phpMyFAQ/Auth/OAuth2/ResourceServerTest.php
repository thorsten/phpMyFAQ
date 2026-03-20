<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2;

use phpMyFAQ\Configuration;
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
