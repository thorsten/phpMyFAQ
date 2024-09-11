<?php

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;

class AuthHttpTest extends TestCase
{
    private AuthHttp $authHttp;

    protected function setUp(): void
    {
        $this->authHttp = new AuthHttp(Configuration::getConfigurationInstance());
        $this->authHttp->selectEncType('sha1');
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

    /**
     * @throws Exception
     */
    public function testCheckCredentials(): void
    {
        $login = 'testUser';
        $password = 'testPassword';

        $this->assertTrue($this->authHttp->checkCredentials($login, $password));
    }

    /**
     * @throws Exception
     */
    public function testIsValidLogin(): void
    {
        $login = 'testUser';

        $this->assertEquals(1, $this->authHttp->isValidLogin($login));
    }
}
