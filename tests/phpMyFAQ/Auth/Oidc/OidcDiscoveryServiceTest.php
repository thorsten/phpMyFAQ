<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(OidcDiscoveryService::class)]
#[UsesClass(OidcProviderConfig::class)]
#[UsesClass(OidcDiscoveryDocument::class)]
#[UsesClass(OidcClientConfig::class)]
final class OidcDiscoveryServiceTest extends TestCase
{
    public function testDiscoverReturnsTypedDiscoveryDocument(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->with(false)
            ->willReturn(json_encode([
                'issuer' => 'https://sso.example.com/realms/faq',
                'authorization_endpoint' => 'https://sso.example.com/realms/faq/protocol/openid-connect/auth',
                'token_endpoint' => 'https://sso.example.com/realms/faq/protocol/openid-connect/token',
                'userinfo_endpoint' => 'https://sso.example.com/realms/faq/protocol/openid-connect/userinfo',
                'jwks_uri' => 'https://sso.example.com/realms/faq/protocol/openid-connect/certs',
                'end_session_endpoint' => 'https://sso.example.com/realms/faq/protocol/openid-connect/logout',
            ], JSON_THROW_ON_ERROR));

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://sso.example.com/realms/faq/.well-known/openid-configuration')
            ->willReturn($response);

        $service = new OidcDiscoveryService($httpClient);
        $document = $service->discover(new OidcProviderConfig(
            provider: 'keycloak',
            enabled: true,
            discoveryUrl: 'https://sso.example.com/realms/faq/.well-known/openid-configuration',
            client: new OidcClientConfig(
                clientId: 'pmf-web',
                clientSecret: 'secret',
                redirectUri: 'https://faq.example.com/auth/keycloak/callback',
                scopes: ['openid', 'profile', 'email'],
            ),
            autoProvision: false,
            logoutRedirectUrl: '',
        ));

        $this->assertSame('https://sso.example.com/realms/faq', $document->issuer);
        $this->assertStringContainsString('/auth', $document->authorizationEndpoint);
        $this->assertStringContainsString('/token', $document->tokenEndpoint);
        $this->assertStringContainsString('/userinfo', $document->userInfoEndpoint);
        $this->assertStringContainsString('/certs', $document->jwksUri);
        $this->assertStringContainsString('/logout', (string) $document->endSessionEndpoint);
    }

    public function testDiscoverThrowsWhenStatusCodeIndicatesError(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(503);
        $response->method('getContent')->willReturn('');

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $service = new OidcDiscoveryService($httpClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OIDC discovery request failed for keycloak with status 503');

        $service->discover(new OidcProviderConfig(
            provider: 'keycloak',
            enabled: true,
            discoveryUrl: 'https://sso.example.com/realms/faq/.well-known/openid-configuration',
            client: new OidcClientConfig('pmf-web', 'secret', 'https://faq.example.com/cb', ['openid']),
            autoProvision: false,
            logoutRedirectUrl: '',
        ));
    }

    public function testDiscoverThrowsWhenPayloadIsNotAnArray(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn('"a-string"');

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $service = new OidcDiscoveryService($httpClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OIDC discovery response is not a JSON object/array');

        $service->discover(new OidcProviderConfig(
            provider: 'keycloak',
            enabled: true,
            discoveryUrl: 'https://sso.example.com/realms/faq/.well-known/openid-configuration',
            client: new OidcClientConfig('pmf-web', 'secret', 'https://faq.example.com/cb', ['openid']),
            autoProvision: false,
            logoutRedirectUrl: '',
        ));
    }

    public function testDiscoverThrowsForInvalidJson(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getContent')->with(false)->willReturn('not-json');

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $service = new OidcDiscoveryService($httpClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OIDC discovery response is not valid JSON');

        $service->discover(new OidcProviderConfig(
            provider: 'keycloak',
            enabled: true,
            discoveryUrl: 'https://sso.example.com/realms/faq/.well-known/openid-configuration',
            client: new OidcClientConfig(
                clientId: 'pmf-web',
                clientSecret: 'secret',
                redirectUri: 'https://faq.example.com/auth/keycloak/callback',
                scopes: ['openid'],
            ),
            autoProvision: false,
            logoutRedirectUrl: '',
        ));
    }
}
