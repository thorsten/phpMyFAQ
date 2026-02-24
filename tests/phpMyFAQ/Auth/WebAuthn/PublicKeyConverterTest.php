<?php

namespace phpMyFAQ\Auth\WebAuthn;

use CBOR\CBOREncoder;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;

class PublicKeyConverterTest extends TestCase
{
    /**
     * @throws Exception
     */ public function testFromCoseToPkcsReturnsNullWhenAlgIsMissing(): void
    {
        $encoded = CBOREncoder::encode([]);

        $this->assertNull(PublicKeyConverter::fromCoseToPkcs($encoded));
    }

    /**
     * @throws Exception
     */ public function testFromCoseToPkcsReturnsNullForUnsupportedAlgorithm(): void
    {
        $encoded = CBOREncoder::encode([3 => 123456]);

        $this->assertNull(PublicKeyConverter::fromCoseToPkcs($encoded));
    }

    public function testFromCoseToPkcsThrowsWhenRsaExponentIsMissing(): void
    {
        $encoded = CBOREncoder::encode([3 => -257]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('RSA Exponent missing');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }
}
