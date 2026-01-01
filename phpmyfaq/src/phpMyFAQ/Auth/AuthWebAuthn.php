<?php

/**
 * Manages user authentication via WebAuthn.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use CBOR\CBOREncoder;
use phpMyFAQ\Auth;
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Utils;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;
use Random\RandomException;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthWebAuthn extends Auth
{
    private string $appId;

    private const int ES256 = -7;

    private const int RS256 = -257;

    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);

        $this->setAppId(Utils::getHostFromUrl($configuration->getDefaultUrl()));
    }

    /**
     * Generate a challenge ready for registering a hardware key, fingerprint or whatever
     *
     * @return array<string, array<string, array|int|null>|string>
     * @throws RandomException
     */
    public function prepareChallengeForRegistration(string $username, string $userId): array
    {
        $challenge = random_bytes(16);

        // Convert the challenge to an array of bytes
        $challengeArray = $this->stringToArray($challenge);

        // Prepare user information
        $user = [
            'name' => $username,
            'displayName' => $username,
            'id' => $this->stringToArray($userId),
        ];

        // Prepare relying party (rp) information
        $relyingParty = [
            'name' => $this->appId,
        ];

        // Set the 'id' field if not running on localhost
        if (!str_contains($this->appId, 'localhost')) {
            $relyingParty['id'] = $this->appId;
        }

        // Prepare public key credential parameters
        $pubKeyCredParams = [
            [
                'alg' => self::ES256,
                'type' => 'public-key',
            ],
            [
                'alg' => self::RS256,
                'type' => 'public-key',
            ],
        ];

        // Prepare authenticator selection criteria
        $authSelection = [
            'requireResidentKey' => false,
            'userVerification' => 'discouraged',
        ];

        // Prepare extensions
        $extensions = [
            'exts' => true,
        ];

        // Build the publicKey object
        $publicKey = [
            'challenge' => $challengeArray,
            'user' => $user,
            'rp' => $relyingParty,
            'pubKeyCredParams' => $pubKeyCredParams,
            'authenticatorSelection' => $authSelection,
            'attestation' => null,
            'timeout' => 60000,
            'excludeCredentials' => [],
            'extensions' => $extensions,
        ];

        // Base64 URL-encode the challenge for later verification
        $b64challenge = rtrim(strtr(base64_encode($challenge), '+/', '-_'), '=');

        // Return the prepared data
        return [
            'publicKey' => $publicKey,
            'b64challenge' => $b64challenge,
        ];
    }

    /**
     * Store the WebAuth user information in the session
     */
    public function storeUserInSession(WebAuthnUser $webAuthnUser): void
    {
        $session = new Session();
        $session->set('webauthn', $webAuthnUser);
    }

    /**
     * Get the WebAuth user information from the session
     */
    public function getUserFromSession(): ?WebAuthnUser
    {
        $session = new Session();
        return $session->get('webauthn');
    }

    /**
     * Registers a new key for a user, requires info from the hardware via JavaScript given below and returns a modified
     * user's webauthn field in your database
     *
     * @param string $info Info provided by the key
     * @param string $userWebAuthn The existing WebAuthn field for the user
     * @throws Exception
     * @throws \Exception
     */
    public function register(string $info, string $userWebAuthn): string
    {
        $info = html_entity_decode($info);
        $info = json_decode($info, false);

        if (empty($info)) {
            throw new Exception('info is not properly JSON encoded');
        }

        if (empty($info->response->attestationObject)) {
            throw new Exception('no attestationObject in info');
        }

        if (empty($info->rawId)) {
            throw new Exception('no rawId in info');
        }

        $attestationString = $this->arrayToString($info->response->attestationObject);
        $attestationObject = (object) CBOREncoder::decode($attestationString);

        if (empty($attestationObject->fmt)) {
            throw new Exception('Cannot decode key for format');
        }

        if (empty($attestationObject->authData)) {
            throw new Exception('Cannot decode key for authentication data');
        }

        $byteString = $attestationObject->authData->get_byte_string();

        if ($attestationObject->fmt === 'fido-u2f') {
            throw new Exception('Cannot decode FIDO format responses');
        }

        if ($attestationObject->fmt !== 'none' && $attestationObject->fmt !== 'packed') {
            throw new Exception('Cannot decode key for format if not none or packed');
        }

        $attestationObject->rpIdHash = substr((string) $byteString, 0, 32);
        $attestationObject->flags = ord(substr((string) $byteString, 32, 1));
        $attestationObject->counter = substr((string) $byteString, 33, 4);

        $hashId = hash('sha256', $this->appId, true);
        if ($hashId !== $attestationObject->rpIdHash) {
            throw new Exception('Cannot decode key as RP ID hash does not match');
        }

        if (($attestationObject->flags & 0x41) === 0) {
            throw new Exception('Cannot decode key as flags are not correct');
        }

        $attestationObject->attData = new stdClass();
        $attestationObject->attData->aaguid = substr((string) $byteString, 37, 16);
        $attestationObject->attData->credIdLen = (ord($byteString[53]) << 8) + ord($byteString[54]);
        $attestationObject->attData->credId = substr((string) $byteString, 55, $attestationObject->attData->credIdLen);

        $cborPubKey = substr((string) $byteString, 55 + $attestationObject->attData->credIdLen);

        $attestationObject->attData->keyBytes = self::COSEECDHAtoPKCS($cborPubKey);

        if (is_null($attestationObject->attData->keyBytes)) {
            $attestationObject->attData->aaguid = substr((string) $byteString, 37, 1);
            $attestationObject->attData->credIdLen = (ord($byteString[38]) << 8) + ord($byteString[39]);
            $attestationObject->attData->credId = substr(
                (string) $byteString,
                40,
                $attestationObject->attData->credIdLen,
            );
            $cborPubKey = substr((string) $byteString, 40 + $attestationObject->attData->credIdLen);
            $attestationObject->attData->keyBytes = self::COSEECDHAtoPKCS($cborPubKey);
            if (is_null($attestationObject->attData->keyBytes)) {
                throw new Exception('Cannot decode key for key bytes');
            }
        }

        $rawId = $this->arrayToString($info->rawId);
        if ($attestationObject->attData->credId !== $rawId) {
            throw new Exception('Cannot decode key for credId');
        }

        $publicKey = new stdClass();
        $publicKey->key = $attestationObject->attData->keyBytes;
        $publicKey->id = $info->rawId;

        if ($userWebAuthn === '' || $userWebAuthn === '0') {
            $userWebAuthn = [$publicKey];
        } else {
            $userWebAuthn = json_decode($userWebAuthn);
            $found = false;
            foreach ($userWebAuthn as $key) {
                if (implode(',', $key->id) !== implode(',', $publicKey->id)) {
                    continue;
                }

                $key->key = $publicKey->key;
                $found = true;
                break;
            }

            if (!$found) {
                array_unshift($userWebAuthn, $publicKey);
            }
        }

        return json_encode($userWebAuthn);
    }

    /**
     * Generates a new key string for the physical key, fingerprint reader, or whatever to respond to on login.
     * You should store the revised $userWebAuthn back to your database after calling this function
     * (to avoid replay attacks)
     *
     * @param string $userWebAuthn the existing webauthn field for the user from your database
     * @throws RandomException
     */
    public function prepareForLogin(string &$userWebAuthn): stdClass
    {
        $allow = new stdClass();
        $allow->type = 'public-key';
        $allow->transports = ['usb', 'nfc', 'ble', 'internal'];
        $allow->id = null;

        $allows = [];

        $challengeBytes = random_bytes(16);
        $challengeB64 = rtrim(strtr(base64_encode($challengeBytes), '+/', '-_'), '=');

        if ($userWebAuthn !== '' && $userWebAuthn !== '0') {
            $webauthn = json_decode($userWebAuthn);
            foreach ($webauthn as $idx => $key) {
                $allow->id = $key->id;
                $allows[] = clone $allow;
                $webauthn[$idx]->challenge = $challengeB64;
            }

            $userWebAuthn = json_encode($webauthn);
        } else {
            $allow->id = [];
            $rb = md5((string) time());
            $allow->id = $this->stringToArray($rb);
            $allows[] = clone $allow;
        }

        /* generate key request */
        $publicKey = new stdClass();
        $publicKey->challenge = $this->stringToArray($challengeBytes);
        $publicKey->timeout = 60000;
        $publicKey->allowCredentials = $allows;
        $publicKey->userVerification = 'discouraged';
        $publicKey->rpId = str_replace('https://', '', $this->appId);

        return $publicKey;
    }

    /**
     * Validates a response for login or 2FA, requires info from the hardware via JavaScript given below.
     *
     * @param string $userWebAuthn the existing webauthn field for the user
     * @throws Exception
     */
    public function authenticate(stdClass $info, string &$userWebAuthn): bool
    {
        $webauthn = $userWebAuthn === '' || $userWebAuthn === '0' ? [] : json_decode($userWebAuthn);

        $key = null;
        foreach ($webauthn as $webAuthnKey) {
            if (implode(',', $webAuthnKey->id) !== implode(',', $info->rawId)) {
                continue;
            }

            $key = $webAuthnKey;
            break;
        }

        if (empty($key)) {
            throw new Exception('No key with ID ' . implode(',', $info->rawId));
        }

        $originalChallenge = rtrim(
            strtr(base64_encode($this->arrayToString($info->originalChallenge)), '+/', '-_'),
            '=',
        );
        if ($originalChallenge != $info->response->clientData->challenge) {
            throw new Exception('Challenge mismatch');
        }

        if (isset($key->challenge) && $key->challenge != $info->response->clientData->challenge) {
            throw new Exception('You cannot use the same login more than once');
        }

        foreach ($webauthn as $idx => $webAuthnKey) {
            $webauthn[$idx]->challenge = '';
        }

        $userWebAuthn = json_encode($webauthn);

        $origin = parse_url((string) $info->response->clientData->origin);
        if ($this->appId !== $origin['host']) {
            throw new Exception(sprintf("Origin mismatch for '%s'", $info->response->clientData->origin));
        }

        if ($info->response->clientData->type !== 'webauthn.get') {
            throw new Exception(sprintf("Type mismatch for '%s'", $info->response->clientData->type));
        }

        $authDataString = $this->arrayToString($info->response->authenticatorData);

        $authenticatorData = new stdClass();
        $authenticatorData->rpIdHash = substr($authDataString, 0, 32);
        $authenticatorData->flags = ord(substr($authDataString, 32, 1));
        $authenticatorData->counter = substr($authDataString, 33, 4);

        $hashId = hash('sha256', $this->appId, true);
        if ($hashId !== $authenticatorData->rpIdHash) {
            throw new Exception('Cannot decode key response for RP ID hash');
        }

        if (($authenticatorData->flags & 0x1) != 0x1) {
            throw new Exception('Cannot decode key response (2c)');
        }

        $clientData = $this->arrayToString($info->response->clientDataJSONarray);
        $signedData =
            $hashId . chr($authenticatorData->flags) . $authenticatorData->counter . hash('sha256', $clientData, true);

        if (count($info->response->signature) < 70) {
            throw new Exception('Cannot decode key response (3)');
        }

        $signature = $this->arrayToString($info->response->signature);

        $key = $key->key;
        return match (openssl_verify($signedData, $signature, $key, OPENSSL_ALGO_SHA256)) {
            1 => true,
            0 => false,
            default => throw new Exception('Cannot decode key response because of ' . openssl_error_string()),
        };
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * Convert an array of uint8's to a binary string
     */
    private function arrayToString(array $array): string
    {
        return implode('', array_map('chr', $array));
    }

    /**
     * Convert a binary string to an array of uint8's
     */
    private function stringToArray(string $string): array
    {
        return array_map('ord', str_split($string));
    }

    /**
     * Convert a public key from the hardware to PEM format
     */
    private function publicKeyToPem(string $key): ?string
    {
        if (strlen($key) !== 65 || $key[0] !== "\x04") {
            return null;
        }

        $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01";
        $der .= "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42";
        $der .= "\x00" . $key;
        $pem = "-----BEGIN PUBLIC KEY-----\x0A";
        $pem .= chunk_split(base64_encode($der), 64);
        return $pem . "-----END PUBLIC KEY-----\x0A";
    }

    /**
     * Convert COSE ECDHA to PKCS
     *
     * @param string $binary binary string to be converted
     * @return string|null converted public key
     * @throws Exception
     * @throws \Exception
     */
    private function COSEECDHAtoPKCS(string $binary): ?string
    {
        $cosePubKey = CBOREncoder::decode($binary);

        if (!isset($cosePubKey[3])) { /* cose_alg */
            return null;
        }

        switch ($cosePubKey[3]) {
            case self::ES256:
                /* COSE Alg: ECDSA w/ SHA-256 */
                if (!isset($cosePubKey[-1])) { /* cose_crv */
                    throw new Exception('Cannot decode key response for curve');
                }

                if (!isset($cosePubKey[-2])) { /* cose_crv_x */
                    throw new Exception('Cannot decode key response for x coordinate');
                }

                if ($cosePubKey[-1] !== 1) { /* cose_crv_P256 */
                    throw new Exception('Cannot decode key response for curve P256');
                }

                if (!isset($cosePubKey[-2])) { /* cose_crv_x */
                    throw new Exception('x coordinate for curve missing');
                }

                if (!isset($cosePubKey[1])) { /* cose_kty */
                    throw new Exception('Cannot decode key response for key type');
                }

                if (!isset($cosePubKey[-3])) { /* cose_crv_y */
                    throw new Exception('Cannot decode key response for y coordinate');
                }

                if (!isset($cosePubKey[-3])) { /* cose_crv_y */
                    throw new Exception('y coordinate for curve missing');
                }

                if ($cosePubKey[1] !== 2) { /* cose_kty_ec2 */
                    throw new Exception('Cannot decode key response for key type EC2');
                }

                $x = $cosePubKey[-2]->get_byte_string();
                $y = $cosePubKey[-3]->get_byte_string();
                if (strlen((string) $x) != 32 || strlen((string) $y) != 32) {
                    throw new Exception('Cannot decode key response for x or y coordinate');
                }

                $tag = "\x04";
                return $this->publicKeyToPem($tag . $x . $y);
            case self::RS256:
                if (!isset($cosePubKey[-2])) {
                    throw new Exception('RSA Exponent missing');
                }

                if (!isset($cosePubKey[-1])) {
                    throw new Exception('RSA Modulus missing');
                }

                $e = new BigInteger(bin2hex((string) $cosePubKey[-2]->get_byte_string()), 16);
                $n = new BigInteger(bin2hex((string) $cosePubKey[-1]->get_byte_string()), 16);
                return (string) PublicKeyLoader::load(['e' => $e, 'n' => $n]);
            default:
                throw new Exception('Cannot decode key response for algorithm');
        }
    }
}
