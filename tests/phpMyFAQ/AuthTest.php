<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthTest
 *
 * @package phpMyFAQ
 */
class AuthTest extends TestCase
{
    /** @var Auth */
    protected Auth $auth;

    /** @var Configuration */
    protected Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->auth = new Auth($this->configuration);
    }

    public function testSelectEncType(): void
    {
        $encryptionType = $this->auth->selectEncType('bcrypt');
        $this->assertInstanceOf('phpMyFAQ\EncryptionTypes\Bcrypt', $encryptionType);
    }

    public function testErrorWithNoError(): void
    {
        $this->auth->selectEncType('bcrypt');

        $this->assertEquals('', $this->auth->error());
    }

    public function testErrorWithError(): void
    {
        $this->auth->selectEncType('foobar');

        $this->assertEquals("EncryptionTypes method could not be found.\n", $this->auth->error());
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

    public function testSetReadOnly(): void
    {
        $this->assertFalse($this->auth->setReadOnly());
        $this->assertFalse($this->auth->setReadOnly(true));
        $this->assertTrue($this->auth->setReadOnly());
    }

    public function testEncrypt(): void
    {
        $this->auth->selectEncType('bcrypt');
        $hash = $this->auth->encrypt('foobar');

        $this->assertIsString($hash);
        $this->assertStringNotContainsString('foobar', $hash);
    }
}
