<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\TestCase;

class CurrentUserTraitsTest extends TestCase
{
    private string $databaseFile;
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('');
        session_start();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-user-');
        self::assertNotFalse($this->databaseFile);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $this->databaseFile));

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        Database::setTablePrefix('');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('security.ipCheck', false);

        $_COOKIE = [];
        $_SERVER['REQUEST_TIME'] = 1_700_000_000;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        unset($_SERVER['REQUEST_TIME'], $_SERVER['REMOTE_ADDR']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        Database::setTablePrefix('');

        if (isset($this->databaseFile) && file_exists($this->databaseFile)) {
            unlink($this->databaseFile);
        }

        parent::tearDown();
    }

    public function testCurrentUserClassUsesNewTraits(): void
    {
        $usedTraits = class_uses(CurrentUser::class);

        $this->assertContains(CurrentUserSessionLookupTrait::class, $usedTraits);
        $this->assertContains(CurrentUserAccountStateTrait::class, $usedTraits);
    }

    public function testGetCurrentUserGroupIdWithNullUserReturnsDefaultValues(): void
    {
        $result = CurrentUser::getCurrentUserGroupId(null);

        $this->assertSame([-1, [-1]], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCurrentUserGroupIdReturnsFallbackForEmptyMediumPermissionGroups(): void
    {
        $user = new CurrentUser($this->configuration);
        $user->getUserById(1);

        $permission = $this
            ->getMockBuilder(MediumPermission::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserGroups'])
            ->getMock();
        $permission->expects($this->once())->method('getUserGroups')->with(1)->willReturn([]);
        $user->perm = $permission;

        $this->assertSame([1, [-1]], CurrentUser::getCurrentUserGroupId($user));
    }

    /**
     * @throws PluginException
     * @throws Exception
     * @throws \Exception
     */
    public function testGetCurrentUserReturnsCurrentUserInstance(): void
    {
        $result = CurrentUser::getCurrentUser($this->configuration);

        $this->assertInstanceOf(CurrentUser::class, $result);
    }

    /**
     * @throws \Exception
     */
    public function testGetCurrentUserReturnsLoggedInUserFromSession(): void
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->login('admin', 'password');

        $result = CurrentUser::getCurrentUser($this->configuration);

        $this->assertTrue($result->isLoggedIn());
        $this->assertSame(1, $result->getUserId());
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testGetFromCookieReturnsCurrentUser(): void
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->login('admin', 'password');
        $currentUser->setRememberMe('cookie-token');
        $currentUser->deleteFromSession();

        $_COOKIE[UserSession::COOKIE_NAME_REMEMBER_ME] = 'cookie-token';

        $result = CurrentUser::getFromCookie($this->configuration);

        $this->assertInstanceOf(CurrentUser::class, $result);
        $this->assertTrue($result->isLoggedIn());
        $this->assertSame(1, $result->getUserId());
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testGetFromSessionReturnsNullWhenSessionIdDoesNotMatch(): void
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->login('admin', 'password');
        $this->configuration->getDb()->query("UPDATE faquser SET session_id = 'stale-session' WHERE user_id = 1");

        $this->assertNull(CurrentUser::getFromSession($this->configuration));
    }

    /**
     * @throws Exception
     */
    public function testIsBlockedReturnsTrueForBlockedUser(): void
    {
        $this->configuration->getDb()->query("UPDATE faquser SET account_status = 'blocked' WHERE user_id = 1");

        $user = new CurrentUser($this->configuration);
        $user->getUserById(1, true);

        $this->assertTrue($user->isBlocked());
    }
}
