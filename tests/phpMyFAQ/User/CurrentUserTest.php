<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CurrentUserTest extends TestCase
{
    private CurrentUser $currentUser;

    private Configuration $configuration;

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
        $this->configuration = new Configuration($dbHandle);

        $this->currentUser = new CurrentUser($this->configuration);
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
     * Recording failed logins used to be gated on security.loginWithEmailAddress,
     * which is false in this fixture and by default, leaving the lockout inert.
     *
     * @throws Exception
     */
    public function testRepeatedFailuresLockTheAccountWithoutEmailLogin(): void
    {
        $this->resetLockoutState();
        $this->assertFalse(
            (bool) $this->configuration->get('security.loginWithEmailAddress'),
        );

        try {
            for ($attempt = 0; $attempt <= 5; ++$attempt) {
                try {
                    $this->currentUser->login('admin', 'wrong-password');
                } catch (Exception) {
                    // Expected: the credentials are deliberately wrong.
                }
            }

            $caught = null;
            try {
                // Even the correct password must now be refused.
                $this->currentUser->login('admin', 'password');
            } catch (Exception $exception) {
                $caught = $exception;
            }

            $this->assertNotNull($caught);
            $this->assertSame(CurrentUser::ERROR_USER_TOO_MANY_FAILED_LOGINS, $caught->getMessage());
            $this->assertFalse($this->currentUser->isLoggedIn());
        } finally {
            // Restore the shared fixture for the other tests in this suite.
            $this->resetLockoutState();
        }
    }

    /**
     * Puts the shared fixture row back into a clean, unlocked state.
     *
     * Other tests in the suite perform failed logins, which now really do count, so
     * the lockout tests normalise the row on the way in as well as on the way out.
     */
    private function resetLockoutState(): void
    {
        $this->configuration->getDb()->query(sprintf(
            "UPDATE %sfaquser SET login_attempts = 0, success = 1, ip = '' WHERE user_id = 1",
            Database::getTablePrefix(),
        ));
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

    public function testTwoFactorFailuresLockTheAccount(): void
    {
        $this->resetLockoutState();

        try {
            $this->currentUser->getUserById(1);
            $this->assertFalse($this->currentUser->isTwoFactorLockedOut());

            for ($attempt = 0; $attempt <= 5; ++$attempt) {
                $this->currentUser->twoFactorFailure();
            }

            $this->assertTrue($this->currentUser->isTwoFactorLockedOut());
        } finally {
            $this->resetLockoutState();
        }
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

    public function testTwoFactorSuccessClearsTheLockout(): void
    {
        $this->resetLockoutState();

        try {
            $this->currentUser->getUserById(1);
            for ($attempt = 0; $attempt <= 5; ++$attempt) {
                $this->currentUser->twoFactorFailure();
            }

            $this->assertTrue($this->currentUser->isTwoFactorLockedOut());

            $this->currentUser->twoFactorSuccess();

            $this->assertFalse($this->currentUser->isTwoFactorLockedOut());
        } finally {
            $this->resetLockoutState();
        }
    }

    /**
     * The core of the bypass: the token step is reached only with a valid password,
     * so an attacker who holds it could re-authenticate to get a fresh session. The
     * failure count lives on the account and the lockout ignores the client IP, so
     * re-authenticating - even from another address - must not buy new token guesses.
     *
     * @throws Exception
     */
    public function testTwoFactorLockoutSurvivesPasswordReauthentication(): void
    {
        $this->resetLockoutState();
        $database = $this->configuration->getDb();
        $database->query(sprintf(
            'UPDATE %sfaquserdata SET twofactor_enabled = 1 WHERE user_id = 1',
            Database::getTablePrefix(),
        ));

        try {
            $this->currentUser->getUserById(1);
            for ($attempt = 0; $attempt <= 5; ++$attempt) {
                $this->currentUser->twoFactorFailure();
            }

            // Token failures consume the same per-account budget as password failures,
            // so even a correct-password re-authentication is refused while locked out.
            $caught = null;
            try {
                (new CurrentUser($this->configuration))->login('admin', 'password');
            } catch (Exception $exception) {
                $caught = $exception;
            }

            $this->assertNotNull($caught, 'Re-authentication must not be possible while locked out.');
            $this->assertSame(CurrentUser::ERROR_USER_TOO_MANY_FAILED_LOGINS, $caught->getMessage());

            // ... and the second factor stays locked out as well.
            $probe = new CurrentUser($this->configuration);
            $probe->getUserById(1);
            $this->assertTrue(
                $probe->isTwoFactorLockedOut(),
                'A password re-authentication attempt must not reset the 2FA failure count.',
            );
        } finally {
            $database->query(sprintf(
                'UPDATE %sfaquserdata SET twofactor_enabled = 0 WHERE user_id = 1',
                Database::getTablePrefix(),
            ));
            $this->resetLockoutState();
        }
    }

    /**
     * An unknown login leaves the user-ID at its -1 default, which is the anonymous
     * account. The failed-login bookkeeping must not be written to that row.
     *
     * @throws Exception
     */
    public function testFailedLoginForUnknownUserDoesNotTouchTheAnonymousAccount(): void
    {
        $database = $this->configuration->getDb();
        $readAttempts = static function () use ($database): int {
            $result = $database->query(sprintf(
                'SELECT login_attempts FROM %sfaquser WHERE user_id = -1',
                Database::getTablePrefix(),
            ));
            return (int) $database->fetchArray($result)['login_attempts'];
        };

        $before = $readAttempts();

        for ($attempt = 0; $attempt <= 5; ++$attempt) {
            try {
                $this->currentUser->login('no-such-user', 'wrong-password');
            } catch (Exception) {
                // Expected: the login does not exist.
            }
        }

        $this->assertSame($before, $readAttempts());
    }

    /**
     * A remember-me cookie is a password-equivalent credential: getFromCookie() turns it
     * into a fully authenticated session with no second factor. Issuing it once the password
     * step has passed but before 2FA has been verified would hand an attacker who only holds
     * the password a token they can replay to bypass 2FA entirely. login() must therefore not
     * write a remember_me token for a 2FA account.
     *
     * @throws Exception
     */
    public function testRememberMeTokenIsNotIssuedBeforeTwoFactorForATwoFactorAccount(): void
    {
        $this->resetLockoutState();
        $database = $this->configuration->getDb();
        $database->query(sprintf(
            'UPDATE %sfaquserdata SET twofactor_enabled = 1 WHERE user_id = 1',
            Database::getTablePrefix(),
        ));
        $this->resetRememberMeToken();

        try {
            $user = new CurrentUser($this->configuration);
            $user->enableRememberMe();

            $this->assertTrue($user->login('admin', 'password'));
            $this->assertFalse($user->isLoggedIn(), 'Password alone must not log a 2FA user in.');

            $this->assertSame(
                '',
                $this->readRememberMeToken(),
                'A 2FA account must not receive a remember-me token before the second factor is verified.',
            );
        } finally {
            $database->query(sprintf(
                'UPDATE %sfaquserdata SET twofactor_enabled = 0 WHERE user_id = 1',
                Database::getTablePrefix(),
            ));
            $this->resetRememberMeToken();
            $this->resetLockoutState();
        }
    }

    /**
     * The counterpart to the 2FA case: for an account without a second factor login() is
     * the point at which authentication is complete, so a requested remember-me cookie must
     * still be issued and its token persisted.
     *
     * @throws Exception
     */
    public function testRememberMeTokenIsIssuedDuringLoginWithoutTwoFactor(): void
    {
        $this->resetLockoutState();
        $this->resetRememberMeToken();

        try {
            $user = new CurrentUser($this->configuration);
            $user->enableRememberMe();

            $this->assertTrue($user->login('admin', 'password'));
            $this->assertTrue($user->isLoggedIn());

            $this->assertNotSame(
                '',
                $this->readRememberMeToken(),
                'A non-2FA account must receive a remember-me token once login completes.',
            );
        } finally {
            $this->resetRememberMeToken();
            $this->resetLockoutState();
        }
    }

    /**
     * Reads the persisted remember-me token for the shared fixture user, normalising the
     * absent state (SQL NULL) to an empty string.
     */
    private function readRememberMeToken(): string
    {
        $database = $this->configuration->getDb();
        $result = $database->query(sprintf(
            'SELECT remember_me FROM %sfaquser WHERE user_id = 1',
            Database::getTablePrefix(),
        ));

        return (string) ($database->fetchArray($result)['remember_me'] ?? '');
    }

    /**
     * Clears any remember-me token left on the shared fixture row.
     */
    private function resetRememberMeToken(): void
    {
        $this->configuration->getDb()->query(sprintf(
            'UPDATE %sfaquser SET remember_me = NULL WHERE user_id = 1',
            Database::getTablePrefix(),
        ));
    }
}
