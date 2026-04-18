<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

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

    public function testGenerateChallengeMatchesRfcExample(): void
    {
        $generator = new OidcPkceGenerator();

        $this->assertSame(
            'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            $generator->generateChallenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'),
        );
    }
}
