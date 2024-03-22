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

    /**
     * @throws Exception
     */
    public function testCheckCredentials(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authHttp->create($login, $password);

        $this->assertTrue($this->authHttp->checkCredentials($login, $password));
    }

    /**
     * @throws Exception
     */
    public function testIsValidLogin(): void
    {
        $login = 'testUser';
        $password = 'testPassword';
        $this->authHttp->create($login, $password);

        $this->assertEquals(1, $this->authHttp->isValidLogin($login));
    }
}
