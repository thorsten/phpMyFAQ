<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use OpenSSLAsymmetricKey;
use phpMyFAQ\Auth\Oidc\OidcClient;
use phpMyFAQ\Auth\Oidc\OidcDiscoveryService;
use phpMyFAQ\Auth\Oidc\OidcIdTokenValidator;
use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

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

        $idToken = $this->signToken([
            'iss' => 'https://sso.example.test/realms/phpmyfaq',
            'sub' => 'subject-123',
            'aud' => 'phpmyfaq',
            'azp' => 'phpmyfaq',
            'iat' => time(),
            'exp' => time() + 300,
        ]);
        $expectedNonce = '';
        $expectedVerifier = '';

        $responseIndex = 0;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (
            &$responseIndex,
            &$idToken,
            &$expectedNonce,
            &$expectedVerifier,
        ): MockResponse {
            $responseIndex++;

            $expectedMethod = match ($responseIndex) {
                3 => 'POST',
                default => 'GET',
            };
            self::assertSame($expectedMethod, $method, sprintf('Unexpected HTTP method for call #%d', $responseIndex));

            return match ($responseIndex) {
                1, 2 => new MockResponse(
                    '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
                ),
                3 => (function () use ($options, &$idToken, &$expectedNonce, &$expectedVerifier): MockResponse {
                    parse_str((string) ($options['body'] ?? ''), $body);
                    self::assertSame($expectedVerifier, $body['code_verifier'] ?? '');
                    self::assertSame('auth-code', $body['code'] ?? '');

                    $idToken = $this->signToken([
                        'iss' => 'https://sso.example.test/realms/phpmyfaq',
                        'sub' => 'subject-123',
                        'aud' => 'phpmyfaq',
                        'azp' => 'phpmyfaq',
                        'nonce' => $expectedNonce,
                        'iat' => time(),
                        'exp' => time() + 300,
                    ]);

                    return new MockResponse(
                        '{"access_token":"access-token","refresh_token":"refresh-token","id_token":"' . $idToken . '"}',
                    );
                })(),
                4 => new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
                5 => new MockResponse(
                    '{"sub":"subject-123","preferred_username":"admin","email":"admin@example.com","name":"Admin User"}',
                ),
                default => throw new \RuntimeException('Unexpected HTTP call in callback test: ' . $url),
            };
        });

        $container->set('phpmyfaq.http-client', $httpClient);
        $container->set('phpmyfaq.auth.oidc.client', new OidcClient($httpClient));
        $container->set('phpmyfaq.auth.oidc.discovery-service', new OidcDiscoveryService($httpClient));
        $container->set('phpmyfaq.auth.oidc.id-token-validator', new OidcIdTokenValidator($httpClient));

        $authorizeResponse = $this->requestPublic('GET', '/auth/keycloak/authorize');
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND, $authorizeResponse);

        parse_str(
            (string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY),
            $authorizeQuery,
        );
        $authorizationState = $oidcSession->getAuthorizationState();
        $expectedNonce = $authorizationState['nonce'];
        $expectedVerifier = $authorizationState['verifier'];

        $response = $this->requestPublic('GET', '/auth/keycloak/callback', [
            'code' => 'auth-code',
            'state' => (string) ($authorizeQuery['state'] ?? ''),
        ]);

        self::assertResponseStatusCodeSame(302, $response);
        self::assertSame($configuration->getDefaultUrl(), $response->headers->get('Location'));
        self::assertNotSame('', $idToken);
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
        $expectedNonce = '';
        $expectedVerifier = '';

        $responseIndex = 0;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (
            &$responseIndex,
            &$expectedNonce,
            &$expectedVerifier,
        ): MockResponse {
            $responseIndex++;

            $expectedMethod = match ($responseIndex) {
                3 => 'POST',
                default => 'GET',
            };
            self::assertSame($expectedMethod, $method, sprintf('Unexpected HTTP method for call #%d', $responseIndex));

            return match ($responseIndex) {
                1, 2, 6 => new MockResponse(
                    '{"issuer":"https://sso.example.test/realms/phpmyfaq","authorization_endpoint":"https://sso.example.test/auth","token_endpoint":"https://sso.example.test/token","userinfo_endpoint":"https://sso.example.test/userinfo","jwks_uri":"https://sso.example.test/jwks","end_session_endpoint":"https://sso.example.test/logout"}',
                ),
                3 => (function () use ($options, &$expectedNonce, &$expectedVerifier): MockResponse {
                    parse_str((string) ($options['body'] ?? ''), $body);
                    self::assertSame($expectedVerifier, $body['code_verifier'] ?? '');

                    $idToken = $this->signToken([
                        'iss' => 'https://sso.example.test/realms/phpmyfaq',
                        'sub' => 'subject-123',
                        'aud' => 'phpmyfaq',
                        'azp' => 'phpmyfaq',
                        'nonce' => $expectedNonce,
                        'iat' => time(),
                        'exp' => time() + 300,
                    ]);

                    return new MockResponse(
                        '{"access_token":"access-token","refresh_token":"refresh-token","id_token":"' . $idToken . '"}',
                    );
                })(),
                4 => new MockResponse(json_encode(['keys' => [$this->jwk]], JSON_THROW_ON_ERROR)),
                5 => new MockResponse(
                    '{"sub":"subject-123","preferred_username":"admin","email":"admin@example.com","name":"Admin User"}',
                ),
                default => throw new \RuntimeException('Unexpected HTTP call in logout test: ' . $url),
            };
        });

        $container->set('phpmyfaq.http-client', $httpClient);
        $container->set('phpmyfaq.auth.oidc.client', new OidcClient($httpClient));
        $container->set('phpmyfaq.auth.oidc.discovery-service', new OidcDiscoveryService($httpClient));
        $container->set('phpmyfaq.auth.oidc.id-token-validator', new OidcIdTokenValidator($httpClient));

        $authorizeResponse = $this->requestPublic('GET', '/auth/keycloak/authorize');
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND, $authorizeResponse);
        parse_str(
            (string) parse_url((string) $authorizeResponse->headers->get('Location'), PHP_URL_QUERY),
            $authorizeQuery,
        );
        $authorizationState = $oidcSession->getAuthorizationState();
        $expectedNonce = $authorizationState['nonce'];
        $expectedVerifier = $authorizationState['verifier'];

        $callbackResponse = $this->requestPublic('GET', '/auth/keycloak/callback', [
            'code' => 'auth-code',
            'state' => (string) ($authorizeQuery['state'] ?? ''),
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_FOUND, $callbackResponse);

        $response = $this->requestPublic('GET', '/auth/keycloak/logout');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertStringStartsWith('https://sso.example.test/logout?', (string) $response->headers->get('Location'));
        self::assertStringContainsString('client_id=phpmyfaq', (string) $response->headers->get('Location'));
        self::assertStringContainsString(
            'post_logout_redirect_uri=https%3A%2F%2Flocalhost%2F',
            (string) $response->headers->get('Location'),
        );
        self::assertStringContainsString('id_token_hint=', (string) $response->headers->get('Location'));
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
