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
use phpMyFAQ\Auth\WebAuthn\PublicKeyConverter;
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Utils;
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

        $this->setAppId(Utils::getHostFromUrl($configuration->getDefaultUrl()) ?? '');
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
            'userVerification' => 'preferred',
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
            'timeout' => 60_000,
            'excludeCredentials' => [],
            'extensions' => $extensions,
        ];

        // Base64 URL-encode the challenge for later verification
        $b64challenge = rtrim(
            string: strtr(string: base64_encode(string: $challenge), from: '+/', to: '-_'),
            characters: '=',
        );

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
        $webAuthnUser = $session->get('webauthn');

        return $webAuthnUser instanceof WebAuthnUser ? $webAuthnUser : null;
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
        $info = json_decode(json: $info, associative: false);

        if (!is_object($info)) {
            throw new Exception('info is not properly JSON encoded');
        }

        if (
            !property_exists($info, 'response')
            || !is_object($info->response)
            || !property_exists($info->response, 'attestationObject')
            || $info->response->attestationObject === null
        ) {
            throw new Exception('no attestationObject in info');
        }

        if (!property_exists($info, 'rawId') || $info->rawId === null || $info->rawId === []) {
            throw new Exception('no rawId in info');
        }

        $attestationString = $this->byteString($info->response->attestationObject);
        $attestationObject = (object) CBOREncoder::decode($attestationString);

        if (
            !property_exists($attestationObject, 'fmt')
            || $attestationObject->fmt === null
            || $attestationObject->fmt === ''
        ) {
            throw new Exception('Cannot decode key for format');
        }

        if (!property_exists($attestationObject, 'authData') || $attestationObject->authData === null) {
            throw new Exception('Cannot decode key for authentication data');
        }

        $authData = $attestationObject->authData;
        if (!is_object($authData) || !method_exists($authData, 'get_byte_string')) {
            throw new Exception('Cannot decode key for authentication data');
        }

        $byteString = (string) $authData->get_byte_string();

        if ($attestationObject->fmt === 'fido-u2f') {
            throw new Exception('Cannot decode FIDO format responses');
        }

        if ($attestationObject->fmt !== 'none' && $attestationObject->fmt !== 'packed') {
            throw new Exception('Cannot decode key for format if not none or packed');
        }

        $rpIdHash = substr(string: $byteString, offset: 0, length: 32);
        $flags = ord(substr(string: $byteString, offset: 32, length: 1));

        $hashId = hash(algo: 'sha256', data: $this->appId, binary: true);
        if ($hashId !== $rpIdHash) {
            throw new Exception('Cannot decode key as RP ID hash does not match');
        }

        if (($flags & 0x41) === 0) {
            throw new Exception('Cannot decode key as flags are not correct');
        }

        $credIdLen = (ord($byteString[53]) << 8) + ord($byteString[54]);
        $credId = substr(string: $byteString, offset: 55, length: $credIdLen);

        $cborPubKey = substr(string: $byteString, offset: 55 + $credIdLen);

        $keyBytes = PublicKeyConverter::fromCoseToPkcs($cborPubKey);

        if (is_null($keyBytes)) {
            $credIdLen = (ord($byteString[38]) << 8) + ord($byteString[39]);
            $credId = substr(string: $byteString, offset: 40, length: $credIdLen);
            $cborPubKey = substr(string: $byteString, offset: 40 + $credIdLen);
            $keyBytes = PublicKeyConverter::fromCoseToPkcs($cborPubKey);
            if (is_null($keyBytes)) {
                throw new Exception('Cannot decode key for key bytes');
            }
        }

        $rawId = $this->byteString($info->rawId);
        if ($credId !== $rawId) {
            throw new Exception('Cannot decode key for credId');
        }

        $publicKey = new stdClass();
        $publicKey->key = $keyBytes;
        $publicKey->id = $info->rawId;

        if ($userWebAuthn === '' || $userWebAuthn === '0') {
            return (string) json_encode([$publicKey]);
        }

        $existingKeys = json_decode($userWebAuthn);
        if (!is_array($existingKeys)) {
            $existingKeys = [];
        }

        $found = false;
        foreach ($existingKeys as $key) {
            if (!$key instanceof stdClass) {
                continue;
            }

            if ($this->idList($key->id) !== $this->idList($publicKey->id)) {
                continue;
            }

            $key->key = $publicKey->key;
            $found = true;
            break;
        }

        if (!$found) {
            array_unshift($existingKeys, $publicKey);
        }

        return (string) json_encode($existingKeys);
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
        $challengeB64 = rtrim(
            string: strtr(string: base64_encode(string: $challengeBytes), from: '+/', to: '-_'),
            characters: '=',
        );

        if ($userWebAuthn !== '' && $userWebAuthn !== '0') {
            $storedKeys = json_decode($userWebAuthn);
            if (is_array($storedKeys)) {
                foreach ($storedKeys as $key) {
                    if (!$key instanceof stdClass) {
                        continue;
                    }

                    $allow->id = $key->id;
                    $allows[] = clone $allow;
                    $key->challenge = $challengeB64;
                }

                $userWebAuthn = (string) json_encode($storedKeys);
            }
        }

        if ($userWebAuthn === '' || $userWebAuthn === '0') {
            $allow->id = [];
            $rb = md5((string) time());
            $allow->id = $this->stringToArray($rb);
            $allows[] = clone $allow;
        }

        /* generate key request */
        $publicKey = new stdClass();
        $publicKey->challenge = $this->stringToArray($challengeBytes);
        $publicKey->timeout = 60_000;
        $publicKey->allowCredentials = $allows;
        $publicKey->userVerification = 'preferred';
        $publicKey->rpId = str_replace(search: 'https://', replace: '', subject: $this->appId);

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
        $storedKeys = $userWebAuthn === '' || $userWebAuthn === '0' ? [] : json_decode($userWebAuthn);
        if (!is_array($storedKeys)) {
            $storedKeys = [];
        }

        $response = $info->response ?? null;
        if (!$response instanceof stdClass) {
            throw new Exception('No response in info');
        }

        $clientDataObject = $response->clientData ?? null;
        if (!$clientDataObject instanceof stdClass) {
            throw new Exception('No client data in info');
        }

        $rawIdList = $this->idList($info->rawId ?? null);

        $key = null;
        foreach ($storedKeys as $webAuthnKey) {
            if (!$webAuthnKey instanceof stdClass) {
                continue;
            }

            if ($this->idList($webAuthnKey->id) !== $rawIdList) {
                continue;
            }

            $key = $webAuthnKey;
            break;
        }

        if ($key === null) {
            throw new Exception('No key with ID ' . $rawIdList);
        }

        $originalChallenge = rtrim(
            strtr(
                string: base64_encode(string: $this->byteString($info->originalChallenge ?? null)),
                from: '+/',
                to: '-_',
            ),
            characters: '=',
        );
        if ($originalChallenge !== $clientDataObject->challenge) {
            throw new Exception('Challenge mismatch');
        }

        $keyChallenge = $key->challenge ?? null;
        if ($keyChallenge !== null && $keyChallenge !== $clientDataObject->challenge) {
            throw new Exception('You cannot use the same login more than once');
        }

        foreach ($storedKeys as $webAuthnKey) {
            if (!$webAuthnKey instanceof stdClass) {
                continue;
            }

            $webAuthnKey->challenge = '';
        }

        $userWebAuthn = (string) json_encode($storedKeys);

        $origin = parse_url((string) $clientDataObject->origin);
        $originHost = is_array($origin) ? $origin['host'] ?? null : null;
        if ($originHost !== $this->appId) {
            throw new Exception(sprintf("Origin mismatch for '%s'", (string) $clientDataObject->origin));
        }

        if ($clientDataObject->type !== 'webauthn.get') {
            throw new Exception(sprintf("Type mismatch for '%s'", (string) $clientDataObject->type));
        }

        $authDataString = $this->byteString($response->authenticatorData ?? null);

        $rpIdHash = substr(string: $authDataString, offset: 0, length: 32);
        $flags = ord(substr(string: $authDataString, offset: 32, length: 1));
        $counter = substr(string: $authDataString, offset: 33, length: 4);

        $hashId = hash(algo: 'sha256', data: $this->appId, binary: true);
        if ($hashId !== $rpIdHash) {
            throw new Exception('Cannot decode key response for RP ID hash');
        }

        if (($flags & 0x1) !== 0x1) {
            throw new Exception('Cannot decode key response (2c)');
        }

        $clientData = $this->byteString($response->clientDataJSONarray ?? null);
        $signedData = $hashId . chr($flags) . $counter . hash(algo: 'sha256', data: $clientData, binary: true);

        $signatureBytes = $response->signature ?? null;
        if (!is_array($signatureBytes) || count($signatureBytes) < 70) {
            throw new Exception('Cannot decode key response (3)');
        }

        $signature = $this->arrayToString($signatureBytes);

        $publicKeyPem = (string) $key->key;
        $verificationResult = openssl_verify($signedData, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);
        if ($verificationResult === 1) {
            return true;
        }

        if ($verificationResult === 0) {
            return false;
        }

        $opensslError = openssl_error_string();
        throw new Exception(
            'Cannot decode key response because of ' . ($opensslError === false ? 'unknown error' : $opensslError),
        );
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * Normalizes a JSON-decoded byte list to a binary string; non-arrays
     * yield an empty string so the callers' validation fails loudly.
     */
    private function byteString(mixed $bytes): string
    {
        return is_array($bytes) ? $this->arrayToString($bytes) : '';
    }

    /**
     * Renders a JSON-decoded credential ID byte list as a comparable string.
     */
    private function idList(mixed $id): string
    {
        if (!is_array($id)) {
            return '';
        }

        return implode(',', array_map(static fn(mixed $byte): string => is_scalar($byte) ? (string) $byte : '', $id));
    }

    /**
     * Convert an array of uint8's to a binary string
     *
     * @param array<array-key, mixed> $array
     */
    private function arrayToString(array $array): string
    {
        return implode('', array_map(chr(...), $array));
    }

    /**
     * Convert a binary string to an array of uint8's
     */
    private function stringToArray(string $string): array
    {
        return array_map(ord(...), str_split($string));
    }
}
