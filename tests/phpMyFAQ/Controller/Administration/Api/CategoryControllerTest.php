<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Category\Image;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\User\CurrentUser;
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-category-controller-');
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

    private function createController(): CategoryController
    {
        return new CategoryController(
            $this->createStub(Image::class),
            $this->createStub(Order::class),
            $this->createStub(Permission::class),
        );
    }

    private function createControllerWithPermissionStub(Permission $categoryPermission): CategoryController
    {
        return new CategoryController(
            $this->createStub(Image::class),
            $this->createStub(Order::class),
            $categoryPermission,
        );
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturnCallback(
            static fn (int $userId, mixed $right): bool => $userId === 42 && in_array(
                $right,
                [
                    PermissionType::CATEGORY_DELETE->value,
                    PermissionType::CATEGORY_EDIT->value,
                ],
                true
            )
        );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(\phpMyFAQ\Administration\AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog) {
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
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'test-token',
            'categoryId' => 1,
            'language' => 'en',
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testPermissionsRequiresAuthentication(): void
    {
        $request = new Request([], [], ['categories' => '1,2']);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->permissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslationsRequiresAuthentication(): void
    {
        $request = new Request([], [], ['categoryId' => 1]);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->translations($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateOrderRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'test-token',
            'categoryTree' => [],
            'categoryId' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->updateOrder($request);
    }

    /**
     * @throws \Exception
     */
    public function testPermissionsReturnsBadRequestForNonIntegerCategoriesWhenAuthenticated(): void
    {
        $request = new Request([], [], ['categories' => 'foo,bar']);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->permissions($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Only integer values are valid.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateOrderReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'invalid-token',
            'categoryTree' => [],
            'categoryId' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->updateOrder($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testPermissionsReturnsUserAndGroupPermissionsWhenAuthenticated(): void
    {
        $categoryPermission = $this->createMock(Permission::class);
        $categoryPermission->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(static function (string $type, array $categories): array {
                if ($type === Permission::USER) {
                    return ['user' => $categories];
                }

                return ['group' => $categories];
            });

        $controller = $this->createControllerWithPermissionStub($categoryPermission);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->permissions(new Request([], [], ['categories' => '1']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(['user' => ['1']], $payload['user']);
        self::assertSame(['group' => ['1']], $payload['group']);
    }
}
