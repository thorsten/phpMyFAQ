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
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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

    private function createContainerForUser(CurrentUser $currentUser, ?Session $session = null): ContainerInterface
    {
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

    private function seedCategoryFixtures(): void
    {
        // Each category exists in English and French; only the language of the
        // current UI ('en' in this test setup) may be returned. 'fr' sorts
        // after 'en', so without a language filter the French rows would win
        // the per-id overwrite in loadCategories().
        $this->dbHandle->query("INSERT INTO faqcategories
                (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES
                (1, 'en', 0, 'News', '', -1, -1, 1, '', 1),
                (2, 'en', 0, 'Guides', '', -1, -1, 1, '', 1),
                (3, 'en', 2, 'Setup', '', -1, -1, 1, '', 1),
                (4, 'en', 99, 'Orphan', '', -1, -1, 1, '', 1),
                (1, 'fr', 0, 'Nouvelles', '', -1, -1, 1, '', 1),
                (2, 'fr', 0, 'Tutoriels', '', -1, -1, 1, '', 1),
                (3, 'fr', 2, 'Installation', '', -1, -1, 1, '', 1)");
        // The category order (same source the category overview page uses)
        // puts 'Guides' before 'News'; positions restart per parent level.
        $this->dbHandle->query('INSERT INTO faqcategory_order (category_id, parent_id, position)
             VALUES (2, 0, 1), (3, 2, 1), (1, 0, 2), (4, 99, 3)');
        // group_id -1 marks a category as visible to everyone.
        $this->dbHandle->query('INSERT INTO faqcategory_group (category_id, group_id)
             VALUES (1, -1), (2, -1), (3, -1), (4, -1)');
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
    public function testListLanguageRestrictionsRequiresGroupPermission(): void
    {
        $request = new Request([], [], ['groupId' => self::TEST_GROUP_ID]);
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listLanguageRestrictions($request);
    }

    /**
     * @throws \Exception
     */
    public function testListLanguageRestrictionsReturnsEmptyWhenNoneSet(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listLanguageRestrictions(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testSaveLanguageRestrictionsRequiresGroupPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->saveLanguageRestrictions(new Request(content: '{}'));
    }

    /**
     * @throws \Exception
     */
    public function testSaveLanguageRestrictionsRejectsInvalidCsrfToken(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->saveLanguageRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'languages' => ['en'],
                'csrfToken' => 'invalid-token',
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Invalid CSRF token.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testSaveLanguageRestrictionsSavesAndSkipsUnsupportedLanguages(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-language-restrictions');
        $this->setCsrfCookie('save-language-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->saveLanguageRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'languages' => ['en', 'not-a-language'],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);

        $listResponse = $controller->listLanguageRestrictions(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $listPayload = json_decode((string) $listResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['1' => ['en']], $listPayload);
        $this->removeCsrfCookie('save-language-restrictions');
    }

    /**
     * A list of only unsupported codes filters down to an empty set, which would
     * otherwise be persisted as "unrestricted".
     *
     * @throws \Exception
     */
    public function testSaveLanguageRestrictionsRejectsOnlyUnsupportedLanguageCodes(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-language-restrictions');
        $this->setCsrfCookie('save-language-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->saveLanguageRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'languages' => ['not-a-language'],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('No supported language code provided.', $payload['error']);
        $this->removeCsrfCookie('save-language-restrictions');
    }

    /**
     * Group rights are inherited by every member, so a language-restricted admin must
     * not be able to scope a group right to a language they do not hold themselves.
     *
     * @throws \Exception
     */
    public function testSaveLanguageRestrictionsRejectsLanguageNotHeldByNonSuperAdmin(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-language-restrictions');
        $this->setCsrfCookie('save-language-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createRestrictedContainer($session, ['de']));

        $response = $controller->saveLanguageRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'languages' => ['fr'],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
        $this->removeCsrfCookie('save-language-restrictions');
    }

    /**
     * An empty language list clears every restriction row, which the permission model
     * reads as "all languages" — a grant, not a narrowing.
     *
     * @throws \Exception
     */
    public function testSaveLanguageRestrictionsRejectsEmptyListFromRestrictedNonSuperAdmin(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-language-restrictions');
        $this->setCsrfCookie('save-language-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createRestrictedContainer($session, ['de']));

        $response = $controller->saveLanguageRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'languages' => [],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
        $this->removeCsrfCookie('save-language-restrictions');
    }

    /**
     * @throws \Exception
     */
    public function testSaveCategoryRestrictionsRequiresGroupPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(UnauthorizedHttpException::class);
        $controller->saveCategoryRestrictions(new Request(content: '{}'));
    }

    /**
     * @throws \Exception
     */
    public function testSaveCategoryRestrictionsSavesSubsetForSuperAdmin(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-category-restrictions');
        $this->setCsrfCookie('save-category-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer($session));

        $response = $controller->saveCategoryRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'categoryIds' => [3, 3, 'abc', 0],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);

        $listResponse = $controller->listCategoryRestrictions(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $listPayload = json_decode((string) $listResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['1' => [3]], $listPayload);
        $this->removeCsrfCookie('save-category-restrictions');
    }

    /**
     * A list of only invalid IDs filters down to an empty set, which would otherwise be
     * persisted as "unrestricted".
     *
     * @throws \Exception
     */
    public function testSaveCategoryRestrictionsRejectsOnlyInvalidCategoryIds(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-category-restrictions');
        $this->setCsrfCookie('save-category-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer($session));

        $response = $controller->saveCategoryRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'categoryIds' => ['abc', 0, -5],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_category_restrictions_no_category'), $payload['error']);
        $this->removeCsrfCookie('save-category-restrictions');
    }

    /**
     * Group rights are inherited by every member, so a category-restricted admin must
     * not be able to scope a group right to a category they do not hold themselves.
     *
     * @throws \Exception
     */
    public function testSaveCategoryRestrictionsRejectsCategoryNotHeldByNonSuperAdmin(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-category-restrictions');
        $this->setCsrfCookie('save-category-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createRestrictedContainer($session, ['de'], [1, 2]));

        $response = $controller->saveCategoryRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'categoryIds' => [1, 3],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
        $this->removeCsrfCookie('save-category-restrictions');
    }

    /**
     * An empty category list clears every restriction row, which the permission model
     * reads as "all categories" — a grant, not a narrowing.
     *
     * @throws \Exception
     */
    public function testSaveCategoryRestrictionsRejectsEmptyListFromRestrictedNonSuperAdmin(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-category-restrictions');
        $this->setCsrfCookie('save-category-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createRestrictedContainer($session, ['de'], [1, 2]));

        $response = $controller->saveCategoryRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'categoryIds' => [],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
        $this->removeCsrfCookie('save-category-restrictions');
    }

    /**
     * @throws \Exception
     */
    public function testSaveCategoryRestrictionsAcceptsSubsetHeldByNonSuperAdmin(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('save-category-restrictions');
        $this->setCsrfCookie('save-category-restrictions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createRestrictedContainer($session, ['de'], [1, 2]));

        $response = $controller->saveCategoryRestrictions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightId' => 1,
                'categoryIds' => [2],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        $this->removeCsrfCookie('save-category-restrictions');
    }

    /**
     * Builds a container whose acting user holds the group-permission gate but is a
     * non-SuperAdmin restricted to $allowedLanguages (and, if given, $allowedCategories)
     * for every right.
     *
     * @param array<string>  $allowedLanguages
     * @param array<int>|null $allowedCategories null means unrestricted
     */
    private function createRestrictedContainer(
        Session $session,
        array $allowedLanguages,
        ?array $allowedCategories = null,
    ): ContainerInterface {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);
        $permission->method('getAllowedLanguagesForRight')->willReturn($allowedLanguages);
        $permission->method('getAllowedCategoriesForRight')->willReturn($allowedCategories);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('isSuperAdmin')->willReturn(false);

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
     * Acting user 1 as a non-SuperAdmin backed by the real MediumPermission, so the
     * language and category scopes the controller consults come from the database.
     */
    private function createScopedContainer(Session $session, MediumPermission $permission): ContainerInterface
    {
        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('isSuperAdmin')->willReturn(false);

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
     * Turns user 1 into a non-SuperAdmin group administrator without any content right,
     * and creates the empty group the tests grant rights to.
     */
    private function seedScopedGroupAdministrator(): void
    {
        $this->seedCurrentUserSession();
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->dbHandle->query('DELETE FROM faquser_right WHERE user_id = 1');
        $this->dbHandle->query('DELETE FROM faquser_group WHERE user_id = 1');
        foreach (
            [PermissionType::USER_ADD, PermissionType::USER_EDIT, PermissionType::USER_DELETE, PermissionType::GROUP_EDIT]
            as $permissionType
        ) {
            $this->dbHandle->query(sprintf(
                'INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)',
                $this->rightId($permissionType),
            ));
        }

        $this->dbHandle->query(sprintf(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (%d, 'Editors', '', 0)",
            self::TEST_GROUP_ID,
        ));
    }

    private function rightId(PermissionType $permissionType): int
    {
        $result = $this->dbHandle->query(sprintf(
            "SELECT right_id FROM faqright WHERE name = '%s'",
            $permissionType->value,
        ));
        $row = $this->dbHandle->fetchArray($result);
        self::assertIsArray($row);

        return (int) $row['right_id'];
    }

    /**
     * @throws \Exception
     */
    public function testListLanguagesRequiresGroupPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listLanguages();
    }

    /**
     * @throws \Exception
     */
    public function testListLanguagesReturnsAvailableLanguages(): void
    {
        $this->seedCurrentUserSession();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listLanguages();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotEmpty($payload);
        self::assertContains(['code' => 'en', 'label' => 'English'], $payload);
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

        $response = $controller->updateGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'name' => 'Editors',
                'csrfToken' => 'invalid-token',
            ], JSON_THROW_ON_ERROR)),
        );
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

        $response = $controller->updateGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'name' => '   ',
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
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

        $response = $controller->updateGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'name' => 'Editors renamed',
                'description' => 'Updated description',
                'autoJoin' => true,
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
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

        $response = $controller->updateMembers(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'memberIds' => [1],
                'csrfToken' => 'invalid-token',
            ], JSON_THROW_ON_ERROR)),
        );
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

        $response = $controller->updateMembers(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'memberIds' => [1],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot manage group membership without group permission support.', $payload['error']);
        $this->removeCsrfCookie('update-group-members');
    }

    /**
     * @throws \Exception
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testUpdateMembersRejectsNonSuperAdminLackingGroupRight(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group-members');
        $this->setCsrfCookie('update-group-members', $csrfToken);

        // The target group holds right id 42, which the acting user does NOT hold. Managing its
        // membership would let the acting user inherit that right via group membership, so it must
        // be refused. hasPermission() returns true for the string-keyed permission gate but false
        // for the integer right id 42 the target group holds.
        $perm = $this->getMockBuilder(MediumPermission::class)->disableOriginalConstructor()->getMock();
        $perm->method('getGroupRights')->willReturn([42]);
        $perm->method('hasPermission')->willReturnCallback(
            static fn(int $userId, mixed $right): bool => is_string($right),
        );

        $actingUser = $this->createStub(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(5);
        $actingUser->method('isSuperAdmin')->willReturn(false);

        $adminLog = $this->createStub(AdminLog::class);
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($actingUser, $session, $adminLog) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $actingUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        $controller = new GroupController();
        $controller->setContainer($container);

        $response = $controller->updateMembers(
            new Request(content: json_encode([
                'groupId' => 1,
                'memberIds' => [5],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot manage a group whose rights you do not hold.', $payload['error']);
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
        $response = $controller->updateMembers(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'memberIds' => [2],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
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

        $response = $controller->updatePermissions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightIds' => [1],
                'csrfToken' => 'invalid-token',
            ], JSON_THROW_ON_ERROR)),
        );
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

        $response = $controller->updatePermissions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightIds' => [999],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
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
        $response = $controller->updatePermissions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightIds' => [3, 4],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);

        $rightsResponse = $controller->listPermissions(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $rightsPayload = json_decode((string) $rightsResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([3, 4], $rightsPayload);
        $this->removeCsrfCookie('update-group-permissions');
    }

    /**
     * A non-SuperAdmin holding a right for one language only must not be able to grant that
     * right to a group unrestricted and then inherit it for every language by joining the group.
     *
     * @throws \Exception
     */
    public function testUpdatePermissionsNarrowsGrantToLanguageScopeOfNonSuperAdmin(): void
    {
        $this->seedScopedGroupAdministrator();
        $faqEdit = $this->rightId(PermissionType::FAQ_EDIT);
        $this->dbHandle->query(sprintf('INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)', $faqEdit));
        $this->dbHandle->query(sprintf(
            "INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, %d, 'en')",
            $faqEdit,
        ));

        $permission = new MediumPermission($this->configuration);
        self::assertFalse($permission->hasPermissionForLanguage(1, $faqEdit, 'de'));

        $session = new Session(new MockArraySessionStorage());
        $controller = new GroupController();
        $controller->setContainer($this->createScopedContainer($session, $permission));

        $csrfToken = Token::getInstance($session)->getTokenString('update-group-permissions');
        $this->setCsrfCookie('update-group-permissions', $csrfToken);
        $response = $controller->updatePermissions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightIds' => [$faqEdit],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([$faqEdit], $permission->getGroupRights(self::TEST_GROUP_ID));
        self::assertSame(['en'], $permission->getLanguageRestrictions(self::TEST_GROUP_ID, $faqEdit));
        $this->removeCsrfCookie('update-group-permissions');

        // The group now holds the right in the acting user's own scope, so joining it is allowed
        // and widens nothing.
        $csrfToken = Token::getInstance($session)->getTokenString('update-group-members');
        $this->setCsrfCookie('update-group-members', $csrfToken);
        $response = $controller->updateMembers(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'memberIds' => [1],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->removeCsrfCookie('update-group-members');

        self::assertTrue($permission->hasPermissionForLanguage(1, $faqEdit, 'en'));
        self::assertFalse($permission->hasPermissionForLanguage(1, $faqEdit, 'de'));
        self::assertSame(['en'], $permission->getAllowedLanguagesForRight(1, $faqEdit));
    }

    /**
     * Saving permissions resets the group's language scope, so a restriction set earlier is
     * replaced by the acting user's own scope, never by "unrestricted".
     *
     * @throws \Exception
     */
    public function testUpdatePermissionsReplacesGroupRestrictionsWithNonSuperAdminScope(): void
    {
        $this->seedScopedGroupAdministrator();
        $faqEdit = $this->rightId(PermissionType::FAQ_EDIT);
        $this->dbHandle->query(sprintf('INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)', $faqEdit));
        $this->dbHandle->query(sprintf(
            "INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, %d, 'en'), (1, %d, 'de')",
            $faqEdit,
            $faqEdit,
        ));
        $this->dbHandle->query(sprintf(
            "INSERT INTO faqgroup_right_language (group_id, right_id, language) VALUES (%d, %d, 'fr')",
            self::TEST_GROUP_ID,
            $faqEdit,
        ));

        $permission = new MediumPermission($this->configuration);
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group-permissions');
        $this->setCsrfCookie('update-group-permissions', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createScopedContainer($session, $permission));

        $response = $controller->updatePermissions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightIds' => [$faqEdit],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $restrictions = $permission->getLanguageRestrictions(self::TEST_GROUP_ID, $faqEdit);
        sort($restrictions);
        self::assertSame(['de', 'en'], $restrictions);
        $this->removeCsrfCookie('update-group-permissions');
    }

    /**
     * Category scope can only come from a group grant, so the acting user holds the right via
     * another group restricted to one category and must not widen it via a fresh group.
     *
     * @throws \Exception
     */
    public function testUpdatePermissionsNarrowsGrantToCategoryScopeOfNonSuperAdmin(): void
    {
        $this->seedScopedGroupAdministrator();
        $faqEdit = $this->rightId(PermissionType::FAQ_EDIT);
        $this->dbHandle->query("INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (4243, 'News', '', 0)");
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 4243)');
        $this->dbHandle->query(sprintf('INSERT INTO faqgroup_right (group_id, right_id) VALUES (4243, %d)', $faqEdit));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right_category (group_id, right_id, category_id) VALUES (4243, %d, 7)',
            $faqEdit,
        ));

        $permission = new MediumPermission($this->configuration);
        self::assertFalse($permission->hasPermissionForCategory(1, $faqEdit, 8));

        $session = new Session(new MockArraySessionStorage());
        $controller = new GroupController();
        $controller->setContainer($this->createScopedContainer($session, $permission));

        $csrfToken = Token::getInstance($session)->getTokenString('update-group-permissions');
        $this->setCsrfCookie('update-group-permissions', $csrfToken);
        $response = $controller->updatePermissions(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'rightIds' => [$faqEdit],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([7], $permission->getCategoryRestrictions(self::TEST_GROUP_ID, $faqEdit));
        $this->removeCsrfCookie('update-group-permissions');

        $csrfToken = Token::getInstance($session)->getTokenString('update-group-members');
        $this->setCsrfCookie('update-group-members', $csrfToken);
        $response = $controller->updateMembers(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'memberIds' => [1],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->removeCsrfCookie('update-group-members');

        self::assertTrue($permission->hasPermissionForCategory(1, $faqEdit, 7));
        self::assertFalse($permission->hasPermissionForCategory(1, $faqEdit, 8));
    }

    /**
     * A group holding a right unrestricted lies outside the scope of an acting user who holds
     * that right for one language only, so they may not manage its membership.
     *
     * @throws \Exception
     */
    public function testUpdateMembersRejectsGroupRightWiderThanNonSuperAdminScope(): void
    {
        $this->seedScopedGroupAdministrator();
        $faqEdit = $this->rightId(PermissionType::FAQ_EDIT);
        $this->dbHandle->query(sprintf('INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)', $faqEdit));
        $this->dbHandle->query(sprintf(
            "INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, %d, 'en')",
            $faqEdit,
        ));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (%d, %d)',
            self::TEST_GROUP_ID,
            $faqEdit,
        ));

        $permission = new MediumPermission($this->configuration);
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group-members');
        $this->setCsrfCookie('update-group-members', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createScopedContainer($session, $permission));

        $response = $controller->updateMembers(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'memberIds' => [1],
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot manage a group whose rights you do not hold.', $payload['error']);
        self::assertFalse($permission->hasPermissionForLanguage(1, $faqEdit, 'de'));
        $this->removeCsrfCookie('update-group-members');
    }

    /**
     * @throws \Exception
     */
    public function testUpdateGroupRejectsAutoJoinOnGroupRightWiderThanNonSuperAdminScope(): void
    {
        $this->seedScopedGroupAdministrator();
        $faqEdit = $this->rightId(PermissionType::FAQ_EDIT);
        $this->dbHandle->query(sprintf('INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)', $faqEdit));
        $this->dbHandle->query(sprintf(
            "INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, %d, 'en')",
            $faqEdit,
        ));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (%d, %d)',
            self::TEST_GROUP_ID,
            $faqEdit,
        ));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('update-group');
        $this->setCsrfCookie('update-group', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createScopedContainer($session, new MediumPermission($this->configuration)));

        $response = $controller->updateGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'name' => 'Editors',
                'autoJoin' => true,
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot enable auto-join on a group whose rights you do not hold.', $payload['error']);
        $this->removeCsrfCookie('update-group');
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

        $response = $controller->deleteGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'csrfToken' => 'invalid-token',
            ], JSON_THROW_ON_ERROR)),
        );
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
        $controller->deleteGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'csrfToken' => 'irrelevant',
            ], JSON_THROW_ON_ERROR)),
        );
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

        $response = $controller->updateGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'name' => 'Editors',
                'autoJoin' => true,
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot enable auto-join without group permission support.', $payload['error']);
        $this->removeCsrfCookie('update-group');
    }

    /**
     * @throws \Exception
     */
    public function testDeleteGroupFailsClosedForNonSuperAdminWithoutMediumPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('delete-group');
        $this->setCsrfCookie('delete-group', $csrfToken);

        // The acting user holds GROUP_DELETE, but perm is a PermissionInterface stub, NOT
        // MediumPermission, so the group's rights cannot be enumerated and it must fail closed.
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $actingUser = $this->createStub(CurrentUser::class);
        $actingUser->perm = $permission;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(1);
        $actingUser->method('isSuperAdmin')->willReturn(false);

        $controller = new GroupController();
        $controller->setContainer($this->createContainerForUser($actingUser, $session));

        $response = $controller->deleteGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot delete a group without group permission support.', $payload['error']);
        $this->removeCsrfCookie('delete-group');
    }

    /**
     * @throws \Exception
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testDeleteGroupRejectsNonSuperAdminLackingGroupRight(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('delete-group');
        $this->setCsrfCookie('delete-group', $csrfToken);

        // The target group holds right id 42, which the acting user does NOT hold. Deleting the
        // group would destroy a privilege the acting user neither holds nor could grant, so it
        // must be refused. hasPermission() returns true for the string-keyed permission gate but
        // false for the integer right id 42 the target group holds.
        $perm = $this->getMockBuilder(MediumPermission::class)->disableOriginalConstructor()->getMock();
        $perm->method('getGroupRights')->willReturn([42]);
        $perm->method('hasPermission')->willReturnCallback(
            static fn(int $userId, mixed $right): bool => is_string($right),
        );
        $perm->expects(self::never())->method('deleteGroup');

        $actingUser = $this->createStub(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(5);
        $actingUser->method('isSuperAdmin')->willReturn(false);

        $controller = new GroupController();
        $controller->setContainer($this->createContainerForUser($actingUser, $session));

        $response = $controller->deleteGroup(
            new Request(content: json_encode([
                'groupId' => 1,
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot delete a group whose rights you do not hold.', $payload['error']);
        $this->removeCsrfCookie('delete-group');
    }

    /**
     * A group holding a right unrestricted lies outside the scope of an acting user who holds
     * that right for one language only, so they may not delete it either.
     *
     * @throws \Exception
     */
    public function testDeleteGroupRejectsGroupRightWiderThanNonSuperAdminScope(): void
    {
        $this->seedScopedGroupAdministrator();
        $this->dbHandle->query(sprintf(
            'INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)',
            $this->rightId(PermissionType::GROUP_DELETE),
        ));
        $faqEdit = $this->rightId(PermissionType::FAQ_EDIT);
        $this->dbHandle->query(sprintf('INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)', $faqEdit));
        $this->dbHandle->query(sprintf(
            "INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, %d, 'en')",
            $faqEdit,
        ));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (%d, %d)',
            self::TEST_GROUP_ID,
            $faqEdit,
        ));

        $permission = new MediumPermission($this->configuration);
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('delete-group');
        $this->setCsrfCookie('delete-group', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createScopedContainer($session, $permission));

        $response = $controller->deleteGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Cannot delete a group whose rights you do not hold.', $payload['error']);
        self::assertSame([$faqEdit], array_map(intval(...), $permission->getGroupRights(self::TEST_GROUP_ID)));
        $this->removeCsrfCookie('delete-group');
    }

    /**
     * @throws \Exception
     */
    public function testDeleteGroupAllowsNonSuperAdminHoldingEveryGroupRight(): void
    {
        $this->seedScopedGroupAdministrator();
        $this->dbHandle->query(sprintf(
            'INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)',
            $this->rightId(PermissionType::GROUP_DELETE),
        ));
        $faqEdit = $this->rightId(PermissionType::FAQ_EDIT);
        $this->dbHandle->query(sprintf('INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)', $faqEdit));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (%d, %d)',
            self::TEST_GROUP_ID,
            $faqEdit,
        ));

        $permission = new MediumPermission($this->configuration);
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('delete-group');
        $this->setCsrfCookie('delete-group', $csrfToken);

        $controller = new GroupController();
        $controller->setContainer($this->createScopedContainer($session, $permission));

        $response = $controller->deleteGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_group_deleted'), $payload['success']);
        self::assertSame([], $permission->getGroupRights(self::TEST_GROUP_ID));
        $this->removeCsrfCookie('delete-group');
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

        $response = $controller->deleteGroup(
            new Request(content: json_encode([
                'groupId' => self::TEST_GROUP_ID,
                'csrfToken' => $csrfToken,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_group_deleted'), $payload['success']);

        $listResponse = $controller->listGroups();
        $listPayload = json_decode((string) $listResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotContains(self::TEST_GROUP_ID, array_column($listPayload, 'group_id'));
        $this->removeCsrfCookie('delete-group');
    }

    /**
     * @throws \Exception
     */
    public function testListCategoriesReturnsDepthFirstOrderWithLevels(): void
    {
        $this->seedCurrentUserSession();
        $this->seedCategoryFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createSuperAdminContainer());

        $response = $controller->listCategories();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        // Sibling order follows faqcategory_order (same as the category
        // overview page), subcategories directly follow their parent, only the
        // current UI language is returned, and categories with an unreachable
        // parent are appended as roots.
        self::assertSame(['Guides', 'Setup', 'News', 'Orphan'], array_column($payload, 'name'));
        self::assertSame([0, 1, 0, 0], array_column($payload, 'level'));
    }

    /**
     * A logged-out caller must trigger UnauthorizedHttpException (translated to a login
     * redirect / 401 by the application), not a bare 403, because the group permission gate
     * authenticates first.
     *
     * @throws \Exception
     */
    public function testListGroupsRejectsUnauthenticatedUser(): void
    {
        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);

        $controller = new GroupController();
        $controller->setContainer($this->createContainerForUser($currentUser));

        $this->expectException(UnauthorizedHttpException::class);
        $controller->listGroups();
    }

    /**
     * A logged-in caller without the group permissions is still rejected with
     * ForbiddenException (403): authentication passes, the permission gate fails.
     *
     * @throws \Exception
     */
    public function testListGroupsForbidsAuthenticatedUserWithoutPermission(): void
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(false);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(5);

        $controller = new GroupController();
        $controller->setContainer($this->createContainerForUser($currentUser));

        $this->expectException(ForbiddenException::class);
        $controller->listGroups();
    }
}
