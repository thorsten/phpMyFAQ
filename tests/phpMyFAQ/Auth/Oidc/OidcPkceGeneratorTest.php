<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcPkceGenerator::class)]
final class OidcPkceGeneratorTest extends TestCase
{
    public function testGenerateVerifierReturnsExpectedLengthAndCharset(): void
    {
        $generator = new OidcPkceGenerator();
        $verifier = $generator->generateVerifier(64);

        $this->assertSame(64, strlen($verifier));
        $this->assertSame(1, preg_match('/^[A-Za-z0-9\\-._~]+$/', $verifier));
    }

    public function testGenerateVerifierDefaultsToMaximumLength(): void
    {
        $generator = new OidcPkceGenerator();

        $this->assertSame(128, strlen($generator->generateVerifier()));
    }

    public function testGenerateVerifierRejectsLengthBelowMinimum(): void
    {
        $generator = new OidcPkceGenerator();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PKCE verifier length must be between 43 and 128, got 42');

        $generator->generateVerifier(42);
    }

    public function testGenerateVerifierRejectsLengthAboveMaximum(): void
    {
        $generator = new OidcPkceGenerator();

        $this->expectException(InvalidArgumentException::class);

        $generator->generateVerifier(129);
    }

    public function testGenerateChallengeMatchesRfcExample(): void
    {
        $generator = new OidcPkceGenerator();

        $this->assertSame(
            'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            $generator->generateChallenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'),
        );
    }

    public function testGenerateChallengeRejectsTooShortVerifier(): void
    {
        $generator = new OidcPkceGenerator();

        $this->expectException(InvalidArgumentException::class);

        $generator->generateChallenge(str_repeat('a', 42));
    }

    public function testGenerateChallengeRejectsTooLongVerifier(): void
    {
        $generator = new OidcPkceGenerator();

        $this->expectException(InvalidArgumentException::class);

        $generator->generateChallenge(str_repeat('a', 129));
    }

    public function testGenerateChallengeRejectsInvalidCharacters(): void
    {
        $generator = new OidcPkceGenerator();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PKCE verifier contains invalid characters');

        $generator->generateChallenge(str_repeat('a', 42) . '!');
    }
}
