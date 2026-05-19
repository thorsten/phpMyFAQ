<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

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
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-dashboard-controller-');
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

    private CacheItemPoolInterface $cache;

    private function createController(): DashboardController
    {
        $this->cache = new ArrayAdapter();
        return new DashboardController($this->createStub(AdminSession::class), $this->cache);
    }

    private function createControllerWithSession(AdminSession $adminSession): DashboardController
    {
        $this->cache = new ArrayAdapter();
        return new DashboardController($adminSession, $this->cache);
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
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

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    default => null,
                };
            });

        return $container;
    }

    /**
     * @throws \Exception
     */
    public function testVerifyRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], '{}');
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->verify($request);
    }

    /**
     * @throws \Exception
     */
    public function testVersionsRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->versions();
    }

    /**
     * @throws \Exception
     */
    public function testVisitsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->visits($request);
    }

    /**
     * @throws \Exception
     */
    public function testTopTenRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->topTen();
    }

    /**
     * @throws \Exception
     */
    public function testVisitsReturnsBadRequestWhenUserTrackingIsDisabled(): void
    {
        $this->configuration->set('main.enableUserTracking', 'false');
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->visits(new Request(server: ['REQUEST_TIME' => time()]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('User tracking is disabled.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testVisitsReturnsDataWhenUserTrackingIsEnabled(): void
    {
        $this->configuration->set('main.enableUserTracking', 'true');
        $adminSession = $this->createMock(AdminSession::class);
        $adminSession
            ->expects($this->once())
            ->method('getVisitsForDays')
            ->with(1234567890, 30)
            ->willReturn(['visits' => 5]);

        $controller = $this->createControllerWithSession($adminSession);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->visits(new Request(server: ['REQUEST_TIME' => 1234567890]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(['visits' => 5], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testVisitsClampsAndForwardsTheRequestedRange(): void
    {
        $this->configuration->set('main.enableUserTracking', 'true');
        $adminSession = $this->createMock(AdminSession::class);
        // requested 9999 days is clamped to the 365-day maximum
        $adminSession->expects($this->once())->method('getVisitsForDays')->with(1234567890, 365)->willReturn([]);

        $controller = $this->createControllerWithSession($adminSession);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->visits(new Request(query: ['days' => '9999'], server: ['REQUEST_TIME' => 1234567890]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testSearchesRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->searches();
    }

    /**
     * @throws \Exception
     */
    public function testSearchesReturnsArrayForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->searches();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($payload);
    }

    /**
     * @throws \Exception
     */
    public function testContentHealthRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->contentHealth();
    }

    /**
     * @throws \Exception
     */
    public function testContentHealthReturnsCounters(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->contentHealth();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('orphaned', $payload);
        self::assertArrayHasKey('stale', $payload);
        self::assertIsInt($payload['orphaned']);
        self::assertIsInt($payload['stale']);
    }

    /**
     * @throws \Exception
     */
    public function testTopTenReturnsBadRequestWhenUserTrackingIsDisabled(): void
    {
        $this->configuration->set('main.enableUserTracking', 'false');
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->topTen();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('User tracking is disabled.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testTopTenReturnsDataWhenUserTrackingIsEnabled(): void
    {
        $this->configuration->set('main.enableUserTracking', 'true');
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->topTen();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($payload);
    }

    /**
     * @throws \Exception
     */
    public function testVersionsReturnsBadRequestWhenVersionLookupFails(): void
    {
        $this->configuration->set('upgrade.releaseEnvironment', 'non-existent-release-channel');
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->versions();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
        self::assertIsString($payload['error']);
        self::assertNotSame('', trim($payload['error']));
    }

    /**
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testVersionsReturnsFreshCachedPayloadWithoutRemoteLookup(): void
    {
        $this->configuration->set('upgrade.releaseEnvironment', 'stable');
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $cachedPayload = ['success' => 'phpMyFAQ 4.2.0'];
        $item = $this->cache->getItem('dashboard.versions.stable');
        $item->set(['fetchedAt' => time(), 'payload' => $cachedPayload]);
        $this->cache->save($item);

        $response = $controller->versions();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($cachedPayload, $payload);
    }

    /**
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testVersionsServesStaleCacheWhenLookupFails(): void
    {
        $this->configuration->set('upgrade.releaseEnvironment', 'non-existent-release-channel');
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $stalePayload = ['success' => 'phpMyFAQ 4.1.0'];
        $item = $this->cache->getItem('dashboard.versions.non-existent-release-channel');
        // fetchedAt far in the past → stale, but still retained for fallback
        $item->set(['fetchedAt' => time() - 100_000, 'payload' => $stalePayload]);
        $this->cache->save($item);

        $response = $controller->versions();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($stalePayload, $payload);
    }

    /**
     * @throws \Exception
     * @throws \JsonException
     */
    public function testVerifyReturnsJsonForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->verify(new Request([], [], [], [], [], [], '{}'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($payload);
    }

    /**
     * @throws \Exception
     */
    public function testGetLayoutRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->getLayout();
    }

    /**
     * @throws \Exception
     */
    public function testGetLayoutReturnsConfigKey(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->getLayout();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('config', $payload);
        self::assertIsArray($payload['config']);
    }

    /**
     * @throws \Exception
     */
    public function testSaveLayoutRejectsMissingCsrfToken(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], [], [], [], [], json_encode(['config' => []], JSON_THROW_ON_ERROR));
        $response = $controller->saveLayout($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testSaveLayoutRejectsInvalidBody(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->saveLayout(new Request([], [], [], [], [], [], 'not-json'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testResetLayoutRejectsMissingCsrfToken(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->resetLayout(new Request([], [], [], [], [], [], '{}'));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testNewsRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->news();
    }

    /**
     * @throws \Exception
     */
    public function testNewsReturnsForbiddenWhenDisabled(): void
    {
        $this->configuration->set('main.enableRecentNews', 'false');
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->news();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Recent news is disabled.', $payload['error']);
    }
}
