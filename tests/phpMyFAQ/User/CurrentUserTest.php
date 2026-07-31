<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use Symfony\Component\HttpFoundation\Request;
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
        } finally {
            // Restore the shared fixture for the other tests in this suite.
            $this->currentUser->setSuccess(true);
        }

        $this->assertNotNull($caught);
        $this->assertSame(CurrentUser::ERROR_USER_TOO_MANY_FAILED_LOGINS, $caught->getMessage());
        $this->assertFalse($this->currentUser->isLoggedIn());
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
     * An IP that is guaranteed to differ from the one this request appears to come
     * from; other tests in the suite leave $_SERVER['REMOTE_ADDR'] set to varying
     * values, so it cannot be hard-coded.
     */
    private function foreignIp(): string
    {
        return Request::createFromGlobals()->getClientIp() === '198.51.100.7'
            ? '198.51.100.8'
            : '198.51.100.7';
    }

    /**
     * Pins the existing scoping of the password lockout: it is keyed to the client
     * IP, and an unavailable IP must compare as an empty string rather than as
     * "any IP", which would lock every account out at once.
     *
     * @throws Exception
     */
    public function testPasswordLockoutIsScopedToTheClientIp(): void
    {
        $originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '198.51.100.20';
        $this->resetLockoutState();

        try {
            for ($attempt = 0; $attempt <= 5; ++$attempt) {
                try {
                    (new CurrentUser($this->configuration))->login('admin', 'wrong-password');
                } catch (Exception) {
                    // Expected: the credentials are deliberately wrong.
                }
            }

            $caught = null;
            try {
                (new CurrentUser($this->configuration))->login('admin', 'password');
            } catch (Exception $exception) {
                $caught = $exception;
            }

            $this->assertNotNull($caught, 'The originating IP must be locked out.');
            $this->assertSame(CurrentUser::ERROR_USER_TOO_MANY_FAILED_LOGINS, $caught->getMessage());

            $_SERVER['REMOTE_ADDR'] = '198.51.100.21';
            $this->assertTrue(
                (new CurrentUser($this->configuration))->login('admin', 'password'),
                'A different IP must not inherit the lockout.',
            );
        } finally {
            if ($originalRemoteAddr === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $originalRemoteAddr;
            }

            $this->resetLockoutState();
        }
    }

    /**
     * A client IP that cannot be determined must compare as an empty string. Passing
     * the raw null through would drop the IP condition altogether and lock out every
     * account that has any recorded failures.
     *
     * @throws Exception
     */
    public function testPasswordLockoutDoesNotApplyWhenTheClientIpIsUnknown(): void
    {
        $originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '198.51.100.20';
        $this->resetLockoutState();

        try {
            for ($attempt = 0; $attempt <= 5; ++$attempt) {
                try {
                    (new CurrentUser($this->configuration))->login('admin', 'wrong-password');
                } catch (Exception) {
                    // Expected: the credentials are deliberately wrong.
                }
            }

            unset($_SERVER['REMOTE_ADDR']);

            $this->assertTrue(
                (new CurrentUser($this->configuration))->login('admin', 'password'),
                'An unknown client IP must not match the lockout recorded for another IP.',
            );
        } finally {
            if ($originalRemoteAddr === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $originalRemoteAddr;
            }

            $this->resetLockoutState();
        }
    }

    /**
     * @throws Exception
     */
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
     * lockout must survive that, and must not be escapable by changing IP either.
     *
     * @throws Exception
     */
    public function testTwoFactorLockoutSurvivesPasswordReauthenticationFromAnotherIp(): void
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

            // The password lockout is keyed to the client IP, so pretend the attacker
            // moved to a new one; the password step then lets them through again.
            $database->query(sprintf(
                "UPDATE %sfaquser SET ip = '%s' WHERE user_id = 1",
                Database::getTablePrefix(),
                $this->foreignIp(),
            ));

            $reauthenticated = new CurrentUser($this->configuration);
            $this->assertTrue($reauthenticated->login('admin', 'password'));
            $this->assertFalse($reauthenticated->isLoggedIn(), 'Password alone must not log a 2FA user in.');

            // ... but the second factor is still locked out.
            $probe = new CurrentUser($this->configuration);
            $probe->getUserById(1);
            $this->assertTrue(
                $probe->isTwoFactorLockedOut(),
                'A successful password authentication must not reset the 2FA failure count.',
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
}
