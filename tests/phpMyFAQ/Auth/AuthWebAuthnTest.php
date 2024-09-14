<?php

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\TestCase;

class AuthWebAuthnTest extends TestCase
{
    private AuthWebAuthn $authWebAuthn;
    private Configuration $configuration;

    /**
     * @throws PluginException
     */
    protected function setUp(): void
    {

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.referenceURL', 'https://example.com:443/');

        $this->authWebAuthn = new AuthWebAuthn($this->configuration);
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
}
