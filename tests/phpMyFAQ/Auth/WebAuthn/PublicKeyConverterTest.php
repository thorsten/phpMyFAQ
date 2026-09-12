<?php

namespace phpMyFAQ\Auth\WebAuthn;

use CBOR\CBOREncoder;
use CBOR\Types\CBORByteString;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PublicKeyConverterTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testFromCoseToPkcsReturnsNullWhenAlgIsMissing(): void
    {
        $encoded = CBOREncoder::encode([]);

        $this->assertNull(PublicKeyConverter::fromCoseToPkcs($encoded));
    }

    /**
     * @throws Exception
     */
    public function testFromCoseToPkcsReturnsNullForUnsupportedAlgorithm(): void
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

    public function testFromCoseToPkcsThrowsWhenRsaExponentOrModulusIsNotAByteString(): void
    {
        $encoded = CBOREncoder::encode([3 => -257, -2 => 1, -1 => 1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for RSA exponent or modulus');

        PublicKeyConverter::fromCoseToPkcs($encoded);
    }

    /**
     * Exercises the full RS256 path, including the phpseclib BigInteger and
     * PublicKeyLoader calls, by round-tripping an openssl-generated RSA key.
     *
     * @throws Exception
     */
    public function testFromCoseToPkcsConvertsRs256KeyToPem(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($key);
        $details = openssl_pkey_get_details($key);
        $this->assertIsArray($details);

        $encoded = CBOREncoder::encode([
            1 => 3,
            3 => -257,
            -1 => new CBORByteString($details['rsa']['n']),
            -2 => new CBORByteString($details['rsa']['e']),
        ]);

        $pem = PublicKeyConverter::fromCoseToPkcs($encoded);

        $this->assertIsString($pem);
        $this->assertStringStartsWith('-----BEGIN PUBLIC KEY-----', $pem);

        $publicKey = openssl_pkey_get_public($pem);
        $this->assertNotFalse($publicKey);
        $publicKeyDetails = openssl_pkey_get_details($publicKey);
        $this->assertIsArray($publicKeyDetails);
        $this->assertSame(OPENSSL_KEYTYPE_RSA, $publicKeyDetails['type']);
        $this->assertSame($details['rsa']['n'], $publicKeyDetails['rsa']['n']);
        $this->assertSame($details['rsa']['e'], $publicKeyDetails['rsa']['e']);
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
