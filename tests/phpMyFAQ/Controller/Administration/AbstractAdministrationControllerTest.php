<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractAdministrationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class AbstractAdministrationControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-abstract-controller-');
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

    public function testSetContainerInitializesAdminLog(): void
    {
        $adminLog = $this->createStub(AdminLog::class);
        $controller = new AbstractAdministrationControllerTestStub();

        $controller->setContainer($this->createControllerContainer($adminLog));

        self::assertSame($adminLog, $controller->getAdminLogInstance());
    }

    /**
     * @throws \Exception
     */
    public function testHeaderBuildsMenuFlagsAndSharedTemplateData(): void
    {
        $controller = new AbstractAdministrationControllerTestStub();
        $controller->setContainer($this->createControllerContainer(configurationValues: [
            'security.permLevel' => 'medium',
            'upgrade.onlineUpdateEnabled' => true,
            'search.enableElasticsearch' => true,
            'search.enableOpenSearch' => true,
            'main.enableGravatarSupport' => false,
            'security.enableAdminSessionTimeoutCounter' => true,
            'seo.metaTagsAdmin' => 'noindex, nofollow',
        ], ldapActive: true));

        $header = $controller->fetchHeader(new Request(attributes: ['_route' => 'admin.group']));

        self::assertSame('noindex, nofollow', $header['metaRobots']);
        self::assertSame($this->configuration->getDefaultUrl() . 'admin/', $header['baseHref']);
        self::assertSame('Admin User', $header['userName']);
        self::assertTrue($header['userPage']);
        self::assertFalse($header['contentPage']);
        self::assertTrue($header['userHasAccessPermission']);
        self::assertTrue($header['isSessionTimeoutCounterEnabled']);
        self::assertSame(['plugin.css'], $header['pluginStylesheets']);
        self::assertSame(['plugin.js'], $header['pluginScripts']);
        self::assertStringContainsString('[group]', $header['secondLevelEntries']['user']);
        self::assertStringContainsString('[update]', $header['secondLevelEntries']['config']);
        self::assertStringContainsString('[elasticsearch]', $header['secondLevelEntries']['config']);
        self::assertStringContainsString('[opensearch]', $header['secondLevelEntries']['config']);
        self::assertStringContainsString('[ldap]', $header['secondLevelEntries']['config']);
    }

    /**
     * @throws \Exception
     */
    public function testHeaderOmitsOptionalEntriesWhenFeaturesAreDisabled(): void
    {
        $controller = new AbstractAdministrationControllerTestStub();
        $controller->setContainer($this->createControllerContainer(configurationValues: [
            'security.permLevel' => 'basic',
            'upgrade.onlineUpdateEnabled' => false,
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
            'main.enableGravatarSupport' => false,
        ], ldapActive: false));

        $header = $controller->fetchHeader(new Request(attributes: ['_route' => 'admin.category']));

        self::assertFalse($header['userPage']);
        self::assertTrue($header['contentPage']);
        self::assertStringNotContainsString('[group]', $header['secondLevelEntries']['user']);
        self::assertStringNotContainsString('[update]', $header['secondLevelEntries']['config']);
        self::assertStringNotContainsString('[elasticsearch]', $header['secondLevelEntries']['config']);
        self::assertStringNotContainsString('[opensearch]', $header['secondLevelEntries']['config']);
        self::assertStringNotContainsString('[ldap]', $header['secondLevelEntries']['config']);
        self::assertSame('', $header['gravatarImage']);
    }

    /**
     * @throws \Exception
     */
    public function testHeaderMarksExportsPageAndBuildsGravatarWhenEnabled(): void
    {
        $controller = new AbstractAdministrationControllerTestStub();
        $controller->setContainer($this->createControllerContainer(configurationValues: [
            'main.enableGravatarSupport' => true,
        ]));

        $header = $controller->fetchHeader(new Request(attributes: ['_route' => 'admin.export']));

        self::assertFalse($header['userPage']);
        self::assertFalse($header['contentPage']);
        self::assertFalse($header['statisticsPage']);
        self::assertTrue($header['exportsPage']);
        self::assertFalse($header['backupPage']);
        self::assertFalse($header['configurationPage']);
        self::assertStringContainsString('gravatar.com', $header['gravatarImage']);
    }

    /**
     * @throws \Exception
     */
    public function testHeaderMarksBackupPage(): void
    {
        $controller = new AbstractAdministrationControllerTestStub();
        $controller->setContainer($this->createControllerContainer());

        $header = $controller->fetchHeader(new Request(attributes: ['_route' => 'admin.backup.restore']));

        self::assertFalse($header['userPage']);
        self::assertFalse($header['contentPage']);
        self::assertFalse($header['statisticsPage']);
        self::assertFalse($header['exportsPage']);
        self::assertTrue($header['backupPage']);
        self::assertFalse($header['configurationPage']);
    }

    /**
     * @throws \Exception
     */
    public function testHeaderMarksConfigurationPage(): void
    {
        $controller = new AbstractAdministrationControllerTestStub();
        $controller->setContainer($this->createControllerContainer());

        $header = $controller->fetchHeader(new Request(attributes: ['_route' => 'admin.system']));

        self::assertFalse($header['userPage']);
        self::assertFalse($header['contentPage']);
        self::assertFalse($header['statisticsPage']);
        self::assertFalse($header['exportsPage']);
        self::assertFalse($header['backupPage']);
        self::assertTrue($header['configurationPage']);
    }

    public function testFooterReturnsExpectedSharedValues(): void
    {
        $controller = new AbstractAdministrationControllerTestStub();
        $controller->setContainer($this->createControllerContainer());

        $footer = $controller->fetchFooter();

        self::assertSame('en', $footer['currentLanguage']);
        self::assertTrue($footer['isUserLoggedIn']);
        self::assertIsInt($footer['currentTimeStamp']);
        self::assertMatchesRegularExpression('/^\d{4}$/', $footer['currentYear']);
        self::assertNotSame('', $footer['msgPoweredBy']);
        self::assertNotSame('', $footer['documentationUrl']);
    }

    private function createControllerContainer(
        ?AdminLog $adminLog = null,
        array $configurationValues = [],
        bool $ldapActive = false,
    ): ContainerInterface {
        $this->overrideConfigurationValues($configurationValues);

        $pluginManager = $this->createStub(PluginManager::class);
        $pluginManager->method('getAllPluginStylesheets')->willReturn(['plugin.css']);
        $pluginManager->method('getAllPluginScripts')->willReturn(['plugin.js']);
        $this->forcePluginManager($pluginManager);

        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('isSuperAdmin')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Admin User'],
                ['email',        'admin@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
        $adminLog ??= $this->createStub(AdminLog::class);
        $adminHelper = $this->createMock(AdminMenuBuilder::class);
        $adminHelper->method('setUser')->willReturnSelf();
        $adminHelper->method('canAccessContent')->willReturn(true);
        $adminHelper
            ->method('addMenuEntry')
            ->willReturnCallback(
                static fn(string $permissionName, string $label, string $route): string => '[' . $route . ']',
            );

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use (
                $currentUser,
                $session,
                $adminLog,
                $adminHelper,
                $ldapActive,
            ): mixed {
                return match ($id) {
                    'phpmyfaq.configuration' => new class($this->configuration, $ldapActive) extends Configuration {
                        public function __construct(
                            private readonly Configuration $inner,
                            private readonly bool $ldapActive,
                        ) {
                        }

                        public function __call(string $name, array $arguments): mixed
                        {
                            return $this->inner->$name(...$arguments);
                        }

                        public function isLdapActive(): bool
                        {
                            return $this->ldapActive;
                        }

                        public function getTemplateSet(): string
                        {
                            return $this->inner->getTemplateSet();
                        }

                        public function get(string $item): mixed
                        {
                            return $this->inner->get($item);
                        }

                        public function getLanguage(): Language
                        {
                            return $this->inner->getLanguage();
                        }

                        public function getDefaultUrl(): string
                        {
                            return $this->inner->getDefaultUrl();
                        }

                        public function getTitle(): string
                        {
                            return $this->inner->getTitle();
                        }

                        public function getPluginManager(): PluginManager
                        {
                            return $this->inner->getPluginManager();
                        }
                    },
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    'phpmyfaq.admin.helper' => $adminHelper,
                    default => null,
                };
            });

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

final class AbstractAdministrationControllerTestStub extends AbstractAdministrationController
{
    public function __construct()
    {
    }

    public function getAdminLogInstance(): ?AdminLog
    {
        return $this->adminLog;
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
     * @return string[]
     */
    public function fetchFooter(): array
    {
        return $this->getFooter();
    }
}
