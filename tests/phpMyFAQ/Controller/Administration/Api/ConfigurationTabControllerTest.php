<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Http\UrlSafetyValidator;
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

    /** @var list<string> */
    private array $temporaryFiles = [];
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

        foreach ($this->temporaryFiles as $temporaryFile) {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        $this->temporaryFiles = [];

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

    private function createControllerWithUrlValidator(UrlSafetyValidator $validator): ConfigurationTabController
    {
        return new ConfigurationTabController(
            $this->createStub(Language::class),
            $this->createStub(System::class),
            $this->createStub(ThemeManager::class),
            $validator,
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
            'layout mode' => ['layoutMode', 'dark', 'value="dark" selected'],
            'cache adapter' => ['cacheAdapter', 'redis', 'value="redis" selected'],
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

        $request = new Request([], ['theme-csrf-token' => $csrfToken]);
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
        $this->temporaryFiles[] = $archive;
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
            ['theme-csrf-token' => $csrfToken, 'themeName' => 'custom-theme'],
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
        $this->temporaryFiles[] = $archive;
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
            ['theme-csrf-token' => $csrfToken, 'themeName' => 'broken-theme'],
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
        // Checkbox values are normalised to 'true'/'false', and enabling Markdown disables WYSIWYG
        self::assertTrue($this->configuration->get('main.enableMarkdownEditor'));
        self::assertFalse($this->configuration->get('main.enableWysiwygEditor'));
        self::assertSame($originalReferenceUrl, $this->configuration->get('main.referenceURL'));
        self::assertFalse((bool) $this->configuration->get('security.enableRegistration'));
        self::assertTrue((bool) $this->configuration->get('main.maintenanceMode'));
        self::assertTrue((bool) $this->configuration->get('security.enableLoginOnly'));
        self::assertTrue((bool) $this->configuration->get('ldap.ldapSupport'));
        self::assertTrue((bool) $this->configuration->get('security.ssoSupport'));
        $this->removeCsrfCookie('configuration');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidUrlConfigurationProvider(): iterable
    {
        yield 'libretranslate on link-local host' => ['translation.libreTranslateUrl', 'http://metadata.example.test/'];
        yield 'libretranslate on unspecified address' => ['translation.libreTranslateUrl', 'http://0.0.0.0:5000'];
        yield 'libretranslate with wrong scheme' => ['translation.libreTranslateUrl', 'ftp://public.example.test'];
        yield 'keycloak on metadata endpoint' => ['keycloak.baseUrl', 'http://169.254.169.254/'];
        yield 'keycloak redirect with javascript' => ['keycloak.redirectUri', 'javascript:alert(1)'];
        yield 'keycloak logout redirect protocol relative' => ['keycloak.logoutRedirectUrl', '//evil.example.test'];
        yield 'session redis with http' => ['session.redisDsn', 'http://redis:6379'];
        yield 'storage redis without host' => ['storage.redisDsn', 'redis://'];
        yield 'cache redis garbage' => ['storage.cacheRedisDsn', 'redis'];
        yield 'media hosts with scheme' => ['records.allowedMediaHosts', 'https://www.youtube.com'];
        yield 'media hosts with markup' => ['records.allowedMediaHosts', "youtube.com'><script>"];
    }

    /**
     * @throws \Exception
     */
    #[DataProvider('invalidUrlConfigurationProvider')]
    public function testSaveRejectsInvalidUrlValues(string $key, string $value): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('configuration');
        $this->setCsrfCookie('configuration', $csrfToken);

        $validator = new UrlSafetyValidator(static fn(string $host): array => match ($host) {
            'public.example.test' => ['93.184.216.34'],
            'metadata.example.test' => ['169.254.169.254'],
            default => [],
        });
        $controller = $this->createControllerWithUrlValidator($validator);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $originalValue = $this->configuration->get($key);

        $request = new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'availableFields' => json_encode([$key], JSON_THROW_ON_ERROR),
            'edit' => [$key => $value],
        ]);

        $response = $controller->save($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(sprintf(Translation::get('msgInvalidConfigurationUrl'), $key), $payload['error']);
        self::assertSame($originalValue, $this->configuration->get($key));
        $this->removeCsrfCookie('configuration');
    }

    /**
     * @throws \Exception
     */
    public function testSaveAcceptsValidUrlValues(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('configuration');
        $this->setCsrfCookie('configuration', $csrfToken);

        $validator = new UrlSafetyValidator(static fn(string $host): array => match ($host) {
            'translate.example.test', 'sso.example.test' => ['93.184.216.34'],
            default => [],
        });
        $controller = $this->createControllerWithUrlValidator($validator);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $values = [
            'translation.libreTranslateUrl' => 'https://translate.example.test',
            'keycloak.baseUrl' => 'https://sso.example.test/realms/faq',
            'keycloak.redirectUri' => 'http://localhost/faq/keycloak/callback',
            'keycloak.logoutRedirectUrl' => '',
            'session.redisDsn' => 'tcp://redis:6379?database=0',
            'storage.redisDsn' => 'rediss://cache.example.test:6380',
            'storage.cacheRedisDsn' => 'unix:///var/run/redis.sock',
            'records.allowedMediaHosts' => 'www.youtube.com, player.vimeo.com',
        ];

        $request = new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'availableFields' => json_encode(array_keys($values), JSON_THROW_ON_ERROR),
            'edit' => $values,
        ]);

        $response = $controller->save($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        self::assertSame('https://translate.example.test', $this->configuration->get('translation.libreTranslateUrl'));
        self::assertSame('www.youtube.com, player.vimeo.com', $this->configuration->get('records.allowedMediaHosts'));
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

    /**
     * @param array<string, string> $edit
     * @param list<string>|null $availableFields
     * @return array<string, mixed>
     * @throws \Exception
     */
    private function saveConfiguration(array $edit, ?array $availableFields = null): array
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('configuration');
        $this->setCsrfCookie('configuration', $csrfToken);

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $post = ['pmf-csrf-token' => $csrfToken, 'edit' => $edit];
        if ($availableFields !== null) {
            $post['availableFields'] = json_encode($availableFields, JSON_THROW_ON_ERROR);
        }

        $response = $controller->save(new Request([], $post));
        $this->removeCsrfCookie('configuration');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return $this->configuration->getAll();
    }

    /**
     * @throws \Exception
     */
    public function testUncheckedCheckboxIsPersistedAsFalseWithoutAvailableFields(): void
    {
        $this->configuration->update(['main.enableNotifications' => 'true', 'security.enableRegistration' => 'true']);

        // The form sends the hidden "false" value of an unchecked checkbox itself,
        // so persistence must not depend on the JavaScript-generated availableFields list.
        $stored = $this->saveConfiguration(['main.enableNotifications' => 'false']);

        self::assertSame('false', $stored['main.enableNotifications']);
        self::assertSame('true', $stored['security.enableRegistration'], 'Fields from other tabs are untouched');
    }

    /**
     * @throws \Exception
     */
    public function testCheckedCheckboxIsPersistedAsTrue(): void
    {
        $stored = $this->saveConfiguration([
            'main.enableMarkdownEditor' => 'true',
            'main.enableNotifications' => 'true',
        ]);

        self::assertSame('true', $stored['main.enableNotifications']);
        self::assertSame('true', $stored['main.enableMarkdownEditor']);
    }

    /**
     * @throws \Exception
     */
    public function testLegacyTruthyCheckboxValueIsNormalisedToTrue(): void
    {
        $stored = $this->saveConfiguration(['main.enableNotifications' => '1']);

        self::assertSame('true', $stored['main.enableNotifications']);
    }

    /**
     * @throws \Exception
     */
    public function testMissingCheckboxListedInAvailableFieldsIsPersistedAsFalse(): void
    {
        $this->configuration->update(['main.enableNotifications' => 'true']);

        $stored = $this->saveConfiguration(['main.titleFAQ' => 'New title'], [
            'main.titleFAQ',
            'main.enableNotifications',
        ]);

        self::assertSame('false', $stored['main.enableNotifications']);
        self::assertSame('New title', $stored['main.titleFAQ']);
    }

    /**
     * @throws \Exception
     */
    public function testMissingCheckboxWithLegacyStoredValueIsPersistedAsFalse(): void
    {
        $this->configuration->update(['main.enableNotifications' => '1']);

        $stored = $this->saveConfiguration(['main.titleFAQ' => 'New title'], [
            'main.titleFAQ',
            'main.enableNotifications',
        ]);

        self::assertSame('false', $stored['main.enableNotifications']);
    }

    /**
     * @throws \Exception
     */
    public function testMissingNonCheckboxFieldInAvailableFieldsKeepsStoredValue(): void
    {
        // A non-checkbox field with a "truthy looking" stored value must never be reset.
        $this->configuration->update(['records.numberOfRecordsPerPage' => '1']);

        $stored = $this->saveConfiguration(['main.titleFAQ' => 'New title'], [
            'main.titleFAQ',
            'records.numberOfRecordsPerPage',
        ]);

        self::assertSame('1', $stored['records.numberOfRecordsPerPage']);
    }

    /**
     * @throws \Exception
     */
    public function testEnablingMarkdownEditorDisablesWysiwygEditor(): void
    {
        $stored = $this->saveConfiguration([
            'main.enableMarkdownEditor' => 'true',
            'main.enableWysiwygEditor' => 'true',
        ]);

        self::assertSame('true', $stored['main.enableMarkdownEditor']);
        self::assertSame('false', $stored['main.enableWysiwygEditor']);
    }

    /**
     * @throws \Exception
     */
    public function testSubmittedFalseMarkdownEditorDoesNotDisableWysiwygEditor(): void
    {
        // Regression: the hidden "false" value must not be mistaken for "Markdown enabled".
        $stored = $this->saveConfiguration([
            'main.enableMarkdownEditor' => 'false',
            'main.enableWysiwygEditor' => 'true',
        ]);

        self::assertSame('false', $stored['main.enableMarkdownEditor']);
        self::assertSame('true', $stored['main.enableWysiwygEditor']);
    }

    private function setCsrfCookie(string $page, string $token): void
    {
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;
    }

    private function removeCsrfCookie(string $page): void
    {
        unset($_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)]);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function convertedValueProvider(): iterable
    {
        yield 'null' => [null, 'null'];
        yield 'boolean' => [true, '1'];
        yield 'integer' => [42, '42'];
        yield 'string' => ['value', 'value'];
        yield 'object' => [new \stdClass(), 'stdClass'];
        yield 'array' => [['a'], 'array'];
    }

    #[DataProvider('convertedValueProvider')]
    public function testConvertToStringRendersEveryValueTypeForTheLog(mixed $value, string $expected): void
    {
        $controller = $this->createController();

        self::assertSame(
            $expected,
            new \ReflectionMethod(ConfigurationTabController::class, 'convertToString')->invoke($controller, $value),
        );
    }

    /**
     * @throws \Exception
     */
    public function testSaveDropsAnAttachmentsPathThatDoesNotExist(): void
    {
        $before = (string) $this->configuration->get('records.attachmentsPath');

        $stored = $this->saveConfiguration(['records.attachmentsPath' => '/no/such/directory/anywhere']);

        self::assertSame($before, $stored['records.attachmentsPath']);
    }

    /**
     * @throws \Exception
     */
    public function testSaveStoresAnExistingAttachmentsPathRelativeToTheDocumentRoot(): void
    {
        $documentRoot = (string) realpath(sys_get_temp_dir()) . '/pmf-docroot-' . bin2hex(random_bytes(4));
        mkdir($documentRoot . '/content/attachments', 0o755, true);
        $previousDocumentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

        try {
            // A path with a redundant segment, so realpath() has something to normalise.
            $stored = $this->saveConfiguration([
                'records.attachmentsPath' => $documentRoot . '/content/./attachments',
            ]);
        } finally {
            if ($previousDocumentRoot === null) {
                unset($_SERVER['DOCUMENT_ROOT']);
            } else {
                $_SERVER['DOCUMENT_ROOT'] = $previousDocumentRoot;
            }

            rmdir($documentRoot . '/content/attachments');
            rmdir($documentRoot . '/content');
            rmdir($documentRoot);
        }

        self::assertSame('content/attachments', $stored['records.attachmentsPath']);
    }

    /**
     * @throws \Exception
     */
    public function testSaveDropsAReferenceUrlThatIsNotAUrl(): void
    {
        $before = (string) $this->configuration->get('main.referenceURL');

        $stored = $this->saveConfiguration(['main.referenceURL' => 'not a url']);

        self::assertSame($before, $stored['main.referenceURL']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function maintenanceModeProvider(): iterable
    {
        yield 'enabled' => ['false', 'true', 'system-maintenance-mode-enabled'];
        yield 'disabled' => ['true', 'false', 'system-maintenance-mode-disabled'];
    }

    /**
     * @throws \Exception
     */
    #[DataProvider('maintenanceModeProvider')]
    public function testSaveLogsMaintenanceModeChanges(string $before, string $after, string $expectedLogEntry): void
    {
        $this->configuration->update(['main.maintenanceMode' => $before]);

        $logged = [];
        $adminLog = $this->createStub(AdminLog::class);
        $adminLog->method('log')->willReturnCallback(static function ($user, string $message) use (&$logged): bool {
            $logged[] = $message;

            return true;
        });

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('configuration');
        $this->setCsrfCookie('configuration', $csrfToken);

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainerWithAdminLog($adminLog, $session));

        $response = $controller->save(new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'edit' => ['main.maintenanceMode' => $after],
        ]));
        $this->removeCsrfCookie('configuration');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($after, $this->configuration->getAll()['main.maintenanceMode']);
        self::assertContains($expectedLogEntry, $logged);
        self::assertContains('config-change:main.maintenanceMode', $logged);
    }
}
