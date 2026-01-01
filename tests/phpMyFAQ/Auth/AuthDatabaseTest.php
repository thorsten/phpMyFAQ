<?php

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthDatabaseTest extends TestCase
{
    private AuthDatabase $authDatabase;

    protected function setUp(): void
    {
        $this->authDatabase = new AuthDatabase(Configuration::getConfigurationInstance());
        $this->authDatabase->getEncryptionContainer('sha1');
    }

    protected function tearDown(): void
    {
        try {
            $this->authDatabase->delete('testUser');
        } catch (Exception $e) {
            // Ignore
        }
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $login = 'testUser2';
        $password = 'testPassword2';

        $this->assertTrue($this->authDatabase->create($login, $password));
    }

    /**
     * @throws Exception
     */
    public function testCreateThrowsException(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->expectException(Exception::class);
        $this->authDatabase->create($login, $password);
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $login = 'testUser';
        $password = 'newTestPassword';
        $this->authDatabase->create($login, $password);

        $this->assertTrue($this->authDatabase->update($login, $password));
    }

    /**
     * @throws Exception
     */
    public function testUpdateThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->authDatabase->update('nonExistingUser', 'newPassword');
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->assertTrue($this->authDatabase->delete($login));
    }

    public function testDeleteThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->authDatabase->delete('nonExistingUser');
    }

    public function testCheckCredentials(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->assertTrue($this->authDatabase->checkCredentials($login, $password));
    }

    public function testCheckCredentialsThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->authDatabase->checkCredentials('nonExistingUser', 'password');
    }

    /**
     * @throws Exception
     */
    public function testIsValidLogin(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->assertEquals(1, $this->authDatabase->isValidLogin($login));
    }

    /**
     * @throws Exception
     */
    public function testIsValidLoginReturnsZero(): void
    {
        $this->assertEquals(0, $this->authDatabase->isValidLogin('nonExistingUser'));
    }
}
