<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use DateTime;
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CustomPageEntity;
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
#[CoversClass(PageController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PageControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-page-controller-');
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

    /**
     * @throws \Exception
     */
    public function testIndexRendersPaginatedPages(): void
    {
        $customPage = $this->createMock(CustomPage::class);
        $customPage->expects($this->once())->method('countAllLanguages')->with(false)->willReturn(1);
        $customPage
            ->expects($this->once())
            ->method('getAllLanguagesPaginated')
            ->with(false, 25, 0, 'id', 'DESC')
            ->willReturn([[
                'id' => 7,
                'lang' => 'en',
                'page_title' => 'Unit Test Page',
                'slug' => 'unit-test-page',
                'created' => '2026-01-15 10:00:00',
                'active' => 'y',
            ]]);

        $controller = new PageController($customPage);
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->index(Request::create('https://example.com/admin/pages?page=1'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Unit Test Page', (string) $response->getContent());
        self::assertStringContainsString('./page/edit/7', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAddRendersAuthorDefaults(): void
    {
        $controller = new PageController($this->createStub(CustomPage::class));
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->add(new Request());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Test User', (string) $response->getContent());
        self::assertStringContainsString('test@example.com', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testTranslateThrowsWhenPageDoesNotExist(): void
    {
        $customPage = $this->createMock(CustomPage::class);
        $customPage->expects($this->once())->method('getById')->with(9)->willReturn(null);

        $controller = new PageController($customPage);
        $controller->setContainer($this->createControllerContainer());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Page not found');
        $controller->translate(new Request([], [], ['pageId' => '9']));
    }

    /**
     * @throws \Exception
     */
    public function testTranslateRedirectsWhenNoLanguagesAreAvailable(): void
    {
        $customPage = $this->createMock(CustomPage::class);
        $customPage->expects($this->once())->method('getById')->with(7)->willReturn($this->createPageEntity());
        $customPage
            ->expects($this->once())
            ->method('getExistingLanguages')
            ->with(7)
            ->willReturn(array_keys($this->getAvailableLanguages()));

        $controller = new PageController($customPage);
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->translate(new Request([], [], ['pageId' => '7']));

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('./pages', $response->headers->get('Location'));
    }

    /**
     * @throws \Exception
     */
    public function testTranslateRendersAvailableLanguages(): void
    {
        $customPage = $this->createMock(CustomPage::class);
        $customPage->expects($this->once())->method('getById')->with(7)->willReturn($this->createPageEntity());
        $customPage->expects($this->once())->method('getExistingLanguages')->with(7)->willReturn(['en']);

        $controller = new PageController($customPage);
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->translate(new Request([], [], ['pageId' => '7']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Unit Test Page', (string) $response->getContent());
        self::assertStringContainsString('Deutsch', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testEditThrowsWhenPageDoesNotExist(): void
    {
        $customPage = $this->createMock(CustomPage::class);
        $customPage->expects($this->once())->method('getById')->with(9)->willReturn(null);

        $controller = new PageController($customPage);
        $controller->setContainer($this->createControllerContainer());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Page not found');
        $controller->edit(new Request([], [], ['pageId' => '9']));
    }

    /**
     * @throws \Exception
     */
    public function testEditRendersExistingPage(): void
    {
        $customPage = $this->createMock(CustomPage::class);
        $customPage->expects($this->once())->method('getById')->with(7)->willReturn($this->createPageEntity());

        $controller = new PageController($customPage);
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->edit(new Request([], [], ['pageId' => '7']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('unit-test-page', (string) $response->getContent());
        self::assertStringContainsString('meta title', (string) $response->getContent());
        self::assertStringContainsString('Author Name', (string) $response->getContent());
    }

    /**
     * @return array<string,string>
     */
    private function getAvailableLanguages(): array
    {
        return \phpMyFAQ\Helper\LanguageHelper::getAvailableLanguages();
    }

    private function createPageEntity(): CustomPageEntity
    {
        return new CustomPageEntity()
            ->setId(7)
            ->setLanguage('en')
            ->setPageTitle('Unit Test Page')
            ->setSlug('unit-test-page')
            ->setContent('<p>Hello page</p>')
            ->setAuthorName('Author Name')
            ->setAuthorEmail('author@example.com')
            ->setActive(true)
            ->setCreated(new DateTime('2026-01-15 10:00:00'))
            ->setSeoTitle('meta title')
            ->setSeoDescription('meta description')
            ->setSeoRobots('index,follow');
    }

    private function createControllerContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Test User'],
                ['email',        'test@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(AdminMenuBuilder::class);
        $adminHelper->method('canAccessContent')->willReturn(true);
        $adminHelper->method('addMenuEntry')->willReturn('');

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog, $adminHelper) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    'phpmyfaq.admin.helper' => $adminHelper,
                    default => null,
                };
            });

        return $container;
    }
}
