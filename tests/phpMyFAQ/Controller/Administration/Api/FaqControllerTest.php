<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Administration\Faq as FaqAdministration;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Push\WebPushService;
use phpMyFAQ\Question;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Visits;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
final class FaqControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-faq-controller-');
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

    private function createController(): FaqController
    {
        return new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(FaqAdministration::class),
            $this->createStub(Tags::class),
            $this->createStub(Notification::class),
            $this->createStub(Changelog::class),
            $this->createStub(Visits::class),
            $this->createStub(Seo::class),
            $this->createStub(Question::class),
            $this->createStub(AdminLog::class),
            $this->createStub(WebPushService::class),
        );
    }

    private function createControllerWithAdminFaq(FaqAdministration $adminFaq): FaqController
    {
        return new FaqController(
            $this->createStub(Faq::class),
            $adminFaq,
            $this->createStub(Tags::class),
            $this->createStub(Notification::class),
            $this->createStub(Changelog::class),
            $this->createStub(Visits::class),
            $this->createStub(Seo::class),
            $this->createStub(Question::class),
            $this->createStub(AdminLog::class),
            $this->createStub(WebPushService::class),
        );
    }

    private function createAuthenticatedContainer(?Session $session = null): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::FAQ_ADD->value,
                        PermissionType::FAQ_EDIT->value,
                        PermissionType::FAQ_DELETE->value,
                        PermissionType::FAQ_APPROVE->value,
                        PermissionType::FAQ_TRANSLATE->value,
                    ],
                    true,
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

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
    public function testCreateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => 'test-token',
            ],
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsRequiresAuthentication(): void
    {
        $request = new Request([], [], ['faqId' => 1]);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->listPermissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testListByCategoryRequiresAuthentication(): void
    {
        $request = new Request([], [], ['categoryId' => 1]);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->listByCategory($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testStickyRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->sticky($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testSearchRequiresAuthentication(): void
    {
        $request = new Request(['search' => 'foo']);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->search($request);
    }

    /**
     * @throws \Exception
     */
    public function testSaveOrderOfStickyFaqsRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['faqIds' => [1, 2]], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->saveOrderOfStickyFaqs($request);
    }

    /**
     * @throws \Exception
     */
    public function testImportRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->import($request);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsReturnsPermissionArraysForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listPermissions(new Request([], [], ['faqId' => 1]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('user', $payload);
        self::assertArrayHasKey('group', $payload);
        self::assertIsArray($payload['user']);
        self::assertIsArray($payload['group']);
    }

    /**
     * @throws \Exception
     */
    public function testListByCategoryReturnsFaqsForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listByCategory(new Request([], [], ['categoryId' => 1, 'language' => 'en']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('faqs', $payload);
        self::assertArrayHasKey('isAllowedToTranslate', $payload);
        self::assertIsArray($payload['faqs']);
        self::assertTrue($payload['isAllowedToTranslate']);
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->activate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->sticky($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqId' => 1,
            'faqLanguage' => 'en',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->delete($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('CSRF Token - ' . Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'search' => 'admin',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->search($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testSaveOrderOfStickyFaqsReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqIds' => [1, 2],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->saveOrderOfStickyFaqs($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsBadRequestWhenFaqIdsAreMissingWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->activate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('No FAQ IDs provided.', $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsBadRequestWhenFaqIdsAreMissingWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->sticky($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('No FAQ IDs provided.', $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsBadRequestWhenLanguageIsUnsupportedWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'zz',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->activate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsBadRequestWhenLanguageIsUnsupportedWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'zz',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->sticky($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsSuccessWhenSearchStringIsNullWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'search' => null,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->search($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testSaveOrderOfStickyFaqsReturnsSuccessWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('order-stickyfaqs');
        $this->setCsrfCookie('order-stickyfaqs', $csrfToken);

        $adminFaq = $this->createMock(FaqAdministration::class);
        $adminFaq->expects($this->once())->method('setStickyFaqOrder')->with([5, 3, 1]);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [5, 3, 1],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithAdminFaq($adminFaq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->saveOrderOfStickyFaqs($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        $this->removeCsrfCookie('order-stickyfaqs');
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsBadRequestWhenNoFileSubmittedForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->import(new Request());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Bad request: There is no file submitted.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsUnauthorizedForInvalidCsrfWithSubmittedFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-faq-import-');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, "question,answer\nQ,A\n");
        $uploadedFile = new UploadedFile($tempFile, 'faq.csv', null, null, true);

        $request = new Request([], [], ['csrf' => 'invalid-token'], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsBadRequestWhenSubmittedFileIsNotCsv(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('importfaqs');
        $this->setCsrfCookie('importfaqs', $csrfToken);

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-faq-import-');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, 'not a csv import');
        $uploadedFile = new UploadedFile($tempFile, 'faq.txt', null, null, true);

        $request = new Request([], [], ['csrf' => $csrfToken], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Bad request: The file is not a CSV file.', $payload['error']);
        $this->removeCsrfCookie('importfaqs');
    }

    private function setCsrfCookie(string $page, string $token): void
    {
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;
    }

    private function removeCsrfCookie(string $page): void
    {
        unset($_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)]);
    }
}
