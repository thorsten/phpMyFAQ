<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\User;
use PHPUnit\Framework\TestCase;

/**
 * Brute-force protection: failed logins are always counted, and the resulting
 * account lockout cannot be bypassed by rotating the client IP.
 */
class CurrentUserLockoutTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private CurrentUser $currentUser;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        parent::setUp();

        Strings::init();

        $dbHandle = $this->connectToTestDatabaseCopy(new Sqlite3());
        Database::setTablePrefix('');

        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('security.loginWithEmailAddress', false);

        $this->currentUser = new CurrentUser($this->configuration);
    }

    protected function tearDown(): void
    {
        session_destroy();

        parent::tearDown();
    }

    private function adminLoginAttempts(): int
    {
        $result = $this->configuration->getDb()->query(
            "SELECT login_attempts FROM faquser WHERE login = 'admin'",
        );

        return (int) $this->configuration->getDb()->fetchObject($result)->login_attempts;
    }

    private function failLogin(): void
    {
        try {
            $this->currentUser->login('admin', 'definitely-wrong-password');
            $this->fail('Login with a wrong password must not succeed.');
        } catch (Exception) {
            // expected
        }
    }

    public function testFailedLoginIsCountedWithoutEmailLoginEnabled(): void
    {
        $this->failLogin();

        $this->assertSame(1, $this->adminLoginAttempts());
    }

    public function testAccountIsLockedAfterTooManyFailedAttempts(): void
    {
        for ($i = 0; $i < 6; ++$i) {
            $this->failLogin();
        }

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(User::ERROR_USER_TOO_MANY_FAILED_LOGINS);

        $this->currentUser->login('admin', 'password');
    }

    public function testLockoutCannotBeBypassedByRotatingTheClientIp(): void
    {
        // Simulate an attack recorded from a different IP than the current client.
        $this->configuration->getDb()->query(sprintf(
            "UPDATE faquser SET login_attempts = 6, success = 0, session_timestamp = '%d', ip = '198.51.100.23' "
            . "WHERE login = 'admin'",
            time(),
        ));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(User::ERROR_USER_TOO_MANY_FAILED_LOGINS);

        $this->currentUser->login('admin', 'password');
    }

    public function testSuccessfulLoginResetsTheFailureCounter(): void
    {
        $this->failLogin();
        $this->failLogin();

        $this->assertTrue($this->currentUser->login('admin', 'password'));
        $this->assertSame(0, $this->adminLoginAttempts());
    }
}
