<?php

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Tenant\QuotaExceededException;
use phpMyFAQ\User\UserData;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
class UserTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $database;
    private User $user;
    private UserData $userData;

    protected function setUp(): void
    {
        $this->configuration = $this->createStub(Configuration::class);
        $this->database = $this->createMock(Sqlite3::class);
        $this->userData = $this->createMock(UserData::class);

        $this->configuration->method('getDb')->willReturn($this->database);
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['security.permLevel', 'basic'],
            ]);

        $this->user = new User($this->configuration);
        $this->user->userdata = $this->userData;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('PMF_TENANT_QUOTA_MAX_USERS');
    }

    public function testIsEmailAddressReturnsTrueForValidEmail(): void
    {
        $reflection = new ReflectionClass($this->user);
        $method = $reflection->getMethod('isEmailAddress');

        $result = $method->invoke($this->user, 'test@example.com');
        $this->assertTrue($result);
    }

    public function testIsEmailAddressReturnsFalseForInvalidEmail(): void
    {
        $reflection = new ReflectionClass($this->user);
        $method = $reflection->getMethod('isEmailAddress');

        $result = $method->invoke($this->user, 'invalid-email');
        $this->assertFalse($result);
    }

    public function testCreateUserThrowsExceptionWhenEmailAlreadyExistsAsLogin(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(User::ERROR_USER_EMAIL_NOT_UNIQUE);

        // Mock that login is valid
        $this->database->method('escape')->willReturn('test@example.com');

        // Mock that getUserByLogin returns false (login doesn't exist as login)
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        // Mock that email exists in userdata
        $this->userData->method('emailExists')->willReturn(true);

        $this->user->createUser('test@example.com');
    }

    public function testCreateUserDoesNotCheckEmailWhenLoginIsNotEmail(): void
    {
        // Mock database operations to simulate login not existing
        $this->database->method('escape')->willReturn('username');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        // emailExists should never be called for non-email logins
        $this->userData->expects($this->never())->method('emailExists');

        // This will throw an exception because no auth container is set up,
        // but that's expected - we just want to verify emailExists wasn't called
        try {
            $this->user->createUser('username');
        } catch (Exception $e) {
            // Expected - ignore this exception as we're only testing the email check logic
        }
    }

    public function testCreateUserThrowsExceptionWhenLoginNotUnique(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(User::ERROR_USER_LOGIN_NOT_UNIQUE);

        // Mock that login exists
        $this->database->method('escape')->willReturn('existinguser');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database
            ->method('fetchArray')
            ->willReturn([
                'user_id' => 1,
                'login' => 'existinguser',
                'account_status' => 'active',
                'is_superadmin' => false,
                'auth_source' => 'local',
            ]);

        $this->user->createUser('existinguser');
    }

    public function testCreateUserThrowsWhenUserQuotaIsExceeded(): void
    {
        putenv('PMF_TENANT_QUOTA_MAX_USERS=0');

        $this->database->expects($this->exactly(2))->method('query')->willReturnOnConsecutiveCalls(true, true);
        $this->database->method('escape')->willReturnArgument(0);
        $this->database->method('numRows')->willReturn(0);
        $this->database->expects($this->once())->method('fetchArray')->with(true)->willReturn(['amount' => 0]);
        $this->database->expects($this->never())->method('nextId');

        $this->expectException(QuotaExceededException::class);
        $this->user->createUser('new_user');
    }

    public function testGetUserAuthSourceReturnsCurrentAuthSource(): void
    {
        $this->setPrivateProperty('authSource', 'ldap');

        $this->assertSame('ldap', $this->user->getUserAuthSource());
    }

    public function testGetUserByCookieReturnsFalseWhenRecordIsMissing(): void
    {
        $this->database->method('escape')->willReturn('cookie-token');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        $this->assertFalse($this->user->getUserByCookie('cookie-token'));
        $this->assertContains(User::ERROR_USER_INCORRECT_LOGIN, $this->user->errors);
    }

    public function testGetUserByCookieReturnsFalseForAnonymousUser(): void
    {
        $this->database->method('escape')->willReturn('cookie-token');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database
            ->method('fetchArray')
            ->willReturn([
                'user_id' => -1,
                'login' => 'anonymous',
                'account_status' => 'active',
            ]);

        $this->assertFalse($this->user->getUserByCookie('cookie-token'));
    }

    public function testGetUserByCookieLoadsUserDataForRegularUser(): void
    {
        $this->database->method('escape')->willReturn('cookie-token');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database
            ->method('fetchArray')
            ->willReturn([
                'user_id' => 42,
                'login' => 'cookie-user',
                'account_status' => 'active',
            ]);
        $this->userData->expects($this->once())->method('load')->with(42);

        $this->assertTrue($this->user->getUserByCookie('cookie-token'));
        $this->assertSame(42, $this->user->getUserId());
    }

    public function testGetUserIdAddsErrorWhenUnset(): void
    {
        $this->setPrivateProperty('userId', 0);

        $this->assertSame(-1, $this->user->getUserId());
        $this->assertContains(User::ERROR_USER_NO_USERID, $this->user->errors);
    }

    public function testCheckDisplayNameAndMailAddressDelegateToUserData(): void
    {
        $this->userData
            ->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnMap([
                ['display_name', 'Thorsten',             'Thorsten'],
                ['email',        'thorsten@example.com', 'thorsten@example.com'],
            ]);

        $this->assertTrue($this->user->checkDisplayName('Thorsten'));
        $this->assertTrue($this->user->checkMailAddress('thorsten@example.com'));
    }

    public function testCheckDisplayNameAndMailAddressReturnFalseForDifferentValues(): void
    {
        $this->userData
            ->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnMap([
                ['display_name', 'Thorsten',             'Another User'],
                ['email',        'thorsten@example.com', 'another@example.com'],
            ]);

        $this->assertFalse($this->user->checkDisplayName('Thorsten'));
        $this->assertFalse($this->user->checkMailAddress('thorsten@example.com'));
    }

    public function testCheckDisplayNameAndMailAddressCreateUserDataWhenUnset(): void
    {
        $this->user->userdata = null;
        $this->database->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $this->database->expects($this->exactly(2))->method('query')->willReturn(true);
        $this->database->expects($this->exactly(2))->method('numRows')->with(true)->willReturn(1);
        $this->database
            ->expects($this->exactly(2))
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls((object) ['display_name' => 'Thorsten'], (object) [
                'email' => 'thorsten@example.com',
            ]);

        $this->assertTrue($this->user->checkDisplayName('Thorsten'));
        $this->assertTrue($this->user->checkMailAddress('thorsten@example.com'));
    }

    public function testSearchUsersReturnsEmptyArrayWhenQueryFails(): void
    {
        $this->database->method('escape')->willReturn('thor%');
        $this->database->method('query')->willReturn(false);

        $this->assertSame([], $this->user->searchUsers('thor'));
    }

    public function testSearchUsersReturnsMappedRows(): void
    {
        $this->database->method('escape')->willReturn('thor%');
        $this->database->method('query')->willReturn(true);
        $this->database->method('fetchArray')->willReturnOnConsecutiveCalls(
            ['login' => 'thorsten', 'user_id' => 1, 'account_status' => 'active'],
            ['login' => 'thorben', 'user_id' => 2, 'account_status' => 'blocked'],
            null,
        );

        $users = $this->user->searchUsers('thor');

        $this->assertCount(2, $users);
        $this->assertSame('thorsten', $users[0]['login']);
    }

    public function testIsValidLoginRejectsTooShortAndAcceptsValidLogin(): void
    {
        $this->assertFalse($this->user->isValidLogin('a'));
        $this->assertContains(User::ERROR_USER_LOGIN_INVALID, $this->user->errors);
        $this->assertTrue($this->user->isValidLogin('valid_user'));
    }

    public function testGetUserByLoginReturnsFalseWithoutErrorWhenDisabled(): void
    {
        $this->database->method('escape')->willReturn('missing');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        $this->assertFalse($this->user->getUserByLogin('missing', false));
        $this->assertSame([], $this->user->errors);
    }

    public function testGetUserByLoginAddsErrorWhenMissingAndRaiseErrorEnabled(): void
    {
        $this->database->method('escape')->willReturn('missing');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        $this->assertFalse($this->user->getUserByLogin('missing'));
        $this->assertContains(User::ERROR_USER_INCORRECT_LOGIN, $this->user->errors);
    }

    public function testGetUserByLoginLoadsUserDataOnSuccess(): void
    {
        $this->database->method('escape')->willReturn('existing');
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database
            ->method('fetchArray')
            ->willReturn([
                'user_id' => 7,
                'login' => 'existing',
                'account_status' => 'active',
                'is_superadmin' => 1,
                'auth_source' => 'local',
            ]);
        $this->userData->expects($this->once())->method('load')->with(7);

        $this->assertTrue($this->user->getUserByLogin('existing'));
        $this->assertSame('existing', $this->user->getLogin());
        $this->assertTrue($this->user->isSuperAdmin());
    }

    public function testGetUserByLoginCreatesUserDataWhenUnset(): void
    {
        $this->user->userdata = null;
        $this->database->method('escape')->willReturn('existing');
        $this->database->expects($this->exactly(2))->method('query')->willReturn(true);
        $this->database->expects($this->exactly(2))->method('numRows')->with(true)->willReturn(1);
        $this->database
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->with(true)
            ->willReturnOnConsecutiveCalls([
                'user_id' => 21,
                'login' => 'existing',
                'account_status' => 'active',
                'is_superadmin' => 0,
                'auth_source' => 'local',
            ], [
                'last_modified' => '20240101000000',
                'display_name' => 'Existing User',
                'email' => 'existing@example.com',
                'is_visible' => 1,
                'twofactor_enabled' => 0,
                'secret' => '',
            ]);

        $this->assertTrue($this->user->getUserByLogin('existing'));
        $this->assertSame('existing', $this->user->getLogin());
    }

    public function testCreateUserSucceedsWithWritableAuthDriver(): void
    {
        $user = $this
            ->getMockBuilder(User::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['getUserByLogin'])
            ->getMock();

        $userData = $this->createMock(UserData::class);
        $user->userdata = $userData;
        $user->perm = $this->createMock(MediumPermission::class);

        $this->database->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $this->database->expects($this->once())->method('nextId')->with('faquser', 'user_id')->willReturn(20);
        $this->database->expects($this->once())->method('query')->willReturn(true);
        $userData->expects($this->once())->method('add')->with(20)->willReturn(true);

        $auth = $this->createMock(AuthDatabase::class);
        $auth->expects($this->once())->method('disableReadOnly')->willReturn(false);
        $auth->expects($this->once())->method('create')->with('new_user', $this->isString(), '')->willReturn(true);

        $user
            ->expects($this->exactly(2))
            ->method('getUserByLogin')
            ->with('new_user', false)
            ->willReturnOnConsecutiveCalls(false, true);

        $user->addAuth($auth, 'local');

        $this->assertTrue($user->createUser('new_user'));
    }

    public function testCreatePasswordGeneratesExpectedLengthAndAllowedCharacters(): void
    {
        $password = $this->user->createPassword(12, true);

        $this->assertSame(12, strlen($password));
        $this->assertMatchesRegularExpression('/^[A-Za-z2-9_]+$/', $password);
    }

    public function testCreatePasswordOmitsUnderscoreWhenDisabled(): void
    {
        $password = $this->user->createPassword(12, false);

        $this->assertSame(12, strlen($password));
        $this->assertMatchesRegularExpression('/^[A-Za-z2-9]+$/', $password);
        $this->assertStringNotContainsString('_', $password);
    }

    public function testDeleteUserFailsWhenUserIdIsMissing(): void
    {
        $this->setPrivateProperty('userId', 0);

        $this->assertFalse($this->user->deleteUser());
        $this->assertContains(User::ERROR_USER_NO_USERID, $this->user->errors);
    }

    public function testDeleteUserFailsWhenLoginIsMissing(): void
    {
        $this->setPrivateProperty('userId', 5);
        $this->setPrivateProperty('login', '');

        $this->assertFalse($this->user->deleteUser());
        $this->assertContains(User::ERROR_USER_LOGIN_INVALID, $this->user->errors);
    }

    public function testDeleteUserFailsForProtectedStatus(): void
    {
        $this->setPrivateProperty('userId', 5);
        $this->setPrivateProperty('login', 'protected-user');
        $this->setPrivateProperty('status', 'protected');

        $this->assertFalse($this->user->deleteUser());
        $this->assertStringContainsString(User::STATUS_USER_PROTECTED, $this->user->errors[0]);
    }

    public function testDeleteUserFailsWhenNoWritableAuthExists(): void
    {
        $this->setPrivateProperty('userId', 5);
        $this->setPrivateProperty('login', 'readonly-user');
        $this->setPrivateProperty('status', 'active');
        $this->setProtectedProperty('authContainer', []);

        $permission = $this->createMock(MediumPermission::class);
        $permission->expects($this->once())->method('refuseAllUserRights')->with(5);
        $this->user->perm = $permission;

        $this->database->method('query')->willReturn(true);
        $this->userData->method('delete')->willReturn(true);

        $auth = $this->createMock(AuthDatabase::class);
        $auth->expects($this->once())->method('disableReadOnly')->willReturn(true);
        $this->user->addAuth($auth, 'readonly');

        $this->assertFalse($this->user->deleteUser());
        $this->assertContains(User::ERROR_USER_NO_AUTH_WRITABLE, $this->user->errors);
    }

    public function testDeleteUserSucceedsWhenAuthDeleteReturnsTrue(): void
    {
        $this->setPrivateProperty('userId', 6);
        $this->setPrivateProperty('login', 'deletable');
        $this->setPrivateProperty('status', 'active');
        $this->setProtectedProperty('authContainer', []);

        $permission = $this->createMock(MediumPermission::class);
        $permission->expects($this->once())->method('refuseAllUserRights')->with(6);
        $this->user->perm = $permission;

        $this->database->method('query')->willReturn(true);
        $this->userData->expects($this->once())->method('delete')->with(6)->willReturn(true);

        $auth = $this->createMock(AuthDatabase::class);
        $auth->expects($this->once())->method('disableReadOnly')->willReturn(false);
        $auth->expects($this->once())->method('delete')->with('deletable')->willReturn(true);
        $this->user->addAuth($auth, 'writable');

        $this->assertTrue($this->user->deleteUser());
    }

    public function testErrorReturnsHtmlSeparatedMessagesAndClearsErrors(): void
    {
        $this->user->errors = ['first error', 'second error'];

        $message = $this->user->error();

        $this->assertSame("first error<br>\nsecond error<br>\n", $message);
        $this->assertSame([], $this->user->errors);
    }

    public function testGetAuthContainerReturnsAddedAuthDrivers(): void
    {
        $auth = $this->createMock(AuthDatabase::class);
        $this->user->addAuth($auth, 'api');

        $container = $this->user->getAuthContainer();

        $this->assertArrayHasKey('api', $container);
        $this->assertSame($auth, $container['api']);
    }

    public function testGetAllUsersHandlesFailureEmptyAndSuccessCases(): void
    {
        $this->database->method('query')->willReturnOnConsecutiveCalls(false, true, true);
        $this->database->method('numRows')->willReturnOnConsecutiveCalls(0, 2);
        $this->database->method('fetchArray')->willReturnOnConsecutiveCalls(['user_id' => 3], ['user_id' => 5], null);

        $this->assertSame([], $this->user->getAllUsers());
        $this->assertSame([], $this->user->getAllUsers());
        $this->assertSame([3, 5], $this->user->getAllUsers(false, false));
    }

    public function testGetUserByIdReturnsFalseWhenNotFoundOrMissingLoginData(): void
    {
        $this->setPrivateProperty('authData', [
            'authSource' => [
                'name' => 'db',
                'type' => 'local',
            ],
            'encType' => User::DEFAULT_ENCRYPTION_TYPE,
            'readOnly' => false,
        ]);
        $this->database->method('query')->willReturnOnConsecutiveCalls(true, true, true);
        $this->database->method('numRows')->willReturnOnConsecutiveCalls(0, 1, 0);
        $this->database
            ->method('fetchArray')
            ->willReturn([
                'user_id' => 8,
                'login' => 'db-user',
                'account_status' => 'active',
                'is_superadmin' => 0,
                'auth_source' => 'local',
            ]);
        $this->database->method('error')->willReturn('db error');

        $this->assertFalse($this->user->getUserById(99));
        $this->assertFalse($this->user->getUserById(8));
    }

    public function testGetUserByIdLoadsUserDataOnSuccess(): void
    {
        $this->setPrivateProperty('authData', [
            'authSource' => [
                'name' => 'local',
                'type' => 'local',
            ],
            'encType' => User::DEFAULT_ENCRYPTION_TYPE,
            'readOnly' => false,
        ]);
        $this->database->expects($this->once())->method('query')->willReturn(true);
        $this->database->expects($this->once())->method('numRows')->with(true)->willReturn(1);
        $this->database
            ->expects($this->once())
            ->method('fetchArray')
            ->with(true)
            ->willReturn([
                'user_id' => 18,
                'login' => 'found-user',
                'account_status' => 'active',
                'is_superadmin' => 0,
                'auth_source' => 'local',
            ]);
        $this->userData->expects($this->once())->method('load')->with(18);

        $this->assertTrue($this->user->getUserById(18));
        $this->assertSame('found-user', $this->user->getLogin());
    }

    public function testGetUserByCookieCreatesUserDataWhenUnset(): void
    {
        $this->user->userdata = null;
        $this->database->method('escape')->willReturn('cookie-token');
        $this->database->expects($this->exactly(2))->method('query')->willReturn(true);
        $this->database->expects($this->exactly(2))->method('numRows')->with(true)->willReturn(1);
        $this->database
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->with(true)
            ->willReturnOnConsecutiveCalls([
                'user_id' => 42,
                'login' => 'cookie-user',
                'account_status' => 'active',
            ], [
                'last_modified' => '20240101000000',
                'display_name' => 'Cookie User',
                'email' => 'cookie@example.com',
                'is_visible' => 1,
                'twofactor_enabled' => 0,
                'secret' => '',
            ]);

        $this->assertTrue($this->user->getUserByCookie('cookie-token'));
        $this->assertSame(42, $this->user->getUserId());
    }

    public function testGetUserDataSetUserDataAndEmailHelpersDelegateToUserData(): void
    {
        $this->setPrivateProperty('userId', 11);
        $this->userData->expects($this->once())->method('get')->with('display_name')->willReturn('Thorsten');
        $this->userData->expects($this->once())->method('load')->with(11);
        $this->userData->expects($this->once())->method('set')->with(['display_name'], ['Thorsten'])->willReturn(true);
        $this->userData
            ->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls(['user_id' => 11, 'is_visible' => true], ['user_id' => 12]);

        $this->assertSame('Thorsten', $this->user->getUserData('display_name'));
        $this->assertTrue($this->user->setUserData(['display_name' => 'Thorsten']));
        $this->assertSame(11, $this->user->getUserIdByEmail('thorsten@example.com'));
        $this->assertTrue($this->user->getUserVisibilityByEmail('anonymous@example.com'));
    }

    public function testGetUserDataCreatesUserDataWhenUnset(): void
    {
        $this->user->userdata = null;
        $this->setPrivateProperty('userId', 7);

        $this->database->expects($this->once())->method('query')->willReturn(true);
        $this->database->expects($this->once())->method('numRows')->with(true)->willReturn(1);
        $this->database
            ->expects($this->once())
            ->method('fetchArray')
            ->with(true)
            ->willReturn([
                'display_name' => 'Loaded User',
            ]);

        $this->assertSame('Loaded User', $this->user->getUserData('display_name'));
    }

    public function testSetUserDataCreatesUserDataWhenUnset(): void
    {
        $this->user->userdata = null;
        $this->setPrivateProperty('userId', 13);

        $this->database->expects($this->exactly(2))->method('query')->willReturn(true);
        $this->database->expects($this->once())->method('numRows')->with(true)->willReturn(1);
        $this->database
            ->expects($this->once())
            ->method('fetchArray')
            ->with(true)
            ->willReturn([
                'last_modified' => '20240101000000',
                'display_name' => 'Existing User',
                'email' => 'existing@example.com',
                'is_visible' => 1,
                'twofactor_enabled' => 0,
                'secret' => '',
            ]);
        $this->database->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $this->assertTrue($this->user->setUserData(['display_name' => 'Updated User']));
    }

    public function testGetUserIdByEmailCreatesUserDataWhenUnset(): void
    {
        $this->user->userdata = null;
        $this->database->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $this->database->expects($this->once())->method('query')->willReturn(true);
        $this->database->expects($this->once())->method('numRows')->with(true)->willReturn(1);
        $this->database
            ->expects($this->once())
            ->method('fetchArray')
            ->with(true)
            ->willReturn([
                'user_id' => 77,
                'email' => 'lookup@example.com',
                'is_visible' => 1,
            ]);

        $this->assertSame(77, $this->user->getUserIdByEmail('lookup@example.com'));
    }

    public function testGetUserVisibilityByEmailReturnsFalseForInvisibleUser(): void
    {
        $this->userData
            ->expects($this->once())
            ->method('fetchAll')
            ->with('email', 'hidden@example.com')
            ->willReturn(['user_id' => 5, 'is_visible' => false]);

        $this->assertFalse($this->user->getUserVisibilityByEmail('hidden@example.com'));
    }

    public function testGetUserVisibilityByEmailCreatesUserDataWhenUnset(): void
    {
        $this->user->userdata = null;
        $this->database->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $this->database->expects($this->once())->method('query')->willReturn(true);
        $this->database->expects($this->once())->method('numRows')->with(true)->willReturn(1);
        $this->database
            ->expects($this->once())
            ->method('fetchArray')
            ->with(true)
            ->willReturn([
                'user_id' => 17,
                'email' => 'visible@example.com',
                'is_visible' => 1,
                'display_name' => 'Visible User',
                'last_modified' => '20240101000000',
                'twofactor_enabled' => 0,
                'secret' => '',
            ]);

        $this->assertTrue($this->user->getUserVisibilityByEmail('visible@example.com'));
    }

    public function testActivateUserReturnsFalseForNonBlockedStatus(): void
    {
        $user = $this
            ->getMockBuilder(User::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['getStatus'])
            ->getMock();

        $user->method('getStatus')->willReturn('active');

        $this->assertFalse($user->activateUser());
    }

    public function testActivateUserChangesStatusWhenMailWasSent(): void
    {
        $user = $this
            ->getMockBuilder(User::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods([
                'getStatus',
                'createPassword',
                'changePassword',
                'getUserData',
                'getLogin',
                'mailUser',
                'setStatus',
            ])
            ->getMock();

        $user->method('getStatus')->willReturn('blocked');
        $user->method('createPassword')->willReturn('Abcd2345');
        $user->expects($this->once())->method('changePassword')->with('Abcd2345')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('display_name')->willReturn('Blocked User');
        $user->method('getLogin')->willReturn('blocked-user');
        $user->expects($this->once())->method('mailUser')->willReturn(1);
        $user->expects($this->once())->method('setStatus')->with('active')->willReturn(true);

        $this->assertTrue($user->activateUser());
    }

    public function testActivateUserReturnsTrueWhenMailSendingFails(): void
    {
        $user = $this
            ->getMockBuilder(User::class)
            ->setConstructorArgs([$this->configuration])
            ->onlyMethods(['getStatus', 'createPassword', 'changePassword', 'getUserData', 'getLogin', 'mailUser'])
            ->getMock();

        $user->method('getStatus')->willReturn('blocked');
        $user->method('createPassword')->willReturn('Abcd2345');
        $user->expects($this->once())->method('changePassword')->with('Abcd2345')->willReturn(true);
        $user->method('getUserData')->willReturn('Blocked User');
        $user->method('getLogin')->willReturn('blocked-user');
        $user->expects($this->once())->method('mailUser')->willReturn(0);

        $this->assertTrue($user->activateUser());
    }

    public function testGetStatusReturnsEmptyForUnsetStatus(): void
    {
        $this->setPrivateProperty('status', '');

        $this->assertSame('', $this->user->getStatus());
    }

    public function testGetStatusReturnsAssignedStatus(): void
    {
        $this->setPrivateProperty('status', 'active');

        $this->assertSame('active', $this->user->getStatus());
    }

    public function testSetStatusRejectsInvalidAndPersistsValidStatus(): void
    {
        $this->setPrivateProperty('userId', 13);
        $this->database->method('escape')->willReturnArgument(0);
        $this->database->method('query')->willReturn(true);

        $this->assertFalse($this->user->setStatus('unknown'));
        $this->assertContains(User::ERROR_USER_INVALID_STATUS, $this->user->errors);
        $this->assertTrue($this->user->setStatus('active'));
        $this->assertSame('active', $this->user->getStatus());
    }

    public function testSetAuthSourceAndChangePassword(): void
    {
        $this->setPrivateProperty('userId', 14);
        $this->setPrivateProperty('login', 'api-user');
        $this->setProtectedProperty('authContainer', []);
        $this->database->method('escape')->willReturnArgument(0);
        $this->database->method('query')->willReturn(true);

        $readonly = $this->createMock(AuthDatabase::class);
        $readonly->expects($this->once())->method('disableReadOnly')->willReturn(true);
        $writable = $this->createMock(AuthDatabase::class);
        $writable->expects($this->once())->method('disableReadOnly')->willReturn(false);
        $writable->expects($this->once())->method('update')->with('api-user', 'Secret123')->willReturn(true);
        $this->user->addAuth($readonly, 'readonly');
        $this->user->addAuth($writable, 'writable');

        $this->assertTrue($this->user->setAuthSource('ldap'));
        $this->assertTrue($this->user->changePassword('Secret123'));
    }

    public function testChangePasswordReturnsFalseWhenAllDriversSkipOrFail(): void
    {
        $this->setPrivateProperty('login', 'api-user');
        $this->setProtectedProperty('authContainer', []);

        $readonly = $this->createMock(AuthDatabase::class);
        $readonly->expects($this->once())->method('disableReadOnly')->willReturn(true);
        $readonly->expects($this->never())->method('update');

        $failing = $this->createMock(AuthDatabase::class);
        $failing->expects($this->once())->method('disableReadOnly')->willReturn(false);
        $failing->expects($this->once())->method('update')->with('api-user', 'Secret123')->willReturn(false);

        $this->user->addAuth($readonly, 'readonly');
        $this->user->addAuth($failing, 'failing');

        $this->assertFalse($this->user->changePassword('Secret123'));
    }

    public function testSetSuperAdminTerminateSessionAndWebAuthnKeys(): void
    {
        $this->setPrivateProperty('userId', 15);
        $this->database->method('escape')->willReturnArgument(0);
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(1);
        $this->database->method('fetchArray')->willReturn(['webauthnkeys' => '{"id":"key-1"}']);

        $this->assertTrue($this->user->setSuperAdmin(true));
        $this->assertTrue($this->user->isSuperAdmin());
        $this->assertTrue($this->user->terminateSessionId());
        $this->assertTrue($this->user->setWebAuthnKeys('{"id":"key-1"}'));
        $this->assertSame('{"id":"key-1"}', $this->user->getWebAuthnKeys());
    }

    public function testGetWebAuthnKeysReturnsEmptyStringWhenMissing(): void
    {
        $this->setPrivateProperty('userId', 16);
        $this->database->method('query')->willReturn(true);
        $this->database->method('numRows')->willReturn(0);

        $this->assertSame('', $this->user->getWebAuthnKeys());
    }

    public function testGetSuperAdminIdsAndExtractUserFromResult(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $database = $this->createMock(Sqlite3::class);
        $configuration->method('getDb')->willReturn($database);

        $database->method('query')->willReturn(true);
        $database->method('fetchObject')->willReturnOnConsecutiveCalls(
            (object) ['user_id' => 2],
            (object) ['user_id' => 5],
            null,
        );

        $this->assertSame([2, 5], User::getSuperAdminIds($configuration));
        $this->database
            ->method('fetchArray')
            ->willReturn([
                'user_id' => 99,
                'login' => 'extract-user',
                'account_status' => 'blocked',
                'is_superadmin' => 1,
                'auth_source' => 'sso',
            ]);

        $this->user->extractUserFromResult(true);

        $this->assertSame(99, $this->user->getUserId());
        $this->assertSame('extract-user', $this->user->getLogin());
        $this->assertSame('blocked', $this->user->getStatus());
        $this->assertSame('sso', $this->user->getUserAuthSource());
        $this->assertTrue($this->user->isSuperAdmin());
    }

    public function testGetSuperAdminIdsReturnsEmptyArrayWhenQueryFails(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $database = $this->createMock(Sqlite3::class);
        $configuration->method('getDb')->willReturn($database);
        $database->expects($this->once())->method('query')->willReturn(false);

        $this->assertSame([], User::getSuperAdminIds($configuration));
    }

    private function setPrivateProperty(string $name, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty(User::class, $name);
        $reflectionProperty->setValue($this->user, $value);
    }

    private function setProtectedProperty(string $name, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty(User::class, $name);
        $reflectionProperty->setValue($this->user, $value);
    }
}
