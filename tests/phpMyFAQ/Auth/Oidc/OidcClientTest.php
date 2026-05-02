<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(OidcClient::class)]
#[UsesClass(OidcClientConfig::class)]
#[UsesClass(OidcDiscoveryDocument::class)]
#[UsesClass(OidcProviderConfig::class)]
final class OidcClientTest extends TestCase
{
    public function testBuildAuthorizationUrlContainsExpectedParameters(): void
    {
        $client = new OidcClient(new MockHttpClient());
        $config = new OidcProviderConfig(
            'keycloak',
            true,
            'https://sso.example.test/.well-known/openid-configuration',
            new OidcClientConfig(
                'phpmyfaq',
                'secret',
                'https://faq.example.test/auth/keycloak/callback',
                ['openid', 'profile', 'email'],
            ),
            true,
            'https://faq.example.test/',
        );
        $discoveryDocument = new OidcDiscoveryDocument(
            'https://sso.example.test/realms/phpmyfaq',
            'https://sso.example.test/auth',
            'https://sso.example.test/token',
            'https://sso.example.test/userinfo',
            'https://sso.example.test/jwks',
            'https://sso.example.test/logout',
        );

        $url = $client->buildAuthorizationUrl($config, $discoveryDocument, 'state-123', 'nonce-456', 'challenge-789');

        $this->assertStringStartsWith('https://sso.example.test/auth?', $url);
        $this->assertStringContainsString('client_id=phpmyfaq', $url);
        $this->assertStringContainsString('state=state-123', $url);
        $this->assertStringContainsString('nonce=nonce-456', $url);
        $this->assertStringContainsString('code_challenge=challenge-789', $url);
        $this->assertStringContainsString('scope=openid+profile+email', $url);
    }

    public function testExchangeAuthorizationCodeReturnsDecodedTokenPayload(): void
    {
        $client = new OidcClient(new MockHttpClient([
            new MockResponse('{"access_token":"access","refresh_token":"refresh","id_token":"header.payload.sig"}'),
        ]));
        $config = new OidcProviderConfig(
            'keycloak',
            true,
            'https://sso.example.test/.well-known/openid-configuration',
            new OidcClientConfig('phpmyfaq', 'secret', 'https://faq.example.test/callback', ['openid']),
            true,
            '',
        );
        $discoveryDocument = new OidcDiscoveryDocument(
            'https://issuer.example.test',
            'https://sso.example.test/auth',
            'https://sso.example.test/token',
            'https://sso.example.test/userinfo',
            'https://sso.example.test/jwks',
        );

        $payload = $client->exchangeAuthorizationCode($config, $discoveryDocument, 'test-code', 'test-verifier');

        $this->assertSame('access', $payload['access_token']);
        $this->assertSame('refresh', $payload['refresh_token']);
        $this->assertSame('header.payload.sig', $payload['id_token']);
    }

    public function testFetchUserInfoReturnsDecodedClaims(): void
    {
        $client = new OidcClient(new MockHttpClient([
            new MockResponse('{"sub":"123","preferred_username":"john","email":"john@example.com"}'),
        ]));
        $discoveryDocument = new OidcDiscoveryDocument(
            'https://issuer.example.test',
            'https://sso.example.test/auth',
            'https://sso.example.test/token',
            'https://sso.example.test/userinfo',
            'https://sso.example.test/jwks',
        );

        $claims = $client->fetchUserInfo($discoveryDocument, 'access-token');

        $this->assertSame('123', $claims['sub']);
        $this->assertSame('john', $claims['preferred_username']);
        $this->assertSame('john@example.com', $claims['email']);
    }

    public function testBuildLogoutUrlIncludesRedirectTarget(): void
    {
        $client = new OidcClient(new MockHttpClient());
        $config = new OidcProviderConfig(
            'keycloak',
            true,
            'https://sso.example.test/.well-known/openid-configuration',
            new OidcClientConfig('phpmyfaq', 'secret', 'https://faq.example.test/callback', ['openid']),
            true,
            'https://faq.example.test/',
        );
        $discoveryDocument = new OidcDiscoveryDocument(
            'https://issuer.example.test',
            'https://sso.example.test/auth',
            'https://sso.example.test/token',
            'https://sso.example.test/userinfo',
            'https://sso.example.test/jwks',
            'https://sso.example.test/logout',
        );

        $url = $client->buildLogoutUrl($config, $discoveryDocument, 'id-token');

        $this->assertSame(
            'https://sso.example.test/logout?client_id=phpmyfaq&post_logout_redirect_uri=https%3A%2F%2Ffaq.example.test%2F&id_token_hint=id-token',
            $url,
        );
    }

    public function testBuildLogoutUrlReturnsNullWithoutEndSessionEndpoint(): void
    {
        $client = new OidcClient(new MockHttpClient());
        $config = new OidcProviderConfig(
            'keycloak',
            true,
            'https://sso.example.test/.well-known/openid-configuration',
            new OidcClientConfig('phpmyfaq', 'secret', 'https://faq.example.test/callback', ['openid']),
            true,
            '',
        );
        $discoveryDocument = new OidcDiscoveryDocument(
            'https://issuer.example.test',
            'https://sso.example.test/auth',
            'https://sso.example.test/token',
            'https://sso.example.test/userinfo',
            'https://sso.example.test/jwks',
        );

        $this->assertNull($client->buildLogoutUrl($config, $discoveryDocument));
    }

    public function testBuildLogoutUrlOmitsOptionalParameters(): void
    {
        $client = new OidcClient(new MockHttpClient());
        $config = new OidcProviderConfig(
            'keycloak',
            true,
            'https://sso.example.test/.well-known/openid-configuration',
            new OidcClientConfig('phpmyfaq', 'secret', 'https://faq.example.test/callback', ['openid']),
            true,
            '',
        );
        $discoveryDocument = new OidcDiscoveryDocument(
            'https://issuer.example.test',
            'https://sso.example.test/auth',
            'https://sso.example.test/token',
            'https://sso.example.test/userinfo',
            'https://sso.example.test/jwks',
            'https://sso.example.test/logout',
        );

        $this->assertSame('https://sso.example.test/logout?client_id=phpmyfaq', $client->buildLogoutUrl($config, $discoveryDocument));
    }

    public function testExchangeAuthorizationCodeThrowsOnHttpError(): void
    {
        $client = new OidcClient(new MockHttpClient([
            new MockResponse('{"error":"invalid_grant"}', ['http_code' => 400]),
        ]));
        $config = $this->makeConfig();
        $discoveryDocument = $this->makeDiscoveryDocument();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OIDC token request failed with status 400');

        $client->exchangeAuthorizationCode($config, $discoveryDocument, 'code', 'verifier');
    }

    public function testExchangeAuthorizationCodeThrowsOnInvalidJson(): void
    {
        $client = new OidcClient(new MockHttpClient([
            new MockResponse('not-json'),
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OIDC token response is not valid JSON');

        $client->exchangeAuthorizationCode($this->makeConfig(), $this->makeDiscoveryDocument(), 'code', 'verifier');
    }

    public function testExchangeAuthorizationCodeThrowsWhenPayloadIsNotArray(): void
    {
        $client = new OidcClient(new MockHttpClient([
            new MockResponse('"a-string"'),
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OIDC token response is not a JSON object/array');

        $client->exchangeAuthorizationCode($this->makeConfig(), $this->makeDiscoveryDocument(), 'code', 'verifier');
    }

    public function testExchangeAuthorizationCodeThrowsWhenAccessTokenMissing(): void
    {
        $client = new OidcClient(new MockHttpClient([
            new MockResponse('{"refresh_token":"refresh"}'),
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OIDC token response did not contain a valid access_token');

        $client->exchangeAuthorizationCode($this->makeConfig(), $this->makeDiscoveryDocument(), 'code', 'verifier');
    }

    private function makeConfig(): OidcProviderConfig
    {
        return new OidcProviderConfig(
            'keycloak',
            true,
            'https://sso.example.test/.well-known/openid-configuration',
            new OidcClientConfig('phpmyfaq', 'secret', 'https://faq.example.test/callback', ['openid']),
            true,
            '',
        );
    }

    private function makeDiscoveryDocument(): OidcDiscoveryDocument
    {
        return new OidcDiscoveryDocument(
            'https://issuer.example.test',
            'https://sso.example.test/auth',
            'https://sso.example.test/token',
            'https://sso.example.test/userinfo',
            'https://sso.example.test/jwks',
        );
    }
}
