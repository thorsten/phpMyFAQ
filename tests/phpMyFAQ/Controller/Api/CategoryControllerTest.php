<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CategoryController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractApiController::class)]
#[UsesClass(PaginatedResponseOptions::class)]
class CategoryControllerTest extends TestCase
{
    private Configuration $configuration;

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

        try {
            $this->configuration = Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $db = new Sqlite3();
            $db->connect(PMF_TEST_DIR . '/test.db', '', '');
            $this->configuration = new Configuration($db);

            $configurationReflection = new \ReflectionClass(Configuration::class);
            $configurationProperty = $configurationReflection->getProperty('configuration');
            $configurationProperty->setValue(null, $this->configuration);
        }

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_PMF_TOKEN']);

        parent::tearDown();
    }

    private function forceConfigurationValue(string $key, mixed $value): void
    {
        $this->configuration->getAll();
        $reflection = new \ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('config');
        $config = $property->getValue($this->configuration);
        $config[$key] = $value;
        $property->setValue($this->configuration, $config);
    }

    private function authenticateApiToken(?string $token = null): void
    {
        $token ??= 'test-token';
        $this->forceConfigurationValue('api.apiClientToken', $token);
        $_SERVER['HTTP_X_PMF_TOKEN'] = $token;
    }

    /**
     * @throws MockException
     */
    public function testListReturnsJsonResponse(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController($this->createStub(Language::class));
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws MockException
     */
    public function testListReturnsValidStatusCode(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController($this->createStub(Language::class));
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testCreateRequiresValidToken(): void
    {
        $requestData = json_encode([
            'language' => 'en',
            'parent-id' => 0,
            'category-name' => 'Test Category',
            'description' => 'Test Description',
            'user-id' => 1,
            'group-id' => -1,
            'is-active' => true,
            'show-on-homepage' => true,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController($this->createStub(Language::class));

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController($this->createStub(Language::class));

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateWithMissingRequiredFieldsThrowsException(): void
    {
        $requestData = json_encode([
            'language' => 'en',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController($this->createStub(Language::class));

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws MockException
     */
    public function testListResponseContainsJsonData(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController($this->createStub(Language::class));
        $response = $controller->list();

        $this->assertJson($response->getContent());
    }

    /**
     * @throws MockException
     */
    public function testListReturnsArrayData(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController($this->createStub(Language::class));
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * @throws MockException
     */
    public function testListResponseContentIsNotNull(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController($this->createStub(Language::class));
        $response = $controller->list();

        $this->assertNotNull($response->getContent());
    }

    /**
     * @throws MockException
     */
    public function testListReturnsEmptyArrayOn404(): void
    {
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);

        $controller = new CategoryController($this->createStub(Language::class));
        $response = $controller->list();

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertEquals([], json_decode($response->getContent(), true));
        } else {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsCreatedWhenPayloadIsValid(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'en',
            'parent-id' => 0,
            'category-name' => 'API Category',
            'description' => 'Created in unit test',
            'user-id' => 7,
            'group-id' => 3,
            'is-active' => true,
            'show-on-homepage' => false,
        ], JSON_THROW_ON_ERROR));

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('setUser')->with(-1)->willReturnSelf();
        $category->expects($this->once())->method('setGroups')->with([-1])->willReturnSelf();
        $category->expects($this->once())->method('setLanguage')->with('en')->willReturnSelf();
        $category
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (CategoryEntity $entity): bool {
                return (
                    $entity->getLang() === 'en'
                    && $entity->getParentId() === 0
                    && $entity->getName() === 'API Category'
                    && $entity->getDescription() === 'Created in unit test'
                    && $entity->getUserId() === 7
                    && $entity->getGroupId() === 3
                    && $entity->getActive() === true
                    && $entity->getShowHome() === false
                );
            }))
            ->willReturn(123);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('add')->with(123, 0);

        $permission = $this->createMock(CategoryPermission::class);
        $permission
            ->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function (string $mode, array $categories, array $ids): bool {
                TestCase::assertContains($mode, [CategoryPermission::USER, CategoryPermission::GROUP]);
                TestCase::assertSame([123], $categories);
                TestCase::assertSame([-1], $ids);
                return true;
            });

        $controller = new CategoryController($this->createStub(Language::class));
        $controller->setCategoryFactory(static fn(array $currentGroups): Category => $category);
        $controller->setCategoryPermissionFactory(static fn(): CategoryPermission => $permission);
        $controller->setOrderFactory(static fn(): Order => $order);

        $response = $controller->create($request);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testCreateMapsParentCategoryNameWhenPresent(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'en',
            'parent-id' => 999,
            'parent-category-name' => 'Mapped Parent',
            'category-name' => 'API Child Category',
            'description' => 'Created in unit test',
            'is-active' => true,
            'show-on-homepage' => true,
        ], JSON_THROW_ON_ERROR));

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('setUser')->with(-1)->willReturnSelf();
        $category->expects($this->once())->method('setGroups')->with([-1])->willReturnSelf();
        $category->expects($this->once())->method('setLanguage')->with('en')->willReturnSelf();
        $category->expects($this->once())->method('getCategoryIdFromName')->with('Mapped Parent')->willReturn(55);
        $category
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (CategoryEntity $entity): bool {
                return (
                    $entity->getParentId() === 55
                    && $entity->getUserId() === 1
                    && $entity->getGroupId() === -1
                    && $entity->getShowHome() === true
                );
            }))
            ->willReturn(124);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('add')->with(124, 55);

        $permission = $this->createMock(CategoryPermission::class);
        $permission->expects($this->exactly(2))->method('add')->willReturn(true);

        $controller = new CategoryController($this->createStub(Language::class));
        $controller->setCategoryFactory(static fn(array $currentGroups): Category => $category);
        $controller->setCategoryPermissionFactory(static fn(): CategoryPermission => $permission);
        $controller->setOrderFactory(static fn(): Order => $order);

        $response = $controller->create($request);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestWhenCategoryCannotBeAdded(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'en',
            'parent-id' => 0,
            'category-name' => 'Broken Category',
            'description' => 'This should fail',
            'user-id' => 7,
            'group-id' => 3,
            'is-active' => true,
            'show-on-homepage' => false,
        ], JSON_THROW_ON_ERROR));

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('setUser')->with(-1)->willReturnSelf();
        $category->expects($this->once())->method('setGroups')->with([-1])->willReturnSelf();
        $category->expects($this->once())->method('setLanguage')->with('en')->willReturnSelf();
        $category->expects($this->once())->method('create')->willReturn(0);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('add')->with(0, 0);

        $permission = $this->createMock(CategoryPermission::class);
        $permission->expects($this->never())->method('add');

        $controller = new CategoryController($this->createStub(Language::class));
        $controller->setCategoryFactory(static fn(array $currentGroups): Category => $category);
        $controller->setCategoryPermissionFactory(static fn(): CategoryPermission => $permission);
        $controller->setOrderFactory(static fn(): Order => $order);

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'Cannot add category'], $payload);
    }
}
