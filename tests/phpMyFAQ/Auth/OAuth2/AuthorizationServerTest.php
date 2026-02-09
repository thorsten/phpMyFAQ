<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class AuthorizationServerTest extends TestCase
{
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

    public function testIssueTokenThrowsWhenNotConfiguredAndLeagueMissing(): void
    {
        if (class_exists(\League\OAuth2\Server\AuthorizationServer::class)) {
            $this->markTestSkipped('league/oauth2-server is installed in this environment.');
        }

        $configuration = $this->createMock(Configuration::class);
        $server = new AuthorizationServer($configuration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OAuth2 server dependency not installed');

        $server->issueToken(new Request());
    }

    public function testIssueTokenThrowsWhenOauth2IsDisabled(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->with('oauth2.enable')->willReturn('false');
        $server = new AuthorizationServer($configuration);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OAuth2 authorization server is disabled');

        $server->issueToken(new Request());
    }
}
