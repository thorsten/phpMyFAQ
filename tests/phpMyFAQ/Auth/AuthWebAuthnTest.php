<?php

namespace phpMyFAQ\Auth;

use CBOR\CBOREncoder;
use CBOR\Types\CBORByteString;
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class AuthWebAuthnTest extends TestCase
{
    private AuthWebAuthn $authWebAuthn;
    private Configuration $configuration;

    /**
     * @throws PluginException
     */
    protected function setUp(): void
    {
        $dbHandle = new PdoSqlite();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);

        $this->authWebAuthn = new AuthWebAuthn($this->configuration);
        $this->authWebAuthn->setAppId('example.com');
    }

    public function testPrepareChallengeForRegistration(): void
    {
        $username = 'testUser';
        $userId = '12345';

        $result = $this->authWebAuthn->prepareChallengeForRegistration($username, $userId);

        // Assert that the publicKey and b64challenge keys exist
        $this->assertArrayHasKey('publicKey', $result);
        $this->assertArrayHasKey('b64challenge', $result);

        // Assert that the challenge is an array of bytes
        $this->assertIsArray($result['publicKey']['challenge']);
        $this->assertCount(16, $result['publicKey']['challenge']);

        // Assert user info
        $this->assertEquals($username, $result['publicKey']['user']['name']);
        $this->assertEquals($username, $result['publicKey']['user']['displayName']);
        $this->assertIsArray($result['publicKey']['user']['id']);

        // Assert rp info
        $this->assertEquals('example.com', $result['publicKey']['rp']['name']);
        $this->assertEquals('example.com', $result['publicKey']['rp']['id']);

        // Assert pubKeyCredParams
        $this->assertCount(2, $result['publicKey']['pubKeyCredParams']);
        $this->assertEquals(-7, $result['publicKey']['pubKeyCredParams'][0]['alg']);
        $this->assertEquals('public-key', $result['publicKey']['pubKeyCredParams'][0]['type']);

        // Assert authenticatorSelection
        $this->assertFalse($result['publicKey']['authenticatorSelection']['requireResidentKey']);
        $this->assertEquals('preferred', $result['publicKey']['authenticatorSelection']['userVerification']);

        // Assert extensions
        $this->assertTrue($result['publicKey']['extensions']['exts']);

        // Assert the b64challenge is a string
        $this->assertIsString($result['b64challenge']);
    }

    public function testRegisterInvalidInfoJsonThrowsException(): void
    {
        // Invalid JSON string
        $info = '';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('info is not properly JSON encoded');

        $this->authWebAuthn->register($info, '', '');
    }

    public function testRegisterRejectsMismatchedChallenge(): void
    {
        // A ceremony whose clientDataJSON challenge does not match the challenge issued by
        // prepareChallengeForRegistration() must be rejected, so a fabricated or replayed
        // attestation cannot register a credential.
        $info = json_encode([
            'rawId' => [1, 2, 3],
            'response' => [
                'attestationObject' => [1, 2, 3],
                'clientDataJSON' => ['challenge' => 'attacker-supplied-challenge'],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Challenge mismatch');

        $this->authWebAuthn->register($info, '', 'server-issued-challenge');
    }

    public function testRegisterRejectsMissingExpectedChallenge(): void
    {
        // Fail closed: if no challenge was issued for this session, registration must be rejected
        // rather than accepting any client-supplied value.
        $info = json_encode([
            'rawId' => [1, 2, 3],
            'response' => [
                'attestationObject' => [1, 2, 3],
                'clientDataJSON' => ['challenge' => ''],
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Challenge mismatch');

        $this->authWebAuthn->register($info, '', '');
    }

    public function testStoreUserInSession(): void
    {
        $webAuthnUser = new \phpMyFAQ\Auth\WebAuthn\WebAuthnUser();
        $webAuthnUser->setId('12345');
        $webAuthnUser->setName('testuser');

        // Test that storeUserInSession doesn't throw exceptions
        $this->expectNotToPerformAssertions();
        $this->authWebAuthn->storeUserInSession($webAuthnUser);
    }

    public function testGetUserFromSessionWhenNoUserStored(): void
    {
        // Clear any existing session data
        $session = new Session();
        $session->remove('webauthn');

        $result = $this->authWebAuthn->getUserFromSession();
        $this->assertNull($result);
    }

    public function testGetUserFromSessionWhenUserStored(): void
    {
        $webAuthnUser = new \phpMyFAQ\Auth\WebAuthn\WebAuthnUser();
        $webAuthnUser->setId('12345');
        $webAuthnUser->setName('testuser');

        // Store user first
        $this->authWebAuthn->storeUserInSession($webAuthnUser);

        // Retrieve user
        $result = $this->authWebAuthn->getUserFromSession();

        $this->assertInstanceOf(\phpMyFAQ\Auth\WebAuthn\WebAuthnUser::class, $result);
        $this->assertEquals('12345', $result->getId());
        $this->assertEquals('testuser', $result->getName());
    }

    public function testSetAppId(): void
    {
        $newAppId = 'test.example.com';

        $this->authWebAuthn->setAppId($newAppId);

        // Test by generating a challenge and checking the rp name
        $result = $this->authWebAuthn->prepareChallengeForRegistration('testuser', '123');
        $this->assertEquals($newAppId, $result['publicKey']['rp']['name']);
        $this->assertEquals($newAppId, $result['publicKey']['rp']['id']);
    }

    public function testSetAppIdWithLocalhost(): void
    {
        $localhostAppId = 'localhost:3000';

        $this->authWebAuthn->setAppId($localhostAppId);

        // Test localhost behavior - should not have 'id' field in rp
        $result = $this->authWebAuthn->prepareChallengeForRegistration('testuser', '123');
        $this->assertEquals($localhostAppId, $result['publicKey']['rp']['name']);
        $this->assertArrayNotHasKey('id', $result['publicKey']['rp']);
    }

    public function testPrepareChallengeForRegistrationChallengeLengthAndFormat(): void
    {
        $username = 'testUser';
        $userId = '12345';

        $result = $this->authWebAuthn->prepareChallengeForRegistration($username, $userId);

        // Test challenge properties
        $challenge = $result['publicKey']['challenge'];
        $this->assertIsArray($challenge);
        $this->assertCount(16, $challenge);

        // Each byte should be between 0 and 255
        foreach ($challenge as $byte) {
            $this->assertIsInt($byte);
            $this->assertGreaterThanOrEqual(0, $byte);
            $this->assertLessThanOrEqual(255, $byte);
        }

        // Test b64challenge format
        $b64challenge = $result['b64challenge'];
        $this->assertIsString($b64challenge);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $b64challenge);
    }

    public function testPrepareChallengeForRegistrationUserIdConversion(): void
    {
        $username = 'testUser';
        $userId = 'user123';

        $result = $this->authWebAuthn->prepareChallengeForRegistration($username, $userId);

        $userIdArray = $result['publicKey']['user']['id'];
        $this->assertIsArray($userIdArray);
        $this->assertCount(strlen($userId), $userIdArray);

        // Each character should be converted to its ASCII value
        for ($i = 0; $i < strlen($userId); $i++) {
            $this->assertEquals(ord($userId[$i]), $userIdArray[$i]);
        }
    }

    public function testPrepareChallengeForRegistrationAlgorithmSupport(): void
    {
        $result = $this->authWebAuthn->prepareChallengeForRegistration('testuser', '123');

        $pubKeyCredParams = $result['publicKey']['pubKeyCredParams'];
        $this->assertCount(2, $pubKeyCredParams);

        // Test ES256 algorithm
        $this->assertEquals(-7, $pubKeyCredParams[0]['alg']);
        $this->assertEquals('public-key', $pubKeyCredParams[0]['type']);

        // Test RS256 algorithm
        $this->assertEquals(-257, $pubKeyCredParams[1]['alg']);
        $this->assertEquals('public-key', $pubKeyCredParams[1]['type']);
    }

    public function testPrepareChallengeForRegistrationTimeout(): void
    {
        $result = $this->authWebAuthn->prepareChallengeForRegistration('testuser', '123');

        $this->assertEquals(60000, $result['publicKey']['timeout']);
        $this->assertIsArray($result['publicKey']['excludeCredentials']);
        $this->assertEmpty($result['publicKey']['excludeCredentials']);
        $this->assertNull($result['publicKey']['attestation']);
    }

    public function testPrepareForLogin(): void
    {
        // Create a WebAuthn data structure that matches the expected format
        // The AuthWebAuthn::prepareForLogin() expects objects with 'id' property
        $userWebAuthn = json_encode([
            (object) [
                'id' => base64_encode('test-credential-id'),
                'publicKey' => 'test-public-key',
            ],
        ]);

        $result = $this->authWebAuthn->prepareForLogin($userWebAuthn);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertObjectHasProperty('challenge', $result);
        $this->assertObjectHasProperty('allowCredentials', $result);
        $this->assertEquals('preferred', $result->userVerification);
        $this->assertObjectHasProperty('timeout', $result);
    }

    public function testPrepareForLoginWithEmptyWebAuthn(): void
    {
        $userWebAuthn = '';

        $result = $this->authWebAuthn->prepareForLogin($userWebAuthn);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertObjectHasProperty('allowCredentials', $result);
        // Should have empty credentials array
        $this->assertIsArray($result->allowCredentials);
    }

    public function testAuthenticateWithInvalidJson(): void
    {
        // Create a more realistic but invalid authentication info structure
        $invalidInfo = new \stdClass();
        $invalidInfo->rawId = 'test-raw-id';
        $invalidInfo->response = new \stdClass();
        $invalidInfo->response->clientDataJSON = 'invalid-json-data';

        // Create proper WebAuthn structure with 'id' property for authenticate method
        $userWebAuthn = json_encode([
            (object) [
                'id' => base64_encode('test-credential-id'),
                'publicKey' => 'test-public-key',
            ],
        ]);

        // Malformed info now fails loudly with a typed exception instead of a TypeError
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No client data in info');
        $this->authWebAuthn->authenticate($invalidInfo, $userWebAuthn);
    }

    public function testConstructorSetsAppIdFromConfiguration(): void
    {
        // Test constructor behavior with configuration URL
        $result = $this->authWebAuthn->prepareChallengeForRegistration('testuser', '123');

        // Should extract host from reference URL
        $this->assertEquals('example.com', $result['publicKey']['rp']['name']);
    }

    public function testConstructorWithDifferentUrls(): void
    {
        // Test constructor behavior - the configuration is set in setUp,
        // so we need to test the actual behavior
        $result = $this->authWebAuthn->prepareChallengeForRegistration('testuser', '123');

        // Should use the configured URL from setUp (example.com)
        $this->assertEquals('example.com', $result['publicKey']['rp']['name']);
        $this->assertEquals('example.com', $result['publicKey']['rp']['id']);
    }

    public function testRegisterThrowsWhenAttestationObjectIsMissing(): void
    {
        $info = json_encode([
            'response' => [],
            'rawId' => [1, 2, 3],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no attestationObject in info');

        $this->authWebAuthn->register($info, '', '');
    }

    public function testRegisterThrowsWhenRawIdIsMissing(): void
    {
        $info = json_encode([
            'response' => ['attestationObject' => [1, 2, 3]],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no rawId in info');

        $this->authWebAuthn->register($info, '', '');
    }

    public function testRegisterThrowsWhenClientDataJsonIsMissing(): void
    {
        $info = json_encode([
            'response' => ['attestationObject' => [1, 2, 3]],
            'rawId' => [1, 2, 3],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no clientDataJSON in info');

        $this->authWebAuthn->register($info, '', 'issued-challenge');
    }

    public function testRegisterThrowsWhenChallengeDoesNotMatch(): void
    {
        $info = $this->createRegistrationInfo(challenge: 'attacker-supplied-challenge');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Challenge mismatch');

        $this->authWebAuthn->register($info, '', 'issued-challenge');
    }

    /**
     * Regression test: register() must reject the ceremony outright when no challenge was ever
     * issued for it (e.g. register() called without a preceding prepare() in this session),
     * rather than accepting whatever challenge the caller claims.
     */
    public function testRegisterThrowsWhenNoChallengeWasIssued(): void
    {
        $info = $this->createRegistrationInfo(challenge: 'anything');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Challenge mismatch');

        $this->authWebAuthn->register($info, '', '');
    }

    public function testRegisterThrowsWhenTypeDoesNotMatch(): void
    {
        $info = $this->createRegistrationInfo(challenge: 'issued-challenge', type: 'webauthn.get');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Type mismatch for 'webauthn.get'");

        $this->authWebAuthn->register($info, '', 'issued-challenge');
    }

    public function testRegisterThrowsWhenOriginDoesNotMatch(): void
    {
        $info = $this->createRegistrationInfo(challenge: 'issued-challenge', origin: 'https://evil.example');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Origin mismatch for 'https://evil.example'");

        $this->authWebAuthn->register($info, '', 'issued-challenge');
    }

    /**
     * End-to-end: a well-formed fmt=none attestation answering the issued challenge is accepted
     * and produces a stored key for the credential ID carried in the attestation.
     */
    public function testRegisterAcceptsWellFormedAttestationForIssuedChallenge(): void
    {
        [$info, $credId] = $this->createSignedRegistrationInfo(challenge: 'issued-challenge');

        $result = $this->authWebAuthn->register($info, '', 'issued-challenge');

        $keys = json_decode($result);
        self::assertIsArray($keys);
        self::assertCount(1, $keys);
        self::assertSame(array_values(unpack('C*', $credId)), $keys[0]->id);
        self::assertStringContainsString('BEGIN PUBLIC KEY', (string) $keys[0]->key);
    }

    /**
     * Builds a minimal registration payload with a forged (unsigned) attestation, whose
     * clientDataJSON carries the given challenge/origin/type. Used to exercise the ceremony-level
     * validation guard clauses without needing a real authenticator key pair.
     */
    private function createRegistrationInfo(
        string $challenge,
        string $origin = 'https://example.com',
        string $type = 'webauthn.create',
    ): string {
        return (string) json_encode([
            'rawId' => [1, 2, 3],
            'response' => [
                'attestationObject' => [1, 2, 3],
                'clientDataJSON' => (object) [
                    'challenge' => $challenge,
                    'origin' => $origin,
                    'type' => $type,
                ],
            ],
        ]);
    }

    /**
     * Builds a complete, well-formed fmt=none registration payload: a fresh EC P-256 key pair
     * encoded as a COSE key, wrapped in an authenticator data structure with the appId's RP ID
     * hash, and a matching clientDataJSON. Mirrors what a real browser ceremony produces for a
     * relying party that does not request attestation.
     *
     * @return array{0: string, 1: string} the JSON-encoded info payload and the raw credential ID
     */
    private function createSignedRegistrationInfo(
        string $challenge,
        string $origin = 'https://example.com',
    ): array {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $details = openssl_pkey_get_details($key);

        $cosePublicKey = [
            1 => 2,
            3 => -7,
            -1 => 1,
            -2 => new CBORByteString($details['ec']['x']),
            -3 => new CBORByteString($details['ec']['y']),
        ];
        $cborPublicKey = (string) CBOREncoder::encode($cosePublicKey);

        $credId = random_bytes(32);
        $rpIdHash = hash('sha256', 'example.com', true);
        $flags = chr(0x45);
        $counter = "\x00\x00\x00\x00";
        $aaguid = str_repeat("\x00", 16);
        $credIdLength = pack('n', strlen($credId));

        $authData = $rpIdHash . $flags . $counter . $aaguid . $credIdLength . $credId . $cborPublicKey;

        $attestationObject = (string) CBOREncoder::encode([
            'fmt' => 'none',
            'attStmt' => [],
            'authData' => new CBORByteString($authData),
        ]);

        $info = (string) json_encode([
            'rawId' => array_values(unpack('C*', $credId)),
            'response' => [
                'attestationObject' => array_values(unpack('C*', $attestationObject)),
                'clientDataJSON' => (object) [
                    'challenge' => $challenge,
                    'origin' => $origin,
                    'type' => 'webauthn.create',
                ],
            ],
        ]);

        return [$info, $credId];
    }

    public function testAuthenticateThrowsWhenNoMatchingKeyExists(): void
    {
        $info = $this->createAuthenticationInfo();
        $userWebAuthn = json_encode([
            (object) [
                'id' => [9, 9, 9],
                'key' => 'unused',
                'challenge' => '',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No key with ID 1,2,3');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsWhenChallengeDoesNotMatch(): void
    {
        $info = $this->createAuthenticationInfo(challenge: 'wrong-challenge');
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => 'pending-challenge',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Challenge mismatch');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsOnReplayChallenge(): void
    {
        // A previous successful login consumed the challenge and blanked it.
        $info = $this->createAuthenticationInfo(challenge: 'already-used-challenge');
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => '',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You cannot use the same login more than once');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    /**
     * Regression test: keys written by register() carry no challenge property at all. A stored key
     * without a pending challenge must fail closed instead of skipping the replay check.
     */
    public function testAuthenticateRejectsAssertionWhenNoChallengeWasStored(): void
    {
        $challenge = $this->base64UrlChallenge();
        $info = $this->createAuthenticationInfo(
            challenge: $challenge,
            origin: 'https://example.com',
            type: 'webauthn.get',
        );
        $this->addUnsignedDecoy($info, $challenge, 'https://example.com', 'webauthn.get');

        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You cannot use the same login more than once');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    /**
     * Regression test: the challenge must be read from the signed clientDataJSON, not from the
     * unsigned clientData object the client sends alongside it. Otherwise a replayed assertion
     * passes by simply claiming the freshly issued challenge.
     */
    public function testAuthenticateVerifiesChallengeAgainstSignedClientDataJson(): void
    {
        $freshChallenge = $this->base64UrlChallenge();

        // The replayed assertion was signed over a stale challenge...
        $info = $this->createAuthenticationInfo(
            challenge: 'stale-captured-challenge',
            origin: 'https://example.com',
            type: 'webauthn.get',
        );
        // ...but every unsigned field the attacker controls claims the freshly issued one.
        $this->addUnsignedDecoy($info, $freshChallenge, 'https://example.com', 'webauthn.get');

        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => $freshChallenge,
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Challenge mismatch');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    /**
     * Regression test: the origin must likewise come from the signed clientDataJSON.
     */
    public function testAuthenticateVerifiesOriginAgainstSignedClientDataJson(): void
    {
        $challenge = $this->base64UrlChallenge();

        $info = $this->createAuthenticationInfo(
            challenge: $challenge,
            origin: 'https://evil.example',
            type: 'webauthn.get',
        );
        $this->addUnsignedDecoy($info, $challenge, 'https://example.com', 'webauthn.get');

        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => $challenge,
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Origin mismatch for 'https://evil.example'");

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsWhenOriginDoesNotMatch(): void
    {
        $info = $this->createAuthenticationInfo(challenge: 'pending-challenge');
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => 'pending-challenge',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Origin mismatch for 'https://evil.example'");

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsWhenTypeDoesNotMatch(): void
    {
        $info = $this->createAuthenticationInfo(challenge: 'pending-challenge', origin: 'https://example.com');
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => 'pending-challenge',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Type mismatch for 'webauthn.create'");

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsWhenSignatureIsTooShort(): void
    {
        $info = $this->createAuthenticationInfo(
            challenge: 'pending-challenge',
            origin: 'https://example.com',
            type: 'webauthn.get',
        );
        $info->response->authenticatorData = array_merge(array_fill(0, 32, 0), [0x01], [0, 0, 0, 1]);
        $info->response->signature = [1, 2, 3];
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => 'pending-challenge',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for RP ID hash');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    /**
     * End-to-end: a properly signed assertion answering the pending challenge authenticates once,
     * and replaying that exact same payload afterwards is rejected.
     */
    public function testAuthenticateAcceptsSignedAssertionOnceAndRejectsTheReplay(): void
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        self::assertInstanceOf(\OpenSSLAsymmetricKey::class, $privateKey);

        $details = openssl_pkey_get_details($privateKey);
        self::assertIsArray($details);

        $userWebAuthn = (string) json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => $details['key'],
            ],
        ]);

        // The server issues a challenge and persists the stamped keys, as the controller now does.
        $this->authWebAuthn->prepareForLogin($userWebAuthn);
        $pendingChallenge = json_decode($userWebAuthn)[0]->challenge;
        self::assertNotEmpty($pendingChallenge);

        $info = $this->createSignedAssertion($privateKey, $pendingChallenge);

        self::assertTrue($this->authWebAuthn->authenticate($info, $userWebAuthn));

        // The consumed challenge was blanked on the keys the caller persists.
        self::assertSame('', json_decode($userWebAuthn)[0]->challenge);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You cannot use the same login more than once');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    /**
     * Builds an assertion signed the way an authenticator signs one: over the authenticator data
     * followed by the hash of the clientDataJSON.
     */
    private function createSignedAssertion(\OpenSSLAsymmetricKey $privateKey, string $challenge): \stdClass
    {
        $clientDataJson = (string) json_encode([
            'challenge' => $challenge,
            'origin' => 'https://example.com',
            'type' => 'webauthn.get',
        ]);

        $rpIdHash = hash('sha256', 'example.com', true);
        $flags = chr(0x01);
        $counter = "\0\0\0\1";
        $authenticatorData = $rpIdHash . $flags . $counter;

        $signature = '';
        openssl_sign(
            $authenticatorData . hash('sha256', $clientDataJson, true),
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256,
        );

        $info = new \stdClass();
        $info->rawId = [1, 2, 3];
        $info->response = new \stdClass();
        $info->response->authenticatorData = array_map(ord(...), str_split($authenticatorData));
        $info->response->clientDataJSONarray = array_map(ord(...), str_split($clientDataJson));
        $info->response->signature = array_map(ord(...), str_split($signature));

        return $info;
    }

    public function testArrayStringConversionHelpers(): void
    {
        $reflection = new ReflectionClass($this->authWebAuthn);

        $arrayToString = $reflection->getMethod('arrayToString');
        $stringToArray = $reflection->getMethod('stringToArray');

        $binary = $arrayToString->invoke($this->authWebAuthn, [65, 66, 67]);
        $this->assertSame('ABC', $binary);
        $this->assertSame([65, 66, 67], $stringToArray->invoke($this->authWebAuthn, 'ABC'));
    }

    private function createAuthenticationInfo(
        string $challenge = 'unused',
        string $origin = 'https://evil.example',
        string $type = 'webauthn.create',
    ): \stdClass {
        $info = new \stdClass();
        $info->rawId = [1, 2, 3];
        $info->response = new \stdClass();
        $info->response->authenticatorData = [];
        $info->response->clientDataJSONarray = $this->clientDataJsonArray($challenge, $origin, $type);
        $info->response->signature = [];

        return $info;
    }

    /**
     * A challenge in the base64url shape prepareForLogin() issues and stores.
     */
    private function base64UrlChallenge(string $bytes = 'sixteen-byte-chg'): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Adds the unsigned fields an attacker fully controls: the pre-parsed clientData object and the
     * echoed originalChallenge. A correct implementation ignores both and trusts only the signed
     * clientDataJSON, so setting them to values the server would accept must not help.
     */
    private function addUnsignedDecoy(\stdClass $info, string $challenge, string $origin, string $type): void
    {
        $info->response->clientData = (object) [
            'challenge' => $challenge,
            'origin' => $origin,
            'type' => $type,
        ];
        $info->originalChallenge = array_map(ord(...), str_split(
            (string) base64_decode(strtr($challenge, '-_', '+/'), true),
        ));
    }

    /**
     * Builds the byte list of the clientDataJSON the authenticator signed over.
     *
     * @return int[]
     */
    private function clientDataJsonArray(string $challenge, string $origin, string $type): array
    {
        $clientDataJson = (string) json_encode([
            'challenge' => $challenge,
            'origin' => $origin,
            'type' => $type,
        ]);

        return array_map(ord(...), str_split($clientDataJson));
    }
}
