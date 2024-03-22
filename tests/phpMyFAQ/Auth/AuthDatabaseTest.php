<?php

namespace phpMyFAQ\Auth;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;

class AuthDatabaseTest extends TestCase
{
    private AuthDatabase $authDatabase;

    protected function setUp(): void
    {
        $this->authDatabase = new AuthDatabase(Configuration::getConfigurationInstance());
        $this->authDatabase->selectEncType('sha1');
    }

    protected function tearDown(): void
    {
        $this->authDatabase->delete('testUser');
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $login = 'testUser';
        $password = 'testPassword';

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

    public function testCheckCredentials(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->assertTrue($this->authDatabase->checkCredentials($login, $password));
    }

    public function testIsValidLogin(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authDatabase->create($login, $password);

        $this->assertEquals(1, $this->authDatabase->isValidLogin($login));
    }
}
