<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
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
