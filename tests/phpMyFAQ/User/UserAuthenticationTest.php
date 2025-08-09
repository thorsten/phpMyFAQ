<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Translation;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;

class UserAuthenticationTest extends TestCase
{
    private CurrentUser $currentUser;
    private UserAuthentication $userAuth;
    private Configuration $configuration;

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();


        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);

        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->userAuth = new UserAuthentication($this->configuration, $this->currentUser);
    }

    public function testIsRememberMe(): void
    {
        $this->assertFalse($this->userAuth->isRememberMe());
        $this->userAuth->setRememberMe(true);
        $this->assertTrue($this->userAuth->isRememberMe());
    }

    public function testHasTwoFactorAuthentication(): void
    {
        $this->assertFalse($this->userAuth->hasTwoFactorAuthentication());
        $this->userAuth->setTwoFactorAuth(true);
        $this->assertTrue($this->userAuth->hasTwoFactorAuthentication());
    }

    /**
     * @throws UserException
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testAuthenticateWithLocal(): void
    {
        // Only start session if not already active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentUser = new CurrentUser($this->configuration);
        $userAuth = new UserAuthentication($this->configuration, $currentUser);

        $userAuth->authenticate('admin', 'password');
        $this->assertTrue($currentUser->isLoggedIn());
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testAuthenticateFails(): void
    {
        $this->currentUser->method('login')->willReturn(false);

        $this->expectException(UserException::class);
        $this->userAuth->authenticate('username', 'password');
    }
}
