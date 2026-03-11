<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CurrentUserTest extends TestCase
{
    private CurrentUser $currentUser;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        // Check if session is already started before calling session_start()
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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

    public function testTwoFactorSuccessMarksUserAsLoggedIn(): void
    {
        $currentUser = $this
            ->getMockBuilder(CurrentUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateSessionId', 'saveToSession', 'setSuccess'])
            ->getMock();

        $currentUser->expects($this->once())->method('updateSessionId')->with(true)->willReturn(true);
        $currentUser->expects($this->once())->method('saveToSession');
        $currentUser->expects($this->once())->method('setSuccess')->with(true)->willReturn(true);

        $this->assertTrue($currentUser->twoFactorSuccess());
        $this->assertTrue($currentUser->isLoggedIn());
    }

    public function testSessionAgeReturnsZeroWithoutTimestamp(): void
    {
        unset($_SESSION[CurrentUser::SESSION_ID_TIMESTAMP]);

        $this->assertSame(0.0, $this->currentUser->sessionAge());
    }

    public function testSessionAgeAndSessionIdTimeoutUseSessionTimestamp(): void
    {
        $_SERVER['REQUEST_TIME'] = 600;
        $reflection = new \ReflectionClass($this->currentUser);
        $property = $reflection->getProperty('sessionWrapper');
        /** @var \phpMyFAQ\Session\SessionWrapper $sessionWrapper */
        $sessionWrapper = $property->getValue($this->currentUser);
        $sessionWrapper->set(CurrentUser::SESSION_ID_TIMESTAMP, 480);

        $this->assertSame(2.0, $this->currentUser->sessionAge());
        $this->assertTrue($this->currentUser->sessionIdIsTimedOut());
    }

    /**
     * @throws Exception
     */
    public function testGetSessionInfoReturnsDataForLoggedInUser(): void
    {
        $this->currentUser->login('admin', 'password');

        $sessionInfo = $this->currentUser->getSessionInfo();

        $this->assertIsArray($sessionInfo);
        $this->assertArrayHasKey('session_id', $sessionInfo);
        $this->assertSame(session_id(), $sessionInfo['session_id']);
    }

    public function testSetSessionTimeoutStoresAbsoluteValue(): void
    {
        $this->currentUser->setSessionTimeout(-5);

        $reflection = new \ReflectionClass($this->currentUser);
        $property = $reflection->getProperty('sessionTimeout');
        $this->assertSame(5, $property->getValue($this->currentUser));
    }

    public function testEnableRememberMeSetsFlag(): void
    {
        $this->currentUser->enableRememberMe();

        $reflection = new \ReflectionClass($this->currentUser);
        $property = $reflection->getProperty('rememberMe');
        $this->assertTrue($property->getValue($this->currentUser));
    }

    /**
     * @throws Exception
     */
    public function testSetRememberMeAndSetSuccessPersistChanges(): void
    {
        $this->currentUser->login('admin', 'password');

        $this->assertTrue($this->currentUser->setRememberMe('remember-me-token'));
        $this->assertTrue($this->currentUser->setSuccess(true));
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testSetTokenDataPersistsOauthFields(): void
    {
        $this->currentUser->login('admin', 'password');

        $this->assertTrue($this->currentUser->setTokenData([
            'refresh_token' => 'refresh',
            'access_token' => 'access',
            'code_verifier' => 'verifier',
            'jwt' => ['sub' => 'admin'],
        ]));
    }
}
