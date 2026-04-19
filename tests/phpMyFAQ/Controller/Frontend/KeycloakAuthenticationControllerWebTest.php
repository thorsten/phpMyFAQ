<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use OpenSSLAsymmetricKey;
use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(KeycloakAuthenticationController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractFrontController::class)]
final class KeycloakAuthenticationControllerWebTest extends ControllerWebTestCase
{
    private OpenSSLAsymmetricKey $privateKey;

    /** @var array<string, mixed> */
    private array $jwk;

    protected function setUp(): void
    {
        parent::setUp();

        set_error_handler(static fn(): bool => true);
        try {
            $privateKey = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]);
        } finally {
            restore_error_handler();
        }

        self::assertInstanceOf(OpenSSLAsymmetricKey::class, $privateKey);
        $details = openssl_pkey_get_details($privateKey);
        self::assertIsArray($details);
        self::assertIsArray($details['rsa'] ?? null);

        $this->privateKey = $privateKey;
        $this->jwk = [
            'kty' => 'RSA',
            'kid' => 'test-key',
            'alg' => 'RS256',
            'use' => 'sig',
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ];
    }

    public function testCallbackCompletesSuccessfulLoginFlowWithMockedProviderResponses(): void
    {
        $configuration = $this->getConfiguration();
        $this->overrideConfigurationValues([
            'keycloak.enable' => true,
            'keycloak.baseUrl' => 'https://sso.example.test',
            'keycloak.realm' => 'phpmyfaq',
            'keycloak.clientId' => 'phpmyfaq',
            'keycloak.clientSecret' => 'secret',
            'keycloak.redirectUri' => 'https://localhost/auth/keycloak/callback',
            'keycloak.scopes' => 'openid profile email',
            'keycloak.autoProvision' => false,
        ]);

        $container = self::$kernel?->getContainer();
        self::assertInstanceOf(ContainerInterface::class, $container);

        $oidcSession = $container->get('phpmyfaq.auth.oidc.session');
        self::assertInstanceOf(\phpMyFAQ\Auth\Oidc\OidcSession::class, $oidcSession);
        $oidcSession->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $idToken = $this->signToken([
            'iss' => 'https://sso.example.test/realms/phpmyfaq',
            'aud' => ['phpmyfaq'],
            'azp' => 'phpmyfaq',
            'nonce' => 'nonce-456',
            'exp' => time() + 300,
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
            new MockResponse(
                '{"access_token":"access-token","refresh_token":"refresh-token","id_token":"' . $idToken . '"}',
            ),
            new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
            new MockResponse(
                '{"preferred_username":"admin","email":"admin@example.com","name":"Admin User"}',
            ),
        ]);

        $container->set('phpmyfaq.http-client', $httpClient);

        $response = $this->requestPublic('GET', '/auth/keycloak/callback', [
            'code' => 'auth-code',
            'state' => 'state-123',
        ]);

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame($configuration->getDefaultUrl(), $response->headers->get('Location'));
        self::assertSame('', $oidcSession->getAuthorizationState()['state']);
        self::assertSame($idToken, $oidcSession->getIdToken());
    }

    public function testLogoutBuildsProviderRedirectWithSessionIdToken(): void
    {
        $configuration = $this->getConfiguration();
        $this->overrideConfigurationValues([
            'keycloak.enable' => true,
            'keycloak.baseUrl' => 'https://sso.example.test',
            'keycloak.realm' => 'phpmyfaq',
            'keycloak.clientId' => 'phpmyfaq',
            'keycloak.clientSecret' => 'secret',
            'keycloak.redirectUri' => 'https://localhost/auth/keycloak/callback',
            'keycloak.logoutRedirectUrl' => 'https://localhost/',
        ]);

        $container = self::$kernel?->getContainer();
        self::assertInstanceOf(ContainerInterface::class, $container);

        $oidcSession = $container->get('phpmyfaq.auth.oidc.session');
        self::assertInstanceOf(\phpMyFAQ\Auth\Oidc\OidcSession::class, $oidcSession);
        $oidcSession->setIdToken('session-id-token');

        $httpClient = new MockHttpClient([
            new MockResponse(
                '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
            ),
        ]);

        $container->set('phpmyfaq.http-client', $httpClient);

        $response = $this->requestPublic('GET', '/auth/keycloak/logout');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame(
            'https://sso.example.test/logout?client_id=phpmyfaq&post_logout_redirect_uri=https%3A%2F%2Flocalhost%2F&id_token_hint=session-id-token',
            $response->headers->get('Location'),
        );
        self::assertSame('', $oidcSession->getIdToken());
        self::assertNotSame($configuration->getDefaultUrl(), $response->headers->get('Location'));
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function signToken(array $claims): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => 'test-key',
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));
        $signingInput = $encodedHeader . '.' . $encodedPayload;

        $signature = '';
        openssl_sign($signingInput, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
