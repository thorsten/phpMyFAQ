<?php

namespace phpMyFAQ\Auth\WebAuthn;

use CBOR\CBOREncoder;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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

    public function testFromCoseToPkcsThrowsWhenRsaModulusIsMissing(): void
    {
        $encoded = CBOREncoder::encode([3 => -257, -2 => 1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('RSA Modulus missing');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }

    public function testFromCoseToPkcsThrowsWhenCurveIsMissing(): void
    {
        $encoded = CBOREncoder::encode([3 => -7]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for curve');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }

    public function testFromCoseToPkcsThrowsWhenXCoordinateIsMissing(): void
    {
        $encoded = CBOREncoder::encode([3 => -7, -1 => 1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for x coordinate');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }

    public function testFromCoseToPkcsThrowsWhenCurveIsNotP256(): void
    {
        $encoded = CBOREncoder::encode([3 => -7, -1 => 2, -2 => 1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for curve P256');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }

    public function testFromCoseToPkcsThrowsWhenKeyTypeIsMissing(): void
    {
        $encoded = CBOREncoder::encode([3 => -7, -1 => 1, -2 => 1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for key type');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }

    public function testFromCoseToPkcsThrowsWhenYCoordinateIsMissing(): void
    {
        $encoded = CBOREncoder::encode([3 => -7, -1 => 1, -2 => 1, 1 => 2]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for y coordinate');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }

    public function testFromCoseToPkcsThrowsWhenKeyTypeIsNotEc2(): void
    {
        $encoded = CBOREncoder::encode([3 => -7, -1 => 1, -2 => 1, 1 => 1, -3 => 1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for key type EC2');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }

    public function testPublicKeyToPemReturnsNullForInvalidUncompressedKey(): void
    {
        $reflection = new ReflectionClass(PublicKeyConverter::class);
        $method = $reflection->getMethod('publicKeyToPem');

        $this->assertNull($method->invoke(null, 'invalid'));
    }

    public function testPublicKeyToPemReturnsPemForValidUncompressedKey(): void
    {
        $reflection = new ReflectionClass(PublicKeyConverter::class);
        $method = $reflection->getMethod('publicKeyToPem');
        $key = "\x04" . str_repeat('A', 64);

        $result = $method->invoke(null, $key);

        $this->assertIsString($result);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $result);
        $this->assertStringContainsString('END PUBLIC KEY', $result);
    }
}
