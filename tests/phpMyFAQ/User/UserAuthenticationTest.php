<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Http\RateLimiter;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
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
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);

        $this->currentUser = $this->createStub(CurrentUser::class);
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
    public function testAuthenticateStopsWhenIpFailureBudgetIsExhausted(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.99';
        $rateLimiter = new RateLimiter(
            storage: new \Symfony\Component\RateLimiter\Storage\InMemoryStorage(),
        );

        $failingUser = $this->createMock(CurrentUser::class);
        $failingUser->method('login')->willReturn(false);
        $userAuth = new UserAuthentication($this->configuration, $failingUser, $rateLimiter);

        for ($i = 0; $i < UserAuthentication::MAX_FAILED_LOGINS_PER_IP; ++$i) {
            try {
                $userAuth->authenticate('victim-' . $i, 'guess');
            } catch (UserException) {
                // expected: wrong password
            }
        }

        // The next attempt from this client must be rejected before any password check runs.
        $untouchedUser = $this->createMock(CurrentUser::class);
        $untouchedUser->expects($this->never())->method('login');
        $userAuth = new UserAuthentication($this->configuration, $untouchedUser, $rateLimiter);

        try {
            $this->expectException(UserException::class);
            $userAuth->authenticate('victim-final', 'guess');
        } finally {
            unset($_SERVER['REMOTE_ADDR']);
        }
    }

    public function testSuccessfulLoginsDoNotConsumeTheIpFailureBudget(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.99';
        $rateLimiter = new RateLimiter(
            storage: new \Symfony\Component\RateLimiter\Storage\InMemoryStorage(),
        );

        $validUser = $this->createMock(CurrentUser::class);
        $validUser->method('login')->willReturn(true);
        $validUser->method('getUserData')->willReturn(0);
        $validUser->method('getStatus')->willReturn('active');
        $userAuth = new UserAuthentication($this->configuration, $validUser, $rateLimiter);

        try {
            for ($i = 0; $i < UserAuthentication::MAX_FAILED_LOGINS_PER_IP + 5; ++$i) {
                $this->assertSame($validUser, $userAuth->authenticate('user', 'correct-password'));
            }
        } finally {
            unset($_SERVER['REMOTE_ADDR']);
        }
    }

    public function testAuthenticateFails(): void
    {
        $this->currentUser->method('login')->willReturn(false);

        $this->expectException(UserException::class);
        $this->userAuth->authenticate('username', 'password');
    }

    /**
     * @throws UserException
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testAuthenticateDoesNotInitializeLdapWhenSupportIsStringFalse(): void
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $item): mixed {
                return match ($item) {
                    'ldap.ldapSupport' => 'false',
                    'security.ssoSupport' => false,
                    default => null,
                };
            });
        $configuration->method('getLdapServer')->willReturn([['ldap_server' => 'ldap.example.com']]);
        $configuration->method('getLdapConfig')->willReturn(['ldap_mapping' => []]);
        $configuration->method('getLogger')->willReturn(new \Monolog\Logger('test'));

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->never())->method('addAuth')->with($this->anything(), 'ldap');
        $currentUser->method('login')->willReturn(false);

        $userAuth = new UserAuthentication($configuration, $currentUser);

        $this->expectException(UserException::class);
        $userAuth->authenticate('username', 'password');
    }
}
