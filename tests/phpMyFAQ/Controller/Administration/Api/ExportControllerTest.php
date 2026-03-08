<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
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

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ExportController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ExportControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-export-controller-');
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
        $_COOKIE = [];

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

    private function createController(): ExportController
    {
        return new ExportController($this->createStub(Faq::class));
    }

    /**
     * @throws \Exception
     */
    public function testExportFileRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->exportFile($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportReportRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => 'test-token',
            ],
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->exportReport($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportReportWithInvalidJsonStillRequiresAuthenticationFirst(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->exportReport($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportReportReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => 'invalid-token',
            ],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->exportReport($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    private function createAuthenticatedContainer(?Session $session = null): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(static function (int $userId, mixed $right): bool {
                return $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::EXPORT->value,
                        PermissionType::REPORTS->value,
                    ],
                    true,
                );
            });

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session ??= new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $adminLog, $session) {
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
    public function testExportReportReturnsCsvForAuthenticatedUserWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $token = Token::getInstance($session)->getTokenString('create-report');
        $_COOKIE['pmf-csrf-token-' . substr(md5('create-report'), 0, 10)] = $token;

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $token,
                'category' => true,
                'title' => true,
                'id' => true,
            ],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->exportReport($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('text/csv', $response->headers->get('Content-Type'));
        self::assertStringContainsString('ID', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testExportFileOutputsErrorMessageForUnsupportedExportType(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [
            'categoryId' => 1,
            'downwards' => 'false',
            'disposition' => 'inline',
            'export-type' => 'unsupported-format',
        ]);

        ob_start();
        $controller->exportFile($request);
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Export not implemented!', $output);
    }

    /**
     * @throws \Exception
     */
    public function testExportReportIncludesUrlAndVisitsWhenRequested(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $token = Token::getInstance($session)->getTokenString('create-report');
        $_COOKIE['pmf-csrf-token-' . substr(md5('create-report'), 0, 10)] = $token;

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $token,
                'category' => true,
                'sub_category' => true,
                'translations' => true,
                'language' => true,
                'id' => true,
                'sticky' => true,
                'title' => true,
                'creation_date' => true,
                'owner' => true,
                'url' => true,
                'visits' => true,
            ],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->exportReport($request);
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('text/csv', $response->headers->get('Content-Type'));
        self::assertStringContainsString('URL,Visits', $content);
    }
}
