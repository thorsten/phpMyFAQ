<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Seo;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractFrontController::class)]
#[UsesNamespace('phpMyFAQ')]
final class AbstractFrontControllerRenderTest extends TestCase
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

        Strings::init('en');
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-front-abstract-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $this->configuration);

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

    /**
     * @throws \Exception
     */
    public function testHeaderReturnsSharedTemplateDataForLoggedInUser(): void
    {
        $controller = new AbstractFrontControllerRenderTestStub();
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('success', 'Saved');
        $session->getFlashBag()->add('error', 'Failed');
        $controller->setContainer($this->createControllerContainer($session, [
            'main.maintenanceMode' => true,
            'security.enableLoginOnly' => true,
            'seo.enableRichSnippets' => true,
            'layout.enableCookieConsent' => true,
            'security.enableRegistration' => true,
            'main.enableAskQuestions' => true,
            'layout.enablePrivacyLink' => true,
            'main.termsURL' => 'https://example.com/terms',
            'main.imprintURL' => 'https://example.com/imprint',
            'main.accessibilityStatementURL' => 'https://example.com/a11y',
            'push.enableWebPush' => 'true',
            'push.vapidPublicKey' => 'public-key',
        ], true, true));

        $request = Request::create('https://localhost/index.php?action=show');
        $header = $controller->fetchHeader($request);

        self::assertSame('Saved', $header['successMessage']);
        self::assertSame('Failed', $header['errorMessage']);
        self::assertTrue($header['isMaintenanceMode']);
        self::assertTrue($header['isCompletelySecured']);
        self::assertTrue($header['isUserLoggedIn']);
        self::assertTrue($header['isUserHasAdminRights']);
        self::assertSame(['plugin.css'], $header['pluginStylesheets']);
        self::assertSame(['plugin.js'], $header['pluginScripts']);
        self::assertSame('show', $header['action']);
        self::assertSame('./search', $header['formActionUrl']);
        self::assertTrue($header['isPrivacyLinkEnabled']);
        self::assertTrue($header['isTermsLinkEnabled']);
        self::assertTrue($header['isImprintLinkEnabled']);
        self::assertTrue($header['isAccessibilityStatementEnabled']);
        self::assertTrue($header['pushEnabled']);
        self::assertSame('./show-categories.html', $header['topNavigation'][0]['link']);
        self::assertSame('active', $header['topNavigation'][0]['active']);
        self::assertSame('./contact.html', $header['footerNavigation'][3]['link']);
        self::assertArrayHasKey('csrfLogout', $header);
    }

    /**
     * @throws \Exception
     */
    public function testHeaderOmitsUserDropdownForGuests(): void
    {
        $controller = new AbstractFrontControllerRenderTestStub();
        $controller->setContainer($this->createControllerContainer(new Session(new MockArraySessionStorage()), [
            'push.enableWebPush' => false,
            'push.vapidPublicKey' => '',
        ], false, false));

        $request = Request::create('https://localhost/index.php?action=index');
        $header = $controller->fetchHeader($request);

        self::assertFalse($header['isUserLoggedIn']);
        self::assertFalse($header['isUserHasAdminRights']);
        self::assertFalse($header['pushEnabled']);
        self::assertSame('index', $header['action']);
        self::assertArrayNotHasKey('csrfLogout', $header);
        self::assertSame('Login', $header['msgLoginUser']);
    }

    /**
     * @throws \Exception
     */
    public function testHandleStaticPageRedirectReturnsConfiguredExternalUrl(): void
    {
        $controller = new AbstractFrontControllerRenderTestStub();
        $controller->setContainer($this->createControllerContainer(
            new Session(new MockArraySessionStorage()),
            ['main.privacyURL' => 'https://example.com/privacy'],
            false,
            false
        ));

        $response = $controller->redirectStaticPage('main.privacyURL');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://example.com/privacy', $response->getTargetUrl());
    }

    /**
     * @throws \Exception
     */
    public function testHandleStaticPageRedirectReturns404WhenNoUrlIsConfigured(): void
    {
        $controller = new AbstractFrontControllerRenderTestStub();
        $controller->setContainer($this->createControllerContainer(
            new Session(new MockArraySessionStorage()),
            ['main.privacyURL' => ''],
            false,
            false
        ));

        $response = $controller->redirectStaticPage('main.privacyURL');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertStringContainsString('Error 404', (string) $response->getContent());
    }

    private function createControllerContainer(
        Session $session,
        array $configurationValues,
        bool $isLoggedIn,
        bool $hasAdminPermission
    ): ContainerInterface {
        $this->overrideConfigurationValues($configurationValues);

        $pluginManager = $this->createStub(PluginManager::class);
        $pluginManager->method('getAllPluginStylesheets')->willReturn(['plugin.css']);
        $pluginManager->method('getAllPluginScripts')->willReturn(['plugin.js']);
        $this->forcePluginManager($pluginManager);

        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn($hasAdminPermission);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn($isLoggedIn);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser->method('getUserId')->willReturn($isLoggedIn ? 1 : -1);
        $currentUser->method('getLogin')->willReturn('testuser');
        $currentUser->method('getUserData')->willReturnMap([
            ['display_name', $isLoggedIn ? 'Test User' : ''],
            ['email', 'test@example.com'],
        ]);

        $system = new System();
        $seo = new Seo($this->configuration);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(
            function (string $id) use ($currentUser, $session, $system, $seo): mixed {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.system' => $system,
                    'phpmyfaq.seo' => $seo,
                    default => null,
                };
            }
        );

        return $container;
    }

    private function overrideConfigurationValues(array $configurationValues): void
    {
        $this->configuration->getAll();

        $reflection = new \ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('config');
        $config = $property->getValue($this->configuration);

        foreach ($configurationValues as $key => $value) {
            $config[$key] = $value;
        }

        $property->setValue($this->configuration, $config);
    }

    private function forcePluginManager(PluginManager $pluginManager): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('config');
        $config = $property->getValue($this->configuration);
        $config['core.pluginManager'] = $pluginManager;
        $property->setValue($this->configuration, $config);
    }
}

final class AbstractFrontControllerRenderTestStub extends AbstractFrontController
{
    public function __construct()
    {
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    public function fetchHeader(Request $request): array
    {
        return $this->getHeader($request);
    }

    /**
     * @throws \Exception
     */
    public function redirectStaticPage(string $configKey): Response
    {
        return $this->handleStaticPageRedirect($configKey);
    }
}
