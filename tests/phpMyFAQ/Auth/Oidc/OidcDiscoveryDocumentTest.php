<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcDiscoveryDocument::class)]
final class OidcDiscoveryDocumentTest extends TestCase
{
    public function testFromArrayBuildsDocumentWithAllFields(): void
    {
        $document = OidcDiscoveryDocument::fromArray([
            'issuer' => 'https://issuer.example.com',
            'authorization_endpoint' => 'https://issuer.example.com/auth',
            'token_endpoint' => 'https://issuer.example.com/token',
            'userinfo_endpoint' => 'https://issuer.example.com/userinfo',
            'jwks_uri' => 'https://issuer.example.com/jwks',
            'end_session_endpoint' => 'https://issuer.example.com/logout',
        ]);

        $this->assertSame('https://issuer.example.com', $document->issuer);
        $this->assertSame('https://issuer.example.com/auth', $document->authorizationEndpoint);
        $this->assertSame('https://issuer.example.com/token', $document->tokenEndpoint);
        $this->assertSame('https://issuer.example.com/userinfo', $document->userInfoEndpoint);
        $this->assertSame('https://issuer.example.com/jwks', $document->jwksUri);
        $this->assertSame('https://issuer.example.com/logout', $document->endSessionEndpoint);
    }

    public function testFromArrayTrimsWhitespace(): void
    {
        $document = OidcDiscoveryDocument::fromArray([
            'issuer' => '  https://issuer.example.com  ',
            'authorization_endpoint' => 'https://issuer.example.com/auth',
            'token_endpoint' => 'https://issuer.example.com/token',
            'userinfo_endpoint' => 'https://issuer.example.com/userinfo',
            'jwks_uri' => 'https://issuer.example.com/jwks',
        ]);

        $this->assertSame('https://issuer.example.com', $document->issuer);
        $this->assertNull($document->endSessionEndpoint);
    }

    public function testFromArrayTreatsEmptyEndSessionEndpointAsNull(): void
    {
        $document = OidcDiscoveryDocument::fromArray([
            'issuer' => 'https://issuer.example.com',
            'authorization_endpoint' => 'https://issuer.example.com/auth',
            'token_endpoint' => 'https://issuer.example.com/token',
            'userinfo_endpoint' => 'https://issuer.example.com/userinfo',
            'jwks_uri' => 'https://issuer.example.com/jwks',
            'end_session_endpoint' => '   ',
        ]);

        $this->assertNull($document->endSessionEndpoint);
    }

    public function testFromArrayThrowsForMissingRequiredField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid OIDC discovery field: jwks_uri');

        OidcDiscoveryDocument::fromArray([
            'issuer' => 'https://issuer.example.com',
            'authorization_endpoint' => 'https://issuer.example.com/auth',
            'token_endpoint' => 'https://issuer.example.com/token',
            'userinfo_endpoint' => 'https://issuer.example.com/userinfo',
        ]);
    }

    public function testFromArrayThrowsForBlankRequiredField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid OIDC discovery field: issuer');

        OidcDiscoveryDocument::fromArray([
            'issuer' => '   ',
            'authorization_endpoint' => 'https://issuer.example.com/auth',
            'token_endpoint' => 'https://issuer.example.com/token',
            'userinfo_endpoint' => 'https://issuer.example.com/userinfo',
            'jwks_uri' => 'https://issuer.example.com/jwks',
        ]);
    }

    public function testFromArrayThrowsForNonStringRequiredField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid OIDC discovery field: token_endpoint');

        OidcDiscoveryDocument::fromArray([
            'issuer' => 'https://issuer.example.com',
            'authorization_endpoint' => 'https://issuer.example.com/auth',
            'token_endpoint' => 12345,
            'userinfo_endpoint' => 'https://issuer.example.com/userinfo',
            'jwks_uri' => 'https://issuer.example.com/jwks',
        ]);
    }
}
