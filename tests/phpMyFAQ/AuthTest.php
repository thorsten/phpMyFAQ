<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthTest
 *
 * @package phpMyFAQ
 */
#[AllowMockObjectsWithoutExpectations]
class AuthTest extends TestCase
{
    use TestDatabaseTrait;

    /** @var Auth */
    protected Auth $auth;

    /** @var Configuration */
    protected Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = $this->connectToTestDatabaseCopy(new Sqlite3());
        $this->configuration = new Configuration($dbHandle);
        $this->auth = new Auth($this->configuration);
    }

    public function testGetEncryptionContainer(): void
    {
        $encryptionType = $this->auth->getEncryptionContainer('bcrypt');
        $this->assertInstanceOf('phpMyFAQ\EncryptionTypes\Bcrypt', $encryptionType);
    }

    public function testErrorWithNoError(): void
    {
        $this->auth->getEncryptionContainer('bcrypt');
        $this->assertEquals('', $this->auth->getErrors());
    }

    public function testErrorWithError(): void
    {
        $this->auth->getEncryptionContainer('foobar');
        $this->assertEquals("EncryptionTypes method could not be found.\n", $this->auth->getErrors());
    }

    /**
     * @throws Exception
     */
    public function testSelectAuth(): void
    {
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthDatabase', $this->auth->selectAuth('database'));
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthHttp', $this->auth->selectAuth('http'));
        $this->assertInstanceOf('phpMyFAQ\Auth\AuthSso', $this->auth->selectAuth('sso'));
    }

    public function testSelectAuthWithNonExistingAuth(): void
    {
        $this->expectException(Exception::class);
        $this->auth->selectAuth('foobar');
    }

    public function testEnableDisableReadOnly(): void
    {
        // initial state: false
        $this->assertFalse($this->auth->isReadOnly());

        // enable: returns previous (false), becomes true
        $prev = $this->auth->enableReadOnly();
        $this->assertFalse($prev);
        $this->assertTrue($this->auth->isReadOnly());

        // enable again: returns previous (true), stays true
        $prev2 = $this->auth->enableReadOnly();
        $this->assertTrue($prev2);
        $this->assertTrue($this->auth->isReadOnly());

        // disable: returns previous (true), becomes false
        $prev3 = $this->auth->disableReadOnly();
        $this->assertTrue($prev3);
        $this->assertFalse($this->auth->isReadOnly());

        // disable again: returns previous (false), stays false
        $prev4 = $this->auth->disableReadOnly();
        $this->assertFalse($prev4);
        $this->assertFalse($this->auth->isReadOnly());
    }

    /**
     * @throws Exception
     */
    public function testEncrypt(): void
    {
        $this->auth->getEncryptionContainer('bcrypt');
        $hash = $this->auth->encrypt('foobar');

        $this->assertIsString($hash);
        $this->assertStringNotContainsString('foobar', $hash);
    }

    public function testAddErrorCollectsMessages(): void
    {
        $this->auth->addError('first problem');
        $this->auth->addError('second problem');

        $this->assertEquals("first problem\nsecond problem\n", $this->auth->getErrors());
    }

    public function testSelectAuthRejectsAnAuthClassWithoutTheDriverInterface(): void
    {
        // AuthWebAuthn extends Auth but does not implement AuthDriverInterface.
        try {
            $this->auth->selectAuth('webauthn');
            $this->fail('selectAuth() must reject a class that is not an AuthDriverInterface');
        } catch (Exception $exception) {
            $this->assertSame('Specified authentication access class could not be found.', $exception->getMessage());
        }

        $this->assertStringContainsString('could not be found', $this->auth->getErrors());
    }

    public function testEncryptThrowsWithoutAnEncryptionContainer(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No encryption container configured');

        $this->auth->encrypt('foobar');
    }
}
