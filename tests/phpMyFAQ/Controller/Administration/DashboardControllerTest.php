<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Faq as AdminFaq;
use phpMyFAQ\Administration\RecentUsers;
use phpMyFAQ\Administration\RemoteApiClient;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
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

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(DashboardController::class)]
#[UsesNamespace('phpMyFAQ')]
final class DashboardControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-dashboard-page-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
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

    private function createController(): DashboardController
    {
        return new DashboardController(
            $this->createStub(AdminSession::class),
            $this->createStub(AdminFaq::class),
            $this->createStub(Backup::class),
            new RecentUsers($this->configuration),
            $this->createStub(RemoteApiClient::class),
        );
    }

    /**
     * @throws \Exception
     */
    public function testIndexRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(UnauthorizedHttpException::class);
        $controller->index($request);
    }

    /**
     * @throws \Exception
     */
    public function testIndexRendersDashboardForAuthenticatedUser(): void
    {
        $adminSession = $this->createStub(AdminSession::class);
        $adminSession->method('getNumberOfSessions')->willReturn(3);
        $adminSession->method('getNumberOfOnlineUsers')->willReturn(1);

        $adminFaq = $this->createStub(AdminFaq::class);
        $adminFaq->method('getInactiveFaqsData')->willReturn([]);

        $backup = $this->createStub(Backup::class);
        $backup
            ->method('getLastBackupInfo')
            ->willReturn([
                'lastBackupDate' => '2026-03-01',
                'isBackupOlderThan30Days' => false,
            ]);

        $controller = new DashboardController(
            $adminSession,
            $adminFaq,
            $backup,
            new RecentUsers($this->configuration),
            $this->createStub(RemoteApiClient::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request();
        $request->attributes->set('_route', 'admin.dashboard');
        $response = $controller->index($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Dashboard', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testIndexRendersVersionCheckErrorWhenApiThrows(): void
    {
        $this->overrideConfigurationValues(['main.enableAutoUpdateHint' => false]);

        $adminSession = $this->createStub(AdminSession::class);
        $adminSession->method('getNumberOfSessions')->willReturn(3);
        $adminSession->method('getNumberOfOnlineUsers')->willReturn(1);

        $adminFaq = $this->createStub(AdminFaq::class);
        $adminFaq->method('getInactiveFaqsData')->willReturn([]);

        $backup = $this->createStub(Backup::class);
        $backup
            ->method('getLastBackupInfo')
            ->willReturn([
                'lastBackupDate' => '2026-03-01',
                'isBackupOlderThan30Days' => false,
            ]);

        $adminApi = $this->createStub(RemoteApiClient::class);
        $adminApi->method('getVersions')->willThrowException(new Exception('Version check failed'));

        $controller = new DashboardController(
            $adminSession,
            $adminFaq,
            $backup,
            new RecentUsers($this->configuration),
            $adminApi,
        );
        $controller->setContainer($this->createAuthenticatedContainer(allowConfigEdit: true));

        $request = new Request();
        $request->attributes->set('_route', 'admin.dashboard');
        $request->attributes->set('param', 'version');
        $response = $controller->index($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('id="phpmyfaq-latest-version"', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testIndexPassesRecentNewsConfigToTemplate(): void
    {
        $this->overrideConfigurationValues(['main.enableRecentNews' => true]);

        $adminSession = $this->createStub(AdminSession::class);
        $adminSession->method('getNumberOfSessions')->willReturn(3);
        $adminSession->method('getNumberOfOnlineUsers')->willReturn(1);

        $adminFaq = $this->createStub(AdminFaq::class);
        $adminFaq->method('getInactiveFaqsData')->willReturn([]);

        $backup = $this->createStub(Backup::class);
        $backup
            ->method('getLastBackupInfo')
            ->willReturn([
                'lastBackupDate' => '2026-03-01',
                'isBackupOlderThan30Days' => false,
            ]);

        $controller = new DashboardController(
            $adminSession,
            $adminFaq,
            $backup,
            new RecentUsers($this->configuration),
            $this->createStub(RemoteApiClient::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request();
        $request->attributes->set('_route', 'admin.dashboard');
        $response = $controller->index($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('pmf-recent-news', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testIndexHidesRecentNewsWhenDisabled(): void
    {
        $this->overrideConfigurationValues(['main.enableRecentNews' => false]);

        $adminSession = $this->createStub(AdminSession::class);
        $adminSession->method('getNumberOfSessions')->willReturn(3);
        $adminSession->method('getNumberOfOnlineUsers')->willReturn(1);

        $adminFaq = $this->createStub(AdminFaq::class);
        $adminFaq->method('getInactiveFaqsData')->willReturn([]);

        $backup = $this->createStub(Backup::class);
        $backup
            ->method('getLastBackupInfo')
            ->willReturn([
                'lastBackupDate' => '2026-03-01',
                'isBackupOlderThan30Days' => false,
            ]);

        $controller = new DashboardController(
            $adminSession,
            $adminFaq,
            $backup,
            new RecentUsers($this->configuration),
            $this->createStub(RemoteApiClient::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request();
        $request->attributes->set('_route', 'admin.dashboard');
        $response = $controller->index($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringNotContainsString('pmf-recent-news', (string) $response->getContent());
    }

    /**
     * Regression test: a logged-in account without any admin right (e.g. a front-end user who
     * signed in through the public login form) must not see unpublished FAQs, recent user
     * accounts, site counters or backup state on the dashboard (CWE-862).
     *
     * @throws \Exception
     */
    public function testIndexRendersEmptyDashboardForUserWithoutAdminRights(): void
    {
        $adminSession = $this->createMock(AdminSession::class);
        $adminSession->expects(self::never())->method('getNumberOfSessions');
        $adminSession->expects(self::never())->method('getNumberOfOnlineUsers');

        $adminFaq = $this->createMock(AdminFaq::class);
        $adminFaq->expects(self::never())->method('getInactiveFaqsData');

        $backup = $this->createMock(Backup::class);
        $backup->expects(self::never())->method('getLastBackupInfo');

        $controller = new DashboardController(
            $adminSession,
            $adminFaq,
            $backup,
            new RecentUsers($this->configuration),
            $this->createStub(RemoteApiClient::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer(grantedPermissions: []));

        $request = new Request();
        $request->attributes->set('_route', 'admin.dashboard');
        $response = $controller->index($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = (string) $response->getContent();
        self::assertStringContainsString('Dashboard', $content);
        self::assertStringNotContainsString('id="pmf-dashboard-metrics"', $content);
        self::assertStringNotContainsString('id="pmf-chart-visits"', $content);
        self::assertStringNotContainsString('data-pmf-widget="inactive-faqs"', $content);
        self::assertStringNotContainsString('data-pmf-widget="recent-users"', $content);
        self::assertStringNotContainsString('./user/edit/', $content);
        self::assertStringNotContainsString('data-pmf-widget="content-health"', $content);
        self::assertStringNotContainsString('data-pmf-widget="popular-searches"', $content);
        self::assertStringNotContainsString('data-pmf-widget="version-check"', $content);
        self::assertStringNotContainsString('data-pmf-widget="backup-status"', $content);
    }

    /**
     * @throws \Exception
     */
    public function testIndexShowsInactiveFaqsOnlyWithFaqEditRight(): void
    {
        $adminFaq = $this->createStub(AdminFaq::class);
        $adminFaq
            ->method('getInactiveFaqsData')
            ->willReturn([
                [
                    'id' => 1,
                    'lang' => 'en',
                    'question' => 'DRAFT-MARKER unreleased question',
                    'url' => 'https://localhost/admin/faq/edit/1/en',
                ],
            ]);

        $controller = new DashboardController(
            $this->createStub(AdminSession::class),
            $adminFaq,
            $this->createStub(Backup::class),
            new RecentUsers($this->configuration),
            $this->createStub(RemoteApiClient::class),
        );
        $controller->setContainer(
            $this->createAuthenticatedContainer(grantedPermissions: [PermissionType::FAQ_EDIT]),
        );

        $request = new Request();
        $request->attributes->set('_route', 'admin.dashboard');
        $response = $controller->index($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = (string) $response->getContent();
        self::assertStringContainsString('data-pmf-widget="inactive-faqs"', $content);
        self::assertStringContainsString('DRAFT-MARKER unreleased question', $content);
        self::assertStringNotContainsString('data-pmf-widget="recent-users"', $content);
        self::assertStringNotContainsString('./user/edit/', $content);
        self::assertStringNotContainsString('id="pmf-dashboard-metrics"', $content);
    }

    /**
     * @throws \Exception
     */
    public function testIndexShowsRecentUsersOnlyWithUserEditRight(): void
    {
        $adminFaq = $this->createMock(AdminFaq::class);
        $adminFaq->expects(self::never())->method('getInactiveFaqsData');

        $controller = new DashboardController(
            $this->createStub(AdminSession::class),
            $adminFaq,
            $this->createStub(Backup::class),
            new RecentUsers($this->configuration),
            $this->createStub(RemoteApiClient::class),
        );
        $controller->setContainer(
            $this->createAuthenticatedContainer(grantedPermissions: [PermissionType::USER_EDIT]),
        );

        $request = new Request();
        $request->attributes->set('_route', 'admin.dashboard');
        $response = $controller->index($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = (string) $response->getContent();
        self::assertStringContainsString('data-pmf-widget="recent-users"', $content);
        self::assertStringContainsString('./user/edit/1', $content);
        self::assertStringNotContainsString('data-pmf-widget="inactive-faqs"', $content);
    }

    /**
     * @throws \Exception
     */
    public function testIndexShowsStatisticsAndBackupWidgetsWithMatchingRights(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => true]);

        $adminSession = $this->createStub(AdminSession::class);
        $adminSession->method('getNumberOfSessions')->willReturn(3);
        $adminSession->method('getNumberOfOnlineUsers')->willReturn(1);

        $backup = $this->createStub(Backup::class);
        $backup
            ->method('getLastBackupInfo')
            ->willReturn([
                'lastBackupDate' => '2026-03-01',
                'isBackupOlderThan30Days' => false,
            ]);

        $controller = new DashboardController(
            $adminSession,
            $this->createStub(AdminFaq::class),
            $backup,
            new RecentUsers($this->configuration),
            $this->createStub(RemoteApiClient::class),
        );
        $controller->setContainer(
            $this->createAuthenticatedContainer(
                grantedPermissions: [PermissionType::STATISTICS_VIEWLOGS, PermissionType::BACKUP],
            ),
        );

        $request = new Request();
        $request->attributes->set('_route', 'admin.dashboard');
        $response = $controller->index($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = (string) $response->getContent();
        self::assertStringContainsString('id="pmf-dashboard-metrics"', $content);
        self::assertStringContainsString('id="pmf-chart-visits"', $content);
        self::assertStringContainsString('data-pmf-widget="content-health"', $content);
        self::assertStringContainsString('data-pmf-widget="popular-searches"', $content);
        self::assertStringContainsString('data-pmf-widget="backup-status"', $content);
        self::assertStringContainsString('2026-03-01', $content);
        self::assertStringNotContainsString('data-pmf-widget="inactive-faqs"', $content);
        self::assertStringNotContainsString('data-pmf-widget="recent-users"', $content);
    }

    /**
     * @param list<PermissionType>|null $grantedPermissions null grants every right except CONFIGURATION_EDIT
     */
    private function createAuthenticatedContainer(
        bool $allowConfigEdit = false,
        ?array $grantedPermissions = null,
    ): ContainerInterface {
        $grantedRights = $grantedPermissions === null
            ? null
            : array_map(static fn(PermissionType $type): string => $type->value, $grantedPermissions);

        $permission = $this->createStub(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static function (int $userId, mixed $right) use ($allowConfigEdit, $grantedRights): bool {
                    if ($userId !== 42) {
                        return false;
                    }

                    if ($grantedRights !== null) {
                        return in_array($right, $grantedRights, true);
                    }

                    return $right !== PermissionType::CONFIGURATION_EDIT->value || $allowConfigEdit;
                },
            );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Test User'],
                ['email',        'test@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(AdminMenuBuilder::class);
        $adminHelper->method('canAccessContent')->willReturn(true);
        $adminHelper->method('addMenuEntry')->willReturn('');

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog, $adminHelper) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    'phpmyfaq.admin.helper' => $adminHelper,
                    default => null,
                };
            });

        return $container;
    }

    private function overrideConfigurationValues(array $values): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $config = $configProperty->getValue($this->configuration);
        self::assertIsArray($config);
        $configProperty->setValue($this->configuration, array_merge($config, $values));
    }
}
