<?php

/**
 * OIDC ID token validator.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-04-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use Closure;
use JsonException;
use RuntimeException;
use SensitiveParameter;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OidcIdTokenValidator
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ?Closure $currentTimeProvider = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     * @throws ExceptionInterface
     */
    public function validate(
        #[SensitiveParameter]
        string $idToken,
        OidcDiscoveryDocument $discoveryDocument,
        string $expectedAudience,
        string $expectedNonce,
    ): array {
        if ($idToken === '') {
            return [];
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $this->splitToken($idToken);
        $header = $this->decodeSegment($encodedHeader, 'header');
        $claims = $this->decodeSegment($encodedPayload, 'payload');

        $this->validateClaims($claims, $discoveryDocument->issuer, $expectedAudience, $expectedNonce);
        $this->validateSignature(
            $encodedHeader . '.' . $encodedPayload,
            $encodedSignature,
            $header,
            $discoveryDocument->jwksUri,
        );

        return $claims;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function splitToken(#[SensitiveParameter] string $idToken): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
            throw new RuntimeException('OIDC id_token is malformed');
        }

        return [$parts[0], $parts[1], $parts[2]];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSegment(string $segment, string $context): array
    {
        $decoded = $this->base64UrlDecode($segment);

        try {
            $payload = json_decode($decoded, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('OIDC id_token %s is not valid JSON', $context), previous: $exception);
        }

        if (!is_array($payload)) {
            throw new RuntimeException(sprintf('OIDC id_token %s is not valid', $context));
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function validateClaims(
        array $claims,
        string $expectedIssuer,
        string $expectedAudience,
        string $expectedNonce,
    ): void {
        $issuer = trim((string) ($claims['iss'] ?? ''));
        if ($issuer !== '' && $issuer !== $expectedIssuer) {
            throw new RuntimeException('OIDC issuer mismatch in id_token');
        }

        $audience = $claims['aud'] ?? null;
        if ($audience !== null && !$this->audienceMatches($audience, $expectedAudience)) {
            throw new RuntimeException('OIDC audience mismatch in id_token');
        }

        $authorizedParty = trim((string) ($claims['azp'] ?? ''));
        if ($authorizedParty !== '' && $authorizedParty !== $expectedAudience) {
            throw new RuntimeException('OIDC authorized party mismatch in id_token');
        }

        if (is_array($audience) && count($audience) > 1 && $authorizedParty === '') {
            throw new RuntimeException('OIDC authorized party missing in id_token');
        }

        $nonce = trim((string) ($claims['nonce'] ?? ''));
        if ($expectedNonce !== '' && $nonce !== '' && !hash_equals($expectedNonce, $nonce)) {
            throw new RuntimeException('OIDC nonce mismatch in id_token');
        }

        $now = $this->getCurrentTimestamp();

        if (array_key_exists('exp', $claims) && is_numeric($claims['exp']) && (int) $claims['exp'] < $now) {
            throw new RuntimeException('OIDC id_token has expired');
        }

        if (array_key_exists('nbf', $claims) && is_numeric($claims['nbf']) && (int) $claims['nbf'] > $now) {
            throw new RuntimeException('OIDC id_token is not valid yet');
        }

        if (array_key_exists('iat', $claims) && is_numeric($claims['iat']) && (int) $claims['iat'] > ($now + 60)) {
            throw new RuntimeException('OIDC id_token issued-at time is in the future');
        }
    }

    /**
     * @param array<string, mixed> $header
     * @throws ExceptionInterface
     */
    private function validateSignature(
        string $signedPayload,
        string $encodedSignature,
        array $header,
        string $jwksUri,
    ): void {
        $algorithm = (string) ($header['alg'] ?? '');
        $keyId = trim((string) ($header['kid'] ?? ''));

        $opensslAlgorithm = match ($algorithm) {
            'RS256' => OPENSSL_ALGO_SHA256,
            'RS384' => OPENSSL_ALGO_SHA384,
            'RS512' => OPENSSL_ALGO_SHA512,
            default => throw new RuntimeException('Unsupported OIDC id_token signing algorithm'),
        };

        $jwks = $this->fetchJwks($jwksUri);
        $key = $this->findKey($jwks, $keyId);
        $publicKey = $this->createPemFromJwk($key);
        $signature = $this->base64UrlDecode($encodedSignature);

        $verificationResult = openssl_verify($signedPayload, $signature, $publicKey, $opensslAlgorithm);
        if ($verificationResult !== 1) {
            throw new RuntimeException('OIDC id_token signature validation failed');
        }
    }

    /**
     * @return array<string, mixed>
     * @throws ExceptionInterface
     */
    private function fetchJwks(string $jwksUri): array
    {
        $response = $this->httpClient->request('GET', $jwksUri);
        $content = $response->getContent(false);

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(sprintf('OIDC JWKS request failed with status %d', $response->getStatusCode()));
        }

        try {
            $payload = json_decode($content, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('OIDC JWKS response is not valid JSON', previous: $exception);
        }

        if (!is_array($payload)) {
            throw new RuntimeException('OIDC JWKS response is not valid');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $jwks
     * @return array<string, mixed>
     */
    private function findKey(array $jwks, string $keyId): array
    {
        $keys = $jwks['keys'] ?? null;
        if (!is_array($keys) || $keys === []) {
            throw new RuntimeException('OIDC JWKS response does not contain any keys');
        }

        foreach ($keys as $key) {
            if (!is_array($key)) {
                continue;
            }

            $candidateKeyId = trim((string) ($key['kid'] ?? ''));
            if ($keyId !== '' && $candidateKeyId !== $keyId) {
                continue;
            }

            return $key;
        }

        if ($keyId === '' && is_array($keys[0])) {
            return $keys[0];
        }

        throw new RuntimeException('OIDC JWKS key could not be resolved');
    }

    /**
     * @param array<string, mixed> $key
     */
    private function createPemFromJwk(array $key): string
    {
        $keyType = (string) ($key['kty'] ?? '');
        $modulus = (string) ($key['n'] ?? '');
        $exponent = (string) ($key['e'] ?? '');

        if ($keyType !== 'RSA' || $modulus === '' || $exponent === '') {
            throw new RuntimeException('OIDC JWK is not a supported RSA key');
        }

        $rsaPublicKey = $this->asn1Sequence(
            $this->asn1Integer($this->base64UrlDecode($modulus))
                . $this->asn1Integer($this->base64UrlDecode($exponent)),
        );

        $subjectPublicKeyInfo = $this->asn1Sequence(
            $this->asn1Sequence($this->asn1ObjectIdentifier("\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01") . $this->asn1Null())
                . $this->asn1BitString($rsaPublicKey),
        );

        return (
            "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($subjectPublicKeyInfo), length: 64, separator: "\n")
            . "-----END PUBLIC KEY-----\n"
        );
    }

    private function asn1Sequence(string $value): string
    {
        return "\x30" . $this->asn1Length(strlen($value)) . $value;
    }

    private function asn1Integer(string $value): string
    {
        if ($value === '') {
            $value = "\x00";
        }

        if (ord($value[0]) > 0x7f) {
            $value = "\x00" . $value;
        }

        return "\x02" . $this->asn1Length(strlen($value)) . $value;
    }

    private function asn1ObjectIdentifier(string $value): string
    {
        return "\x06" . $this->asn1Length(strlen($value)) . $value;
    }

    private function asn1Null(): string
    {
        return "\x05\x00";
    }

    private function asn1BitString(string $value): string
    {
        return "\x03" . $this->asn1Length(strlen($value) + 1) . "\x00" . $value;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $encoded = '';
        while ($length > 0) {
            $encoded = chr($length & 0xff) . $encoded;
            $length >>= 8;
        }

        return chr(0x80 | strlen($encoded)) . $encoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = (4 - (strlen($value) % 4)) % 4;
        $value .= str_repeat('=', $padding);
        $decoded = base64_decode(strtr(string: $value, from: '-_', to: '+/'), strict: true);

        if ($decoded === false) {
            throw new RuntimeException('OIDC token could not be base64url decoded');
        }

        return $decoded;
    }

    private function audienceMatches(mixed $audience, string $expectedAudience): bool
    {
        if (is_string($audience)) {
            return $audience === $expectedAudience;
        }

        if (!is_array($audience)) {
            return false;
        }

        foreach ($audience as $entry) {
            if (is_string($entry) && $entry === $expectedAudience) {
                return true;
            }
        }

        return false;
    }

    private function getCurrentTimestamp(): int
    {
        if ($this->currentTimeProvider instanceof Closure) {
            return ($this->currentTimeProvider)();
        }

        return time();
    }
}
