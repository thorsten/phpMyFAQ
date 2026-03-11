<?php

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthHttpTest extends TestCase
{
    private AuthHttp $authHttp;
    private string $login;
    private string $password;
    private string $databaseFile;

    protected function setUp(): void
    {
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-auth-http-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $configuration = new Configuration($dbHandle);

        $this->login = 'testUserHttp_' . bin2hex(random_bytes(4));
        $this->password = 'testPassword';
        $_SERVER['PHP_AUTH_USER'] = $this->login;
        $_SERVER['PHP_AUTH_PW'] = $this->password;

        $this->authHttp = new AuthHttp($configuration);
        $this->authHttp->getEncryptionContainer('sha1');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

        if (isset($this->databaseFile) && file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $this->assertTrue($this->authHttp->create($this->login, $this->password));
    }

    public function testUpdate(): void
    {
        $this->assertTrue($this->authHttp->update($this->login, 'newTestPassword'));
    }

    public function testDelete(): void
    {
        $this->assertTrue($this->authHttp->delete($this->login));
    }

    public function testCheckCredentials(): void
    {
        $this->assertTrue($this->authHttp->checkCredentials($this->login, $this->password));
    }

    public function testCheckCredentialsReturnsFalseWhenUsernameIsMissing(): void
    {
        unset($_SERVER['PHP_AUTH_USER']);
        $_SERVER['PHP_AUTH_PW'] = $this->password;

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $configuration = new Configuration($dbHandle);
        $authHttp = new AuthHttp($configuration);

        $this->assertFalse($authHttp->checkCredentials($this->login, $this->password));
    }

    public function testIsValidLogin(): void
    {
        $this->assertEquals(1, $this->authHttp->isValidLogin($this->login));
    }
}
