<?php

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthHttpTest extends TestCase
{
    private AuthHttp $authHttp;

    protected function setUp(): void
    {
        $this->authHttp = new AuthHttp(Configuration::getConfigurationInstance());
        $this->authHttp->getEncryptionContainer('sha1');
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $login = 'testUser';
        $password = 'testPassword';

        $this->assertTrue($this->authHttp->create($login, $password));
    }

    public function testUpdate(): void
    {
        $login = 'testUser';
        $password = 'newTestPassword';
        $this->assertTrue($this->authHttp->update($login, $password));
    }

    public function testDelete(): void
    {
        $login = 'testUser';
        $this->assertTrue($this->authHttp->delete($login));
    }

    public function testCheckCredentials(): void
    {
        $login = 'testUser';
        $password = 'testPassword';

        $this->assertTrue($this->authHttp->checkCredentials($login, $password));
    }

    public function testIsValidLogin(): void
    {
        $login = 'testUser';

        $this->assertEquals(1, $this->authHttp->isValidLogin($login));
    }
}
