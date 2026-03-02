<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

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

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(FormController::class)]
#[UsesNamespace('phpMyFAQ')]
class FormControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-form-controller-');
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

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturnCallback(
            static fn (int $userId, mixed $right): bool => $userId === 42 && $right === PermissionType::FORMS_EDIT->value
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
    public function testActivateInputRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'formid' => 1, 'inputid' => 1, 'checked' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->activateInput($request);
    }

    /**
     * @throws \Exception
     */
    public function testSetInputAsRequiredRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'formid' => 1, 'inputid' => 1, 'checked' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->setInputAsRequired($request);
    }

    /**
     * @throws \Exception
     */
    public function testEditTranslationRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'formId' => 1,
            'inputId' => 1,
            'lang' => 'en',
            'label' => 'Test',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->editTranslation($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteTranslationRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'formId' => 1, 'inputId' => 1, 'lang' => 'en']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->deleteTranslation($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddTranslationRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'formId' => 1,
            'inputId' => 1,
            'lang' => 'en',
            'translation' => 'Test',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->addTranslation($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateInputWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->activateInput($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateInputWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['formid' => 1, 'inputid' => 1, 'checked' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FormController();

        $this->expectException(\Exception::class);
        $controller->activateInput($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateInputReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'formid' => 1,
            'inputid' => 1,
            'checked' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = new FormController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->activateInput($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testSetInputAsRequiredReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'formid' => 1,
            'inputid' => 1,
            'checked' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = new FormController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->setInputAsRequired($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testEditTranslationReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'formId' => 1,
            'inputId' => 1,
            'lang' => 'en',
            'label' => 'Test',
        ], JSON_THROW_ON_ERROR));
        $controller = new FormController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->editTranslation($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteTranslationReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'formId' => 1,
            'inputId' => 1,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR));
        $controller = new FormController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->deleteTranslation($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testAddTranslationReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'formId' => 1,
            'inputId' => 1,
            'lang' => 'en',
            'translation' => 'Test',
        ], JSON_THROW_ON_ERROR));
        $controller = new FormController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->addTranslation($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }
}
