<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(GroupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class GroupControllerTest extends TestCase
{
    private const int TEST_GROUP_ID = 4242;

    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;
    private string $sessionId = '';

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        Token::resetInstanceForTests();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-group-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->configuration->set('security.permLevel', 'medium');

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->sessionId = 'pmf-admin-group-' . bin2hex(random_bytes(8));
        session_id($this->sessionId);
        session_start();
        $_SERVER['REQUEST_TIME'] = time();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        Token::resetInstanceForTests();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    private function createAuthenticatedContainer(?Session $session = null): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 1
                && in_array(
                    $right,
                    [
                        PermissionType::USER_ADD->value,
                        PermissionType::USER_EDIT->value,
                        PermissionType::USER_DELETE->value,
                        PermissionType::GROUP_EDIT->value,
                    ],
                    true,
                ),
            );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);

        $session ??= new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        return $container;
    }

    private function seedCurrentUserSession(): void
    {
        $_SESSION[CurrentUser::SESSION_CURRENT_USER] = 1;
        $_SESSION[CurrentUser::SESSION_ID_TIMESTAMP] = (int) $_SERVER['REQUEST_TIME'];

        $this->dbHandle->query(sprintf(
            "UPDATE faquser SET session_id = '%s', session_timestamp = %d, ip = '127.0.0.1' WHERE user_id = 1",
            $this->sessionId,
            (int) $_SERVER['REQUEST_TIME'],
        ));
    }

    private function seedGroupFixtures(): void
    {
        $this->dbHandle->query(
            "INSERT OR REPLACE INTO faquser
                (user_id, login, session_id, session_timestamp, ip, account_status, last_login, auth_source, member_since, remember_me, success, is_superadmin, login_attempts, refresh_token, access_token, code_verifier, jwt, webauthnkeys)
             VALUES
                (2, 'testUser', NULL, NULL, NULL, 'active', NULL, 'local', NULL, NULL, 1, 0, 0, NULL, NULL, NULL, NULL, NULL)",
        );
        $this->dbHandle->query("INSERT OR REPLACE INTO faquserdata (user_id, last_modified, display_name, email, is_visible, twofactor_enabled, secret)
             VALUES (2, '20260302120000', 'testUser', '', 1, 0, '')");
        $this->dbHandle->query(sprintf(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (%d, 'Editors', 'Editorial team', 0)",
            self::TEST_GROUP_ID,
        ));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faquser_group (user_id, group_id) VALUES (1, %1$d), (2, %1$d)',
            self::TEST_GROUP_ID,
        ));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (%1$d, 1), (%1$d, 2)',
            self::TEST_GROUP_ID,
        ));
    }

    private function setCsrfCookie(string $page, string $token): void
    {
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;
    }

    private function removeCsrfCookie(string $page): void
    {
        unset($_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)]);
    }

    private function createSuperAdminContainer(?Session $session = null): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('isSuperAdmin')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);

        $session ??= new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        return $container;
    }

    /**
     * @throws \Exception
     */
    public function testListGroupsRequiresGroupPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listGroups();
    }

    /**
     * @throws \Exception
     */
    public function testListUsersRequiresGroupPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listUsers();
    }

    /**
     * @throws \Exception
     */
    public function testGroupDataRequiresGroupPermission(): void
    {
        $request = new Request([], [], ['groupId' => 1]);
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->groupData($request);
    }

    /**
     * @throws \Exception
     */
    public function testListMembersRequiresGroupPermission(): void
    {
        $request = new Request([], [], ['groupId' => 1]);
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listMembers($request);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsRequiresGroupPermission(): void
    {
        $request = new Request([], [], ['groupId' => 1]);
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listPermissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testListGroupsReturnsSeededGroupsForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listGroups();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertContains(['group_id' => self::TEST_GROUP_ID, 'name' => 'Editors'], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testListUsersReturnsAvailableUsersForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listUsers();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($payload);
        self::assertContains(['user_id' => 1, 'login' => 'admin'], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testGroupDataReturnsSeededGroupForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->groupData(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::TEST_GROUP_ID, $payload['group_id']);
        self::assertSame('Editors', $payload['name']);
        self::assertSame('Editorial team', $payload['description']);
    }

    /**
     * @throws \Exception
     */
    public function testListMembersReturnsGroupMembersForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listMembers(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertContains(1, array_column($payload, 'user_id'));
        self::assertContains(2, array_column($payload, 'user_id'));
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsReturnsGroupRightsForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listPermissions(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([1, 2], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateGroupRequiresGroupEditPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->updateGroup(new Request(content: '{}'));
    }

    /**
     * @throws \Exception
     */
    public function testUpdateGroupRejectsInvalidCsrfToken(): void
    {
        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer());

        $response = $controller->updateGroup(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'name' => 'Editors',
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Invalid CSRF token.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateGroupRejectsEmptyName(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group');
        $this->setCsrfCookie('update-group', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer($session));

        $response = $controller->updateGroup(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'name' => '   ',
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
        $this->removeCsrfCookie('update-group');
    }

    /**
     * @throws \Exception
     */
    public function testUpdateGroupUpdatesGroupData(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group');
        $this->setCsrfCookie('update-group', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer($session));

        $response = $controller->updateGroup(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'name' => 'Editors renamed',
            'description' => 'Updated description',
            'autoJoin' => true,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);

        $dataResponse = $controller->groupData(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $dataPayload = json_decode((string) $dataResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Editors renamed', $dataPayload['name']);
        self::assertSame('Updated description', $dataPayload['description']);
        self::assertSame(1, (int) $dataPayload['auto_join']);
        $this->removeCsrfCookie('update-group');
    }

    /**
     * @throws \Exception
     */
    public function testUpdateMembersRequiresGroupEditPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->updateMembers(new Request(content: '{}'));
    }

    /**
     * @throws \Exception
     */
    public function testUpdateMembersRejectsInvalidCsrfToken(): void
    {
        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer());

        $response = $controller->updateMembers(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'memberIds' => [1],
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Invalid CSRF token.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateMembersFailsClosedForNonSuperAdminWithoutMediumPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group-members');
        $this->setCsrfCookie('update-group-members', $csrfToken);

        // createAuthenticatedContainer(): isSuperAdmin() is an unconfigured stub → false,
        // and perm is a PermissionInterface stub, NOT MediumPermission → must fail closed.
        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->updateMembers(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'memberIds' => [1],
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot manage group membership without group permission support.', $payload['error']);
        $this->removeCsrfCookie('update-group-members');
    }

    /**
     * @throws \Exception
     */
    public function testUpdateMembersReplacesMembersForSuperAdmin(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group-members');
        $this->setCsrfCookie('update-group-members', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer($session));

        // Fixtures seed members 1 and 2; replace with just member 2.
        $response = $controller->updateMembers(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'memberIds' => [2],
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);

        $membersResponse = $controller->listMembers(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $membersPayload = json_decode((string) $membersResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $memberIds = array_column($membersPayload, 'user_id');
        self::assertContains(2, $memberIds);
        self::assertNotContains(1, $memberIds);
        $this->removeCsrfCookie('update-group-members');
    }

    /**
     * @throws \Exception
     */
    public function testUpdatePermissionsRequiresGroupEditPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->updatePermissions(new Request(content: '{}'));
    }

    /**
     * @throws \Exception
     */
    public function testUpdatePermissionsRejectsInvalidCsrfToken(): void
    {
        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer());

        $response = $controller->updatePermissions(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'rightIds' => [1],
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Invalid CSRF token.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdatePermissionsRejectsRightNotHeldByNonSuperAdmin(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group-permissions');
        $this->setCsrfCookie('update-group-permissions', $csrfToken);

        // createAuthenticatedContainer(): non-SuperAdmin whose perm stub only holds
        // USER_ADD/USER_EDIT/USER_DELETE/GROUP_EDIT — right 999 is not among them.
        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->updatePermissions(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'rightIds' => [999],
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot grant a right you do not hold.', $payload['error']);
        $this->removeCsrfCookie('update-group-permissions');
    }

    /**
     * @throws \Exception
     */
    public function testUpdatePermissionsReplacesRightsForSuperAdmin(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group-permissions');
        $this->setCsrfCookie('update-group-permissions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer($session));

        // Fixtures seed rights [1, 2]; replace with [3, 4].
        $response = $controller->updatePermissions(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'rightIds' => [3, 4],
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);

        $rightsResponse = $controller->listPermissions(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $rightsPayload = json_decode((string) $rightsResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([3, 4], $rightsPayload);
        $this->removeCsrfCookie('update-group-permissions');
    }

    /**
     * @throws \Exception
     */
    public function testDeleteGroupRequiresGroupDeletePermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->deleteGroup(new Request(content: '{}'));
    }

    /**
     * @throws \Exception
     */
    public function testDeleteGroupRejectsInvalidCsrfToken(): void
    {
        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer());

        $response = $controller->deleteGroup(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Invalid CSRF token.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteGroupRejectsUserWithoutGroupDeletePermission(): void
    {
        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $this->expectException(ForbiddenException::class);
        $controller->deleteGroup(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'csrfToken' => 'irrelevant',
        ], JSON_THROW_ON_ERROR)));
    }

    /**
     * @throws \Exception
     */
    public function testUpdateGroupRejectsAutoJoinForNonSuperAdminWithoutMediumPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group');
        $this->setCsrfCookie('update-group', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->updateGroup(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'name' => 'Editors',
            'autoJoin' => true,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot enable auto-join without group permission support.', $payload['error']);
        $this->removeCsrfCookie('update-group');
    }

    /**
     * @throws \Exception
     */
    public function testDeleteGroupDeletesSeededGroup(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('delete-group');
        $this->setCsrfCookie('delete-group', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer($session));

        $response = $controller->deleteGroup(new Request(content: json_encode([
            'groupId' => self::TEST_GROUP_ID,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_group_deleted'), $payload['success']);

        $listResponse = $controller->listGroups();
        $listPayload = json_decode((string) $listResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotContains(self::TEST_GROUP_ID, array_column($listPayload, 'group_id'));
        $this->removeCsrfCookie('delete-group');
    }
}
