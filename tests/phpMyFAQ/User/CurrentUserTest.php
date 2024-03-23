<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

class CurrentUserTest extends TestCase
{
    private CurrentUser $currentUser;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        session_start();

        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $this->currentUser = new CurrentUser($configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        session_destroy();
    }

    public function testIsLoggedInReturnsTrueWhenLoggedIn(): void
    {
        $this->currentUser->setLoggedIn(true);
        $this->assertTrue($this->currentUser->isLoggedIn());
    }

    public function testIsLoggedInReturnsFalseWhenNotLoggedIn(): void
    {
        $this->currentUser->setLoggedIn(false);
        $this->assertFalse($this->currentUser->isLoggedIn());
    }

    public function testSessionIsTimedOutReturnsFalseWhenNotTimedOut(): void
    {
        $this->currentUser->setSessionTimeout(3600); // 1 hour
        $this->assertFalse($this->currentUser->sessionIsTimedOut());
    }

    /**
     * @throws Exception
     */
    public function testLoginFailureWithInvalidCredentials(): void
    {
        $this->expectException(Exception::class);
        $this->currentUser->login('invalidLogin', 'invalidPassword');
    }

    /**
     * @throws Exception
     */
    public function testLoginSuccessWithValidCredentials(): void
    {
        $this->currentUser->login('admin', 'password');
        $this->assertTrue($this->currentUser->isLoggedIn());
    }

    /**
     * @throws Exception
     */
    public function testLoginSuccessWithValidCredentialsAndRememberMe(): void
    {
        $this->currentUser->setRememberMe(true);
        $this->currentUser->login('admin', 'password');
        $this->assertTrue($this->currentUser->isLoggedIn());
    }

    /**
     * @throws Exception
     */
    public function testIsLocalUser(): void
    {
        $this->currentUser->login('admin', 'password');
        $this->assertTrue($this->currentUser->isLocalUser());
    }

    /**
     * @throws Exception
     */
    public function testDeleteFromSession(): void
    {
        $this->currentUser->login('admin', 'password');
        $this->currentUser->deleteFromSession();
        $this->assertFalse($this->currentUser->isLoggedIn());
    }
}
