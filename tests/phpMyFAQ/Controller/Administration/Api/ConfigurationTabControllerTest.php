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
use phpMyFAQ\System;
use phpMyFAQ\Template\ThemeManager;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ConfigurationTabController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ConfigurationTabControllerTest extends TestCase
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

        if (!defined('PMF_LANGUAGE_DIR')) {
            define('PMF_LANGUAGE_DIR', PMF_TRANSLATION_DIR);
        }

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-config-tab-controller-');
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

    private function createController(): ConfigurationTabController
    {
        return new ConfigurationTabController(
            $this->createStub(Language::class),
            $this->createStub(System::class),
            $this->createStub(ThemeManager::class),
        );
    }

    private function createControllerWithThemeManager(ThemeManager $themeManager): ConfigurationTabController
    {
        return new ConfigurationTabController(
            $this->createStub(Language::class),
            $this->createStub(System::class),
            $themeManager,
        );
    }

    private function createControllerWithLanguage(?Language $language = null): ConfigurationTabController
    {
        return new ConfigurationTabController(
            $language ?? $this->createStub(Language::class),
            $this->createStub(System::class),
            $this->createStub(ThemeManager::class),
        );
    }

    /**
     * @throws \Exception
     */
    public function testListRequiresAuthentication(): void
    {
        $request = new Request([], [], ['mode' => 'security']);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->list($request);
    }

    /**
     * @throws \Exception
     */
    public function testUploadThemeRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->uploadTheme($request);
    }

    /**
     * @throws \Exception
     */
    public function testSaveRequiresAuthentication(): void
    {
        $request = new Request([], ['pmf-csrf-token' => 'test-token']);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->save($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslationsRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->translations();
    }

    /**
     * @throws \Exception
     */
    public function testTemplatesRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->templates();
    }

    /**
     * @throws \Exception
     */
    public function testUploadThemeReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->uploadTheme(new Request());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testListReturnsRenderedConfigurationTabForAuthenticatedUser(): void
    {
        $language = $this->createStub(Language::class);
        $language->method('setLanguageByAcceptLanguage')->willReturn('en');

        $controller = $this->createControllerWithLanguage($language);
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], ['mode' => 'security']);
        $response = $controller->list($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('security.enableLoginOnly', (string) $response->getContent());
        self::assertStringContainsString('data-config-key="security.permLevel"', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testListReturnsRenderedKeycloakConfigurationTabForAuthenticatedUser(): void
    {
        $language = $this->createStub(Language::class);
        $language->method('setLanguageByAcceptLanguage')->willReturn('en');

        $controller = $this->createControllerWithLanguage($language);
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], ['mode' => 'keycloak']);
        $response = $controller->list($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('data-config-key="keycloak.enable"', (string) $response->getContent());
        self::assertStringContainsString('data-config-key="keycloak.clientId"', (string) $response->getContent());
        self::assertStringContainsString(
            'data-config-key="keycloak.groupSyncOnLogin"',
            (string) $response->getContent(),
        );
        self::assertStringContainsString('data-config-key="keycloak.groupMapping"', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testSaveReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->save(new Request([], ['pmf-csrf-token' => 'invalid-token']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testTemplatesReturnsAvailableTemplatesForAuthenticatedUser(): void
    {
        $system = $this->createStub(System::class);
        $system
            ->method('getAvailableTemplates')
            ->willReturn([
                'default' => true,
                'plain' => false,
            ]);

        $controller = new ConfigurationTabController(
            $this->createStub(Language::class),
            $system,
            $this->createStub(ThemeManager::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->templates();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('<option selected>default</option>', (string) $response->getContent());
        self::assertStringContainsString('<option>plain</option>', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testTranslationsReturnsLanguageOptionsForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->translations();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('<option', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    #[DataProvider('helperEndpointProvider')]
    public function testHelperEndpointsReturnRenderedOptionMarkup(
        string $method,
        string $currentValue,
        string $expectedContent,
    ): void {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->$method(new Request([], [], ['current' => $currentValue]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString($expectedContent, (string) $response->getContent());
    }

    public static function helperEndpointProvider(): array
    {
        return [
            'faqs sorting key' => ['faqsSortingKey', 'visits', 'value="visits" selected'],
            'faqs sorting order' => ['faqsSortingOrder', 'DESC', 'value="DESC" selected'],
            'faqs sorting popular' => ['faqsSortingPopular', 'visits', 'value="visits"'],
            'permission level' => ['permLevel', 'medium', 'value="medium" selected'],
            'release environment' => ['releaseEnvironment', 'nightly', 'value="nightly" selected'],
            'search relevance' => [
                'searchRelevance',
                'thema,content,keywords',
                'value="thema,content,keywords"',
            ],
            'seo metatags' => ['seoMetaTags', 'index, follow', '<option selected>index, follow</option>'],
            'translation provider' => ['translationProvider', 'google', 'value="google"'],
            'mail provider' => ['mailProvider', 'smtp', 'value="smtp" selected'],
        ];
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
                    && $right === PermissionType::CONFIGURATION_EDIT->value
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
    public function testUploadThemeReturnsBadRequestForMissingFileWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('theme-manager');
        $this->setCsrfCookie('theme-manager', $csrfToken);

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $request = new Request([], ['pmf-csrf-token' => $csrfToken]);
        $response = $controller->uploadTheme($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('No valid ZIP file uploaded.', $payload['error']);
        $this->removeCsrfCookie('theme-manager');
    }

    /**
     * @throws \Exception
     */
    public function testUploadThemeReturnsSuccessForValidZipUpload(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('theme-manager');
        $this->setCsrfCookie('theme-manager', $csrfToken);

        $archive = tempnam(sys_get_temp_dir(), 'pmf-theme-');
        self::assertNotFalse($archive);
        file_put_contents($archive, 'zip-placeholder');
        $uploadedFile = new UploadedFile($archive, 'my-theme.zip', 'application/zip', null, true);

        $themeManager = $this->createMock(ThemeManager::class);
        $themeManager
            ->expects($this->once())
            ->method('uploadTheme')
            ->with('custom-theme', $uploadedFile->getPathname())
            ->willReturn(5);

        $controller = $this->createControllerWithThemeManager($themeManager);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $request = new Request(
            [],
            ['pmf-csrf-token' => $csrfToken, 'themeName' => 'custom-theme'],
            [],
            [],
            ['themeArchive' => $uploadedFile],
        );
        $response = $controller->uploadTheme($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('Theme "custom-theme" uploaded (5 files).', $payload['success']);
        $this->removeCsrfCookie('theme-manager');
    }

    /**
     * @throws \Exception
     */
    public function testUploadThemeReturnsBadRequestWhenThemeManagerThrows(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('theme-manager');
        $this->setCsrfCookie('theme-manager', $csrfToken);

        $archive = tempnam(sys_get_temp_dir(), 'pmf-theme-');
        self::assertNotFalse($archive);
        file_put_contents($archive, 'zip-placeholder');
        $uploadedFile = new UploadedFile($archive, 'broken-theme.zip', 'application/zip', null, true);

        $themeManager = $this->createMock(ThemeManager::class);
        $themeManager
            ->expects($this->once())
            ->method('uploadTheme')
            ->willThrowException(new \RuntimeException('Theme archive invalid.'));

        $controller = $this->createControllerWithThemeManager($themeManager);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $request = new Request(
            [],
            ['pmf-csrf-token' => $csrfToken, 'themeName' => 'broken-theme'],
            [],
            [],
            ['themeArchive' => $uploadedFile],
        );
        $response = $controller->uploadTheme($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Theme archive invalid.', $payload['error']);
        $this->removeCsrfCookie('theme-manager');
    }

    /**
     * @throws \Exception
     */
    public function testSaveReturnsSuccessWithValidCsrfAndMinimalPayload(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('configuration');
        $this->setCsrfCookie('configuration', $csrfToken);

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $request = new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'availableFields' => json_encode([], JSON_THROW_ON_ERROR),
            'edit' => [],
        ]);

        $response = $controller->save($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        $this->removeCsrfCookie('configuration');
    }

    /**
     * @throws \Exception
     */
    public function testSavePersistsCheckboxAndSecurityConfigurationChanges(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('configuration');
        $this->setCsrfCookie('configuration', $csrfToken);

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog
            ->expects($this->exactly(5))
            ->method('log')
            ->with($this->anything(), $this->callback(static function (string $message): bool {
                static $expectedFragments = [
                    'config-change',
                    'system-maintenance-mode-enabled',
                    'config-security-changed',
                    'config-ldap-changed',
                    'config-sso-changed',
                ];

                $expectedFragment = array_shift($expectedFragments);
                return $expectedFragment !== null && str_contains($message, $expectedFragment);
            }));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainerWithAdminLog($adminLog, $session));

        $originalReferenceUrl = (string) $this->configuration->get('main.referenceURL');

        $request = new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'availableFields' => json_encode([
                'security.enableRegistration',
                'main.enableMarkdownEditor',
                'main.enableWysiwygEditor',
                'main.referenceURL',
                'main.maintenanceMode',
                'security.enableLoginOnly',
                'ldap.ldapSupport',
                'security.ssoSupport',
            ], JSON_THROW_ON_ERROR),
            'edit' => [
                'main.enableMarkdownEditor' => '1',
                'main.enableWysiwygEditor' => 'true',
                'main.referenceURL' => 'not-a-valid-url',
                'main.maintenanceMode' => 'true',
                'security.enableLoginOnly' => 'true',
                'ldap.ldapSupport' => 'true',
                'security.ssoSupport' => 'true',
            ],
        ]);

        $response = $controller->save($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        self::assertSame('1', $this->configuration->get('main.enableMarkdownEditor'));
        self::assertSame('', $this->configuration->get('main.enableWysiwygEditor'));
        self::assertSame($originalReferenceUrl, $this->configuration->get('main.referenceURL'));
        self::assertFalse((bool) $this->configuration->get('security.enableRegistration'));
        self::assertTrue((bool) $this->configuration->get('main.maintenanceMode'));
        self::assertTrue((bool) $this->configuration->get('security.enableLoginOnly'));
        self::assertTrue((bool) $this->configuration->get('ldap.ldapSupport'));
        self::assertTrue((bool) $this->configuration->get('security.ssoSupport'));
        $this->removeCsrfCookie('configuration');
    }

    /**
     * @throws \Exception
     */
    public function testSaveIgnoresInvalidAvailableFieldsJsonAndStillSucceeds(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('configuration');
        $this->setCsrfCookie('configuration', $csrfToken);

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $request = new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'availableFields' => '{invalid-json',
            'edit' => ['main.currentVersion' => '9.9.9'],
        ]);

        $response = $controller->save($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        $this->removeCsrfCookie('configuration');
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
