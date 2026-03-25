<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Date;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Visits;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(StatisticsSessionsController::class)]
#[UsesNamespace('phpMyFAQ')]
final class StatisticsSessionsControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;
    private string $trackingFile;

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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-statistics-sessions-controller-');
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

        $_SERVER['REQUEST_TIME'] = 1_735_689_600;
        $this->trackingFile = PMF_CONTENT_DIR . '/core/data/tracking01012025';
        file_put_contents(
            $this->trackingFile,
            "123;/faq;FAQ Title;127.0.0.1;x;https://ref.example.test/path?foo=bar;Firefox/1.0;1735689600\n",
        );
    }

    protected function tearDown(): void
    {
        @unlink($this->trackingFile);
        unset($_SERVER['REQUEST_TIME']);

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

    /**
     * @throws \Exception
     */
    public function testViewDayRendersSessionsForPayloadDay(): void
    {
        $adminSession = $this->createMock(AdminSession::class);
        $adminSession
            ->expects($this->once())
            ->method('getSessionsByDate')
            ->willReturn([
                123 => [
                    'ip' => '127.0.0.1',
                    'time' => 1_735_689_600,
                ],
            ]);

        $controller = new StatisticsSessionsController(
            $adminSession,
            new Date($this->configuration),
            $this->createStub(Visits::class),
        );
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->viewDay(new Request([], ['day' => '1735689600']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('127.0.0.1', (string) $response->getContent());
        self::assertStringContainsString('./statistics/session/123', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testViewDayFallsBackToDateAttributeWhenPayloadDayIsMissing(): void
    {
        $adminSession = $this->createMock(AdminSession::class);
        $adminSession
            ->expects($this->once())
            ->method('getSessionsByDate')
            ->willReturn([
                456 => [
                    'ip' => '10.0.0.2',
                    'time' => 1_735_689_600,
                ],
            ]);

        $controller = new StatisticsSessionsController(
            $adminSession,
            new Date($this->configuration),
            $this->createStub(Visits::class),
        );
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->viewDay(new Request([], [], ['date' => '2024-12-28']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('10.0.0.2', (string) $response->getContent());
        self::assertStringContainsString('2024-12-28', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testViewSessionRendersTrackingDetails(): void
    {
        $adminSession = $this->createMock(AdminSession::class);
        $adminSession->expects($this->once())->method('getTimeFromSessionId')->with(123)->willReturn(1_735_689_600);

        $controller = new StatisticsSessionsController(
            $adminSession,
            new Date($this->configuration),
            $this->createStub(Visits::class),
        );
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->viewSession(new Request([], [], ['sessionId' => '123']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Firefox/1.0', (string) $response->getContent());
        self::assertStringContainsString('127.0.0.1', (string) $response->getContent());
        self::assertStringContainsString('https://ref.example.test/path? foo=bar', (string) $response->getContent());
    }

    private function createControllerContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 42
                && in_array(
                    $right,
                    [PermissionType::STATISTICS_VIEWLOGS, PermissionType::STATISTICS_VIEWLOGS->value],
                    true,
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
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
}
