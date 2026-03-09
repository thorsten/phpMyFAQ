<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Translation\ContentTranslationService;
use phpMyFAQ\Translation\DTO\TranslationResult;
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
#[CoversClass(TranslationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class TranslationControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-translation-controller-');
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

    private function createController(): TranslationController
    {
        return new TranslationController($this->createStub(ContentTranslationService::class));
    }

    private function createControllerWithService(ContentTranslationService $translationService): TranslationController
    {
        return new TranslationController($translationService);
    }

    private function createAuthenticatedContainer(?Session $session = null): ContainerInterface
    {
        return $this->createAuthenticatedContainerWithAdminLog($this->createStub(AdminLog::class), $session);
    }

    private function createAuthenticatedContainerWithAdminLog(
        AdminLog $adminLog,
        ?Session $session = null,
    ): ContainerInterface {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => (
                    $userId === 42
                    && $right === PermissionType::FAQ_TRANSLATE->value
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session ??= new Session(new MockArraySessionStorage());

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
    public function testTranslateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'pmf-csrf-token' => 'test-token',
            'contentType' => 'faq',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['question' => 'Test'],
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'pmf-csrf-token' => 'invalid-token',
            'contentType' => 'faq',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['question' => 'Test'],
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->translate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame('CSRF - ' . Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateReturnsBadRequestForMissingParametersWhenAuthenticated(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $token = $this->createValidCsrfToken($session, 'translate');

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));
        $request = new Request([], [], [], [], [], [], json_encode([
            'pmf-csrf-token' => $token,
            'contentType' => '',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => [],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->translate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame('Missing required parameters', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateReturnsBadRequestForInvalidContentTypeWhenAuthenticated(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $token = $this->createValidCsrfToken($session, 'translate');

        $controller = $this->createControllerWithService($this->createStub(ContentTranslationService::class));
        $controller->setContainer($this->createAuthenticatedContainer($session));
        $request = new Request([], [], [], [], [], [], json_encode([
            'pmf-csrf-token' => $token,
            'contentType' => 'invalid',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['question' => 'Test'],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->translate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame('Invalid content type', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateReturnsTranslatedFieldsForSuccessfulFaqTranslation(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $token = $this->createValidCsrfToken($session, 'translate');

        $translationService = $this->createMock(ContentTranslationService::class);
        $translationService->expects($this->once())->method('translateFaq')->willReturn(new TranslationResult([
            'question' => 'Frage',
        ], true));

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog->expects($this->once())->method('log')->with($this->anything(), 'faq-translate:en->de');

        $controller = $this->createControllerWithService($translationService);
        $controller->setContainer($this->createAuthenticatedContainerWithAdminLog($adminLog, $session));
        $request = new Request([], [], [], [], [], [], json_encode([
            'pmf-csrf-token' => $token,
            'contentType' => 'faq',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['question' => 'Question'],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->translate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertSame(['question' => 'Frage'], $payload['translatedFields']);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateReturnsInternalServerErrorWhenTranslationResultFails(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $token = $this->createValidCsrfToken($session, 'translate');

        $translationService = $this->createMock(ContentTranslationService::class);
        $translationService
            ->expects($this->once())
            ->method('translateNews')
            ->willReturn(new TranslationResult([], false, 'Provider unavailable'));

        $controller = $this->createControllerWithService($translationService);
        $controller->setContainer($this->createAuthenticatedContainer($session));
        $request = new Request([], [], [], [], [], [], json_encode([
            'pmf-csrf-token' => $token,
            'contentType' => 'news',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['title' => 'News title'],
        ], JSON_THROW_ON_ERROR));

        $response = $controller->translate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame('Provider unavailable', $payload['error']);
    }
}
