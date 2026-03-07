<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Category as AdminCategory;
use phpMyFAQ\Administration\Helper;
use phpMyFAQ\Category\Image;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Seo;
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
#[CoversClass(CategoryController::class)]
#[UsesNamespace('phpMyFAQ')]
final class CategoryControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-category-page-controller-');
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
        unset(
            $_COOKIE[$this->getCsrfCookieName('save-category')],
            $_COOKIE[$this->getCsrfCookieName('update-category')],
        );

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

    private function createController(): CategoryController
    {
        return new CategoryController(
            $this->createStub(AdminCategory::class),
            $this->createStub(Order::class),
            $this->createStub(CategoryPermission::class),
            $this->createStub(Image::class),
            $this->createStub(Seo::class),
            $this->createStub(UserHelper::class),
        );
    }

    /**
     * @throws \Exception
     */
    public function testIndexRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $response = $controller->index($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testAddRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $response = $controller->add($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testCreateRequiresAuthentication(): void
    {
        $request = new Request([], ['pmf-csrf-token' => 'test-token']);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateThrowsForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = new CategoryController(
            new AdminCategory($this->configuration),
            $this->createStub(Order::class),
            $this->createStub(CategoryPermission::class),
            $this->createStub(Image::class),
            $this->createStub(Seo::class),
            $this->createStub(UserHelper::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], ['pmf-csrf-token' => 'invalid-token']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid CSRF token');
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateThrowsForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = new CategoryController(
            new AdminCategory($this->configuration),
            $this->createStub(Order::class),
            $this->createStub(CategoryPermission::class),
            $this->createStub(Image::class),
            $this->createStub(Seo::class),
            $this->createStub(UserHelper::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], ['pmf-csrf-token' => 'invalid-token']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid CSRF token');
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testHierarchyRendersInCurrentAnonymousAdminContext(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $response = $controller->hierarchy($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testTranslateRendersWithConfiguredPermissions(): void
    {
        $this->insertTestCategory();

        $categoryPermission = $this->createMock(CategoryPermission::class);
        $categoryPermission->method('get')->willReturn([-1]);

        $controller = new CategoryController(
            new AdminCategory($this->configuration),
            $this->createStub(Order::class),
            $categoryPermission,
            $this->createStub(Image::class),
            $this->createStub(Seo::class),
            $this->createStub(UserHelper::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request(['translateTo' => 'de']);
        $request->attributes->set('categoryId', '1');

        $response = $controller->translate($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testEditRendersCategoryDataAndSeoFields(): void
    {
        $this->insertTestCategory();

        $categoryPermission = $this->createMock(CategoryPermission::class);
        $categoryPermission->method('get')->willReturnOnConsecutiveCalls([-1], [-1]);

        $seoData = new \phpMyFAQ\Entity\SeoEntity();
        $seoData->setTitle('SEO title')->setDescription('SEO description');

        $seo = $this->createMock(Seo::class);
        $seo->expects($this->once())->method('get')->willReturn($seoData);

        $userHelper = $this->createMock(UserHelper::class);
        $userHelper
            ->expects($this->once())
            ->method('getAllUsersForTemplate')
            ->willReturn([[
                'id' => 42,
                'selected' => true,
                'displayName' => 'Test User',
                'login' => 'testuser',
            ]]);

        $controller = new CategoryController(
            new AdminCategory($this->configuration),
            $this->createStub(Order::class),
            $categoryPermission,
            $this->createStub(Image::class),
            $seo,
            $userHelper,
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], ['categoryId' => '1']);

        $response = $controller->edit($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Parent Category', (string) $response->getContent());
        self::assertStringContainsString('SEO title', (string) $response->getContent());
        self::assertStringContainsString('SEO description', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAddChildRendersParentCategoryDetailsInBasicMode(): void
    {
        $this->setConfigurationValues(['security.permLevel' => 'basic']);

        $adminCategory = $this->createMock(AdminCategory::class);
        $adminCategory->categoryName = [
            1 => ['lang' => 'en', 'name' => 'Parent Category'],
        ];
        $adminCategory->method('setUser')->willReturnSelf();
        $adminCategory->method('setGroups')->willReturnSelf();
        $adminCategory->method('setLanguage')->willReturnSelf();

        $categoryPermission = $this->createMock(CategoryPermission::class);
        $categoryPermission->method('get')->willReturnOnConsecutiveCalls([-1], [-1]);

        $controller = new CategoryController(
            $adminCategory,
            $this->createStub(Order::class),
            $categoryPermission,
            $this->createStub(Image::class),
            $this->createStub(Seo::class),
            $this->createStub(UserHelper::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], ['parentId' => '1']);

        $response = $controller->addChild($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Parent Category', (string) $response->getContent());
        self::assertStringContainsString('value="-1"', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAddChildRendersGroupOptionsInMediumMode(): void
    {
        $this->setConfigurationValues(['security.permLevel' => 'medium']);

        $adminCategory = $this->createMock(AdminCategory::class);
        $adminCategory->categoryName = [
            1 => ['lang' => 'en', 'name' => 'Parent Category'],
        ];
        $adminCategory->method('setUser')->willReturnSelf();
        $adminCategory->method('setGroups')->willReturnSelf();
        $adminCategory->method('setLanguage')->willReturnSelf();

        $categoryPermission = $this->createMock(CategoryPermission::class);
        $categoryPermission->method('get')->willReturnOnConsecutiveCalls([-1], [2]);

        $controller = new CategoryController(
            $adminCategory,
            $this->createStub(Order::class),
            $categoryPermission,
            $this->createStub(Image::class),
            $this->createStub(Seo::class),
            $this->createStub(UserHelper::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer('<option value="2" selected>Editors</option>'));

        $request = new Request([], [], ['parentId' => '1']);

        $response = $controller->addChild($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('value="2"', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateRendersSuccessForExistingCategory(): void
    {
        $this->insertTestCategory();
        $context = $this->createAuthenticatedContext();

        $seo = $this->createMock(Seo::class);
        $seo->expects($this->once())->method('get')->willReturn(new \phpMyFAQ\Entity\SeoEntity());
        $seo->expects($this->once())->method('create');

        $controller = new CategoryController(
            new AdminCategory($this->configuration),
            $this->createStub(Order::class),
            $this->createStub(CategoryPermission::class),
            $this->createStub(Image::class),
            $seo,
            $this->createStub(UserHelper::class),
        );
        $controller->setContainer($context['container']);

        $csrfToken = $this->createCsrfToken($context['session'], 'update-category');
        $request = new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'id' => '1',
            'parent_id' => '0',
            'catlang' => 'en',
            'name' => 'Updated Category',
            'description' => 'Updated description',
            'user_id' => '42',
            'group_id' => '-1',
            'active' => '1',
            'show_home' => '1',
            'existing_image' => '',
            'userpermission' => 'all',
            'grouppermission' => 'all',
            'serpTitle' => 'Updated SEO title',
            'serpDescription' => 'Updated SEO description',
        ]);

        $response = $controller->update($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('alert alert-success', (string) $response->getContent());
    }

    private function insertTestCategory(): void
    {
        $this->configuration
            ->getDb()
            ->query("INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, show_home, image)
             VALUES (1, 'en', 0, 'Parent Category', 'Parent Description', 42, -1, 1, 1, '')");
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        return $this->createAuthenticatedContext()['container'];
    }

    /**
     * @return array{container: ContainerInterface, session: Session}
     */
    private function createAuthenticatedContext(string $groupsOptions = ''): array
    {
        $permission = $this->createMock(MediumPermission::class);
        $permission->method('hasPermission')->willReturn(true);
        $permission->method('getAllGroupsOptions')->willReturn($groupsOptions);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Test User'],
                ['email',        'test@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(Helper::class);
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

        return [
            'container' => $container,
            'session' => $session,
        ];
    }

    private function setConfigurationValues(array $values): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configProperty = $configurationReflection->getProperty('config');
        $currentConfig = $configProperty->getValue($this->configuration);
        self::assertIsArray($currentConfig);
        $configProperty->setValue($this->configuration, array_merge($currentConfig, $values));
    }

    /**
     * @throws \Exception
     */
    private function createCsrfToken(Session $session, string $page): string
    {
        Token::resetInstanceForTests();

        $_SERVER['SCRIPT_NAME'] = '/admin/index.php';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'localhost';

        $token = Token::getInstance($session)->getTokenString($page);
        $_COOKIE[$this->getCsrfCookieName($page)] = $token;
        Token::resetInstanceForTests();

        return $token;
    }

    private function getCsrfCookieName(string $page): string
    {
        return sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5($page), 0, 10));
    }
}
