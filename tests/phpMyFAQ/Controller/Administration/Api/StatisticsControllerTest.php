<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Helper\StatisticsHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Rating;
use phpMyFAQ\Search;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(StatisticsController::class)]
#[UsesNamespace('phpMyFAQ')]
final class StatisticsControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-statistics-controller-');
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
        Token::resetInstanceForTests();
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('sessions'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('clear-visits'), 0, 10)]);

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

    private function createController(
        ?StatisticsHelper $statisticsHelper = null,
        ?Search $search = null,
        ?Rating $rating = null,
    ): StatisticsController
    {
        return new StatisticsController(
            $statisticsHelper ?? $this->createStub(StatisticsHelper::class),
            $search ?? $this->createStub(Search::class),
            $rating ?? $this->createStub(Rating::class),
        );
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturnCallback(
            static fn (int $userId, mixed $right): bool => $userId === 42
                && in_array($right, [PermissionType::STATISTICS_VIEWLOGS, PermissionType::STATISTICS_VIEWLOGS->value], true)
        );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($currentUser, $session) {
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
    private function createValidCsrfToken(Session $session, string $page): string
    {
        Token::resetInstanceForTests();
        $token = Token::getInstance($session)->getTokenString($page);
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;

        return $token;
    }

    /**
     * @throws \Exception
     */
    public function testTruncateSessionsRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->truncateSessions($request);
    }

    /**
     * @throws \Exception
     */
    public function testTruncateSearchTermsRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->truncateSearchTerms($request);
    }

    /**
     * @throws \Exception
     */
    public function testClearRatingsRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->clearRatings($request);
    }

    /**
     * @throws \Exception
     */
    public function testClearVisitsRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->clearVisits($request);
    }

    /**
     * @throws \Exception
     */
    public function testTruncateSessionsWithInvalidJsonStillRequiresAuthenticationFirst(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->truncateSessions($request);
    }

    /**
     * @throws \Exception
     */
    public function testTruncateSessionsReturnsSuccessWhenTrackingFilesAreDeleted(): void
    {
        $statisticsHelper = $this->createMock(StatisticsHelper::class);
        $statisticsHelper->expects($this->once())->method('deleteTrackingFiles')->with('2025-03')->willReturn(true);

        $controller = $this->createController($statisticsHelper);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'sessions');
        $controller->setContainer($container);

        $request = new Request([], ['month' => '2025-03'], [], [], [], [], json_encode([
            'csrfToken' => $token,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->truncateSessions($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_adminlog_delete_success'), $payload['success']);
    }

    /**
     * @throws \Exception
     */
    public function testClearVisitsReturnsBadRequestWhenClearingFails(): void
    {
        $statisticsHelper = $this->createMock(StatisticsHelper::class);
        $statisticsHelper->expects($this->once())->method('clearAllVisits')->willReturn(false);

        $controller = $this->createController($statisticsHelper);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'clear-visits');
        $controller->setContainer($container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->clearVisits($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Cannot clear visits.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testTruncateSearchTermsReturnsSuccessWhenAuthenticated(): void
    {
        $search = $this->createMock(Search::class);
        $search->expects($this->once())->method('deleteAllSearchTerms')->willReturn(true);

        $controller = $this->createController(search: $search);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'truncate-search-terms');
        $controller->setContainer($container);

        $response = $controller->truncateSearchTerms(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_searchterm_del_suc'), $payload['success']);
    }

    /**
     * @throws \Exception
     */
    public function testClearRatingsReturnsSuccessWhenAuthenticated(): void
    {
        $rating = $this->createMock(Rating::class);
        $rating->expects($this->once())->method('deleteAll')->willReturn(true);

        $controller = $this->createController(rating: $rating);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'clear-statistics');
        $controller->setContainer($container);

        $response = $controller->clearRatings(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('msgDeleteAllVotings'), $payload['success']);
    }
}
