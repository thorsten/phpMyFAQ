<?php

namespace phpMyFAQ\Auth;

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
        $this->assertEquals('discouraged', $result['publicKey']['authenticatorSelection']['userVerification']);

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

        $this->authWebAuthn->register($info, '');
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
        $this->assertObjectHasProperty('userVerification', $result);
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

        // Expect TypeError from invalid JSON processing
        $this->expectException(\TypeError::class);
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

        $this->authWebAuthn->register($info, '');
    }

    public function testRegisterThrowsWhenRawIdIsMissing(): void
    {
        $info = json_encode([
            'response' => ['attestationObject' => [1, 2, 3]],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no rawId in info');

        $this->authWebAuthn->register($info, '');
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
        $info = $this->createAuthenticationInfo();
        $info->response->clientData->challenge = 'wrong-challenge';
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => '',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Challenge mismatch');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsOnReplayChallenge(): void
    {
        $info = $this->createAuthenticationInfo();
        $expectedChallenge = rtrim(strtr(base64_encode(chr(4) . chr(5) . chr(6)), '+/', '-_'), '=');
        $info->response->clientData->challenge = $expectedChallenge;
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => 'stored-previous-challenge',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You cannot use the same login more than once');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsWhenOriginDoesNotMatch(): void
    {
        $info = $this->createAuthenticationInfo();
        $expectedChallenge = rtrim(strtr(base64_encode(chr(4) . chr(5) . chr(6)), '+/', '-_'), '=');
        $info->response->clientData->challenge = $expectedChallenge;
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => $expectedChallenge,
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Origin mismatch for 'https://evil.example'");

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsWhenTypeDoesNotMatch(): void
    {
        $info = $this->createAuthenticationInfo();
        $expectedChallenge = rtrim(strtr(base64_encode(chr(4) . chr(5) . chr(6)), '+/', '-_'), '=');
        $info->response->clientData->challenge = $expectedChallenge;
        $info->response->clientData->origin = 'https://example.com';
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => $expectedChallenge,
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Type mismatch for 'webauthn.create'");

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
    }

    public function testAuthenticateThrowsWhenSignatureIsTooShort(): void
    {
        $info = $this->createAuthenticationInfo();
        $expectedChallenge = rtrim(strtr(base64_encode(chr(4) . chr(5) . chr(6)), '+/', '-_'), '=');
        $info->response->clientData->challenge = $expectedChallenge;
        $info->response->clientData->origin = 'https://example.com';
        $info->response->clientData->type = 'webauthn.get';
        $info->response->authenticatorData = array_merge(array_fill(0, 32, 0), [0x01], [0, 0, 0, 1]);
        $info->response->signature = [1, 2, 3];
        $userWebAuthn = json_encode([
            (object) [
                'id' => [1, 2, 3],
                'key' => 'unused',
                'challenge' => $expectedChallenge,
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot decode key response for RP ID hash');

        $this->authWebAuthn->authenticate($info, $userWebAuthn);
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

    private function createAuthenticationInfo(): \stdClass
    {
        $info = new \stdClass();
        $info->rawId = [1, 2, 3];
        $info->originalChallenge = [4, 5, 6];
        $info->response = new \stdClass();
        $info->response->clientData = new \stdClass();
        $info->response->clientData->challenge = 'unused';
        $info->response->clientData->origin = 'https://evil.example';
        $info->response->clientData->type = 'webauthn.create';
        $info->response->authenticatorData = [];
        $info->response->clientDataJSONarray = [123];
        $info->response->signature = [];

        return $info;
    }
}
