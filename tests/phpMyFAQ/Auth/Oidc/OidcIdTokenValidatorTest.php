<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use OpenSSLAsymmetricKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(OidcIdTokenValidator::class)]
#[UsesClass(OidcDiscoveryDocument::class)]
final class OidcIdTokenValidatorTest extends TestCase
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

    public function testValidateReturnsClaimsForValidIdToken(): void
    {
        $validator = $this->createValidator([
            'keys' => [$this->jwk],
        ], 1_700_000_000);

        $claims = $validator->validate(
            $this->signToken([
                'iss' => 'https://sso.example.test/realms/phpmyfaq',
                'aud' => ['phpmyfaq'],
                'azp' => 'phpmyfaq',
                'nonce' => 'nonce-123',
                'iat' => 1_699_999_990,
                'nbf' => 1_699_999_990,
                'exp' => 1_700_000_060,
            ]),
            $this->createDiscoveryDocument(),
            'phpmyfaq',
            'nonce-123',
        );

        self::assertSame('https://sso.example.test/realms/phpmyfaq', $claims['iss']);
    }

    public function testValidateRejectsExpiredToken(): void
    {
        $validator = $this->createValidator([
            'keys' => [$this->jwk],
        ], 1_700_000_000);

        $this->expectExceptionObject(new \RuntimeException('OIDC id_token has expired'));

        $validator->validate(
            $this->signToken([
                'iss' => 'https://sso.example.test/realms/phpmyfaq',
                'aud' => ['phpmyfaq'],
                'azp' => 'phpmyfaq',
                'nonce' => 'nonce-123',
                'exp' => 1_699_999_999,
            ]),
            $this->createDiscoveryDocument(),
            'phpmyfaq',
            'nonce-123',
        );
    }

    public function testValidateRejectsTokenThatIsNotValidYet(): void
    {
        $validator = $this->createValidator([
            'keys' => [$this->jwk],
        ], 1_700_000_000);

        $this->expectExceptionObject(new \RuntimeException('OIDC id_token is not valid yet'));

        $validator->validate(
            $this->signToken([
                'iss' => 'https://sso.example.test/realms/phpmyfaq',
                'aud' => ['phpmyfaq'],
                'azp' => 'phpmyfaq',
                'nonce' => 'nonce-123',
                'nbf' => 1_700_000_001,
                'exp' => 1_700_000_060,
            ]),
            $this->createDiscoveryDocument(),
            'phpmyfaq',
            'nonce-123',
        );
    }

    public function testValidateRejectsFutureIssuedAtTime(): void
    {
        $validator = $this->createValidator([
            'keys' => [$this->jwk],
        ], 1_700_000_000);

        $this->expectExceptionObject(new \RuntimeException('OIDC id_token issued-at time is in the future'));

        $validator->validate(
            $this->signToken([
                'iss' => 'https://sso.example.test/realms/phpmyfaq',
                'aud' => ['phpmyfaq'],
                'azp' => 'phpmyfaq',
                'nonce' => 'nonce-123',
                'iat' => 1_700_000_061,
                'exp' => 1_700_000_120,
            ]),
            $this->createDiscoveryDocument(),
            'phpmyfaq',
            'nonce-123',
        );
    }

    public function testValidateRejectsInvalidSignature(): void
    {
        set_error_handler(static fn(): bool => true);
        try {
            $otherKey = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]);
        } finally {
            restore_error_handler();
        }
        self::assertInstanceOf(OpenSSLAsymmetricKey::class, $otherKey);

        $validator = $this->createValidator([
            'keys' => [$this->jwk],
        ], 1_700_000_000);

        $this->expectExceptionObject(new \RuntimeException('OIDC id_token signature validation failed'));

        $validator->validate(
            $this->signToken([
                'iss' => 'https://sso.example.test/realms/phpmyfaq',
                'aud' => ['phpmyfaq'],
                'azp' => 'phpmyfaq',
                'nonce' => 'nonce-123',
                'exp' => 1_700_000_060,
            ], $otherKey),
            $this->createDiscoveryDocument(),
            'phpmyfaq',
            'nonce-123',
        );
    }

    private function createDiscoveryDocument(): OidcDiscoveryDocument
    {
        return new OidcDiscoveryDocument(
            issuer: 'https://sso.example.test/realms/phpmyfaq',
            authorizationEndpoint: 'https://sso.example.test/auth',
            tokenEndpoint: 'https://sso.example.test/token',
            userInfoEndpoint: 'https://sso.example.test/userinfo',
            jwksUri: 'https://sso.example.test/jwks',
            endSessionEndpoint: 'https://sso.example.test/logout',
        );
    }

    /**
     * @param array<string, mixed> $jwks
     */
    private function createValidator(array $jwks, int $now): OidcIdTokenValidator
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode($jwks, JSON_THROW_ON_ERROR)),
        ]);

        return new OidcIdTokenValidator($httpClient, static fn(): int => $now);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function signToken(array $claims, ?OpenSSLAsymmetricKey $signingKey = null): string
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
        openssl_sign($signingInput, $signature, $signingKey ?? $this->privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
