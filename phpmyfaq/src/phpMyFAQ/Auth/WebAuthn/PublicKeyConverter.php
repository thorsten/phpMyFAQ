<?php

/**
 * Public key converter for WebAuthn
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
 * @since     2026-02-24
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\WebAuthn;

use CBOR\CBOREncoder;
use phpMyFAQ\Core\Exception;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;

class PublicKeyConverter
{
    private const int ES256 = -7;
    private const int RS256 = -257;

    /**
     * Convert COSE ECDHA to PKCS
     *
     * @throws Exception
     * @throws \Exception
     */
    public static function fromCoseToPkcs(string $binary): ?string
    {
        $cosePubKey = CBOREncoder::decode($binary);

        if (!array_key_exists(3, $cosePubKey) || $cosePubKey[3] === null) { /* cose_alg */
            return null;
        }

        switch ($cosePubKey[3]) {
            case self::ES256:
                /* COSE Alg: ECDSA w/ SHA-256 */
                if (!array_key_exists(-1, $cosePubKey) || $cosePubKey[-1] === null) { /* cose_crv */
                    throw new Exception('Cannot decode key response for curve');
                }

                if (!array_key_exists(-2, $cosePubKey) || $cosePubKey[-2] === null) { /* cose_crv_x */
                    throw new Exception('Cannot decode key response for x coordinate');
                }

                if ($cosePubKey[-1] !== 1) { /* cose_crv_P256 */
                    throw new Exception('Cannot decode key response for curve P256');
                }

                if (!array_key_exists(-2, $cosePubKey) || $cosePubKey[-2] === null) { /* cose_crv_x */
                    throw new Exception('x coordinate for curve missing');
                }

                if (!array_key_exists(1, $cosePubKey) || $cosePubKey[1] === null) { /* cose_kty */
                    throw new Exception('Cannot decode key response for key type');
                }

                if (!array_key_exists(-3, $cosePubKey) || $cosePubKey[-3] === null) { /* cose_crv_y */
                    throw new Exception('Cannot decode key response for y coordinate');
                }

                if (!array_key_exists(-3, $cosePubKey) || $cosePubKey[-3] === null) { /* cose_crv_y */
                    throw new Exception('y coordinate for curve missing');
                }

                if ($cosePubKey[1] !== 2) { /* cose_kty_ec2 */
                    throw new Exception('Cannot decode key response for key type EC2');
                }

                $x = $cosePubKey[-2]->get_byte_string();
                $y = $cosePubKey[-3]->get_byte_string();
                if (strlen((string) $x) !== 32 || strlen((string) $y) !== 32) {
                    throw new Exception('Cannot decode key response for x or y coordinate');
                }

                return self::publicKeyToPem("\x04" . $x . $y);
            case self::RS256:
                if (!array_key_exists(-2, $cosePubKey) || $cosePubKey[-2] === null) {
                    throw new Exception('RSA Exponent missing');
                }

                if (!array_key_exists(-1, $cosePubKey) || $cosePubKey[-1] === null) {
                    throw new Exception('RSA Modulus missing');
                }

                $e = new BigInteger(bin2hex((string) $cosePubKey[-2]->get_byte_string()), 16);
                $n = new BigInteger(bin2hex((string) $cosePubKey[-1]->get_byte_string()), 16);
                return (string) PublicKeyLoader::load(['e' => $e, 'n' => $n]);
            default:
                return null;
        }
    }

    private static function publicKeyToPem(string $key): ?string
    {
        if (strlen($key) !== 65 || $key[0] !== "\x04") {
            return null;
        }

        $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01";
        $der .= "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42";
        $der .= "\x00" . $key;
        $pem = "-----BEGIN PUBLIC KEY-----\x0A";
        $pem .= chunk_split(string: base64_encode(string: $der), length: 64);

        return $pem . "-----END PUBLIC KEY-----\x0A";
    }
}
