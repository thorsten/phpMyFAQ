<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
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
    use TestDatabaseTrait;

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

        $this->configuration = $this->createTestConfiguration();

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
     * Injects a logged-in CurrentUser with all permissions into the controller so
     * that the userHasPermission() authorization check passes.
     *
     * @throws MockException
     */
    private function authorizeCurrentUser(AbstractController $controller, int $userId = -1): void
    {
        $permission = $this->createMock(BasicPermission::class);
        $permission->method('hasPermission')->willReturn(true);
        $permission->method('hasPermissionForCategory')->willReturn(true);
        $permission->method('getAllowedCategoriesForRight')->willReturn(null);
        $permission
            ->method('hasPermissionForLanguage')
            ->willReturnCallback(
                // sentinel forbidden language for tests
                static fn(int $userId, mixed $right, string $language): bool => $language !== 'fr',
            );
        $permission->method('getAllowedLanguagesForRight')->willReturn(null);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn($userId);

        new \ReflectionProperty(AbstractController::class, 'currentUser')->setValue($controller, $currentUser);
    }

    /**
     * Injects a logged-in CurrentUser whose rights are limited to the given categories.
     *
     * @param int[] $allowedCategoryIds
     * @throws MockException
     */
    private function authorizeCategoryRestrictedUser(AbstractController $controller, array $allowedCategoryIds): void
    {
        $permission = $this->createMock(BasicPermission::class);
        $permission->method('hasPermission')->willReturn(true);
        $permission
            ->method('hasPermissionForCategory')
            ->willReturnCallback(
                static fn(int $userId, mixed $right, int $categoryId): bool => in_array(
                    $categoryId,
                    $allowedCategoryIds,
                    true,
                ),
            );
        $permission->method('getAllowedCategoriesForRight')->willReturn($allowedCategoryIds);
        $permission
            ->method('hasPermissionForLanguage')
            ->willReturnCallback(
                // sentinel forbidden language for tests
                static fn(int $userId, mixed $right, string $language): bool => $language !== 'fr',
            );
        $permission->method('getAllowedLanguagesForRight')->willReturn(null);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(23);

        new \ReflectionProperty(AbstractController::class, 'currentUser')->setValue($controller, $currentUser);
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
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($request);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());
    }

    /**
     * The new category is stored in the language taken from the request body, so a
     * language-restricted CATEGORY_ADD right must not let its holder create one in
     * another language.
     *
     * @throws \Exception
     */
    public function testCreateIsForbiddenForALanguageTheUserMayNotAdd(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'fr',
            'parent-id' => 0,
            'category-name' => 'Categorie non autorisee',
            'description' => 'Created in unit test',
            'is-active' => true,
            'show-on-homepage' => false,
        ], JSON_THROW_ON_ERROR));

        $category = $this->createMock(Category::class);
        $category->method('setUser')->willReturnSelf();
        $category->method('setGroups')->willReturnSelf();
        $category->method('setLanguage')->willReturnSelf();
        $category->expects($this->never())->method('create');

        $order = $this->createMock(Order::class);
        $order->expects($this->never())->method('add');

        $permission = $this->createMock(CategoryPermission::class);
        $permission->expects($this->never())->method('add');

        $controller = new CategoryController($this->createStub(Language::class));
        $controller->setCategoryFactory(static fn(array $currentGroups): Category => $category);
        $controller->setCategoryPermissionFactory(static fn(): CategoryPermission => $permission);
        $controller->setOrderFactory(static fn(): Order => $order);
        $this->authorizeCurrentUser($controller);

        $this->expectException(ForbiddenException::class);
        $controller->create($request);
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
        $this->authorizeCurrentUser($controller);

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
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'Cannot add category'], $payload);
    }

    /**
     * Security regression: a CATEGORY_ADD right limited to certain categories only covers
     * children of those categories.
     *
     * @throws \Exception
     */
    public function testCreateIsForbiddenWhenParentIsOutsideTheScopeOfTheAddRight(): void
    {
        $this->authenticateApiToken();

        $category = $this->createMock(Category::class);
        $category->expects($this->never())->method('create');

        $controller = $this->createControllerWith($category);
        $this->authorizeCategoryRestrictedUser($controller, [5]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "CATEGORY_ADD" permission for category 9.');

        $controller->create($this->createCategoryRequest(['parent-id' => 9]));
    }

    /**
     * Root categories stay reserved for holders of an unrestricted CATEGORY_ADD right.
     *
     * @throws \Exception
     */
    public function testCreateIsForbiddenAtRootWhenAddRightIsCategoryRestricted(): void
    {
        $this->authenticateApiToken();

        $category = $this->createMock(Category::class);
        $category->expects($this->never())->method('create');

        $controller = $this->createControllerWith($category);
        $this->authorizeCategoryRestrictedUser($controller, [5]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "CATEGORY_ADD" permission for uncategorized content.');

        $controller->create($this->createCategoryRequest(['parent-id' => 0]));
    }

    /**
     * The scope applies to the parent resolved from "parent-category-name" as well.
     *
     * @throws \Exception
     */
    public function testCreateIsForbiddenWhenMappedParentCategoryNameIsOutsideTheScope(): void
    {
        $this->authenticateApiToken();

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('getCategoryIdFromName')->with('Other Team')->willReturn(9);
        $category->expects($this->never())->method('create');

        $controller = $this->createControllerWith($category);
        $this->authorizeCategoryRestrictedUser($controller, [5]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "CATEGORY_ADD" permission for category 9.');

        $controller->create($this->createCategoryRequest(['parent-id' => 5, 'parent-category-name' => 'Other Team']));
    }

    /**
     * @throws \Exception
     */
    public function testCreateStoresWhenParentIsWithinTheScopeOfTheAddRight(): void
    {
        $this->authenticateApiToken();

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('create')->willReturn(125);

        $controller = $this->createControllerWith($category);
        $this->authorizeCategoryRestrictedUser($controller, [5]);

        $response = $controller->create($this->createCategoryRequest(['parent-id' => 5]));

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    private function createCategoryRequest(array $overrides = []): Request
    {
        return new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(
                $overrides
                + [
                    'language' => 'en',
                    'parent-id' => 5,
                    'category-name' => 'Scoped Category',
                    'description' => 'Created in unit test',
                    'is-active' => true,
                    'show-on-homepage' => false,
                ],
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    private function createControllerWith(Category $category): CategoryController
    {
        $permission = $this->createStub(CategoryPermission::class);
        $permission->method('add')->willReturn(true);

        $controller = new CategoryController($this->createStub(Language::class));
        $controller->setCategoryFactory(static fn(array $currentGroups): Category => $category);
        $controller->setCategoryPermissionFactory(static fn(): CategoryPermission => $permission);
        $controller->setOrderFactory(fn(): Order => $this->createStub(Order::class));

        return $controller;
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestWhenTheBodyIsNotAJsonObject(): void
    {
        $this->authenticateApiToken();

        $controller = new CategoryController($this->createStub(Language::class));
        $this->authorizeCurrentUser($controller);

        $response = $controller->create(new Request([], [], [], [], [], [], '[1, 2]'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'The request body must be a JSON object.'], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsConflictWhenTheParentCategoryNameIsUnknown(): void
    {
        $this->authenticateApiToken();

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('getCategoryIdFromName')->with('Unknown parent')->willReturn(false);
        $category->expects($this->never())->method('create');

        $controller = $this->createControllerWith($category);
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($this->createCategoryRequest(['parent-category-name' => 'Unknown parent']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'The given parent category name was not found.'], $payload);
    }

    /**
     * Without a parent id there is nothing to attach the category to; the default permission
     * collaborator is constructed before that check, so no factory is injected here.
     *
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestWithoutAParentCategory(): void
    {
        $this->authenticateApiToken();

        $category = $this->createMock(Category::class);
        $category->expects($this->never())->method('create');

        $controller = new CategoryController($this->createStub(Language::class));
        $controller->setCategoryFactory(static fn(array $currentGroups): Category => $category);
        $controller->setOrderFactory(fn(): Order => $this->createStub(Order::class));
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($this->createCategoryRequest(['parent-id' => null]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'Cannot add category'], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestWhenNoCategoryIdIsReturned(): void
    {
        $this->authenticateApiToken();

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('create')->willReturn(null);

        $order = $this->createMock(Order::class);
        $order->expects($this->never())->method('add');

        $permission = $this->createMock(CategoryPermission::class);
        $permission->expects($this->never())->method('add');

        $controller = new CategoryController($this->createStub(Language::class));
        $controller->setCategoryFactory(static fn(array $currentGroups): Category => $category);
        $controller->setCategoryPermissionFactory(static fn(): CategoryPermission => $permission);
        $controller->setOrderFactory(static fn(): Order => $order);
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($this->createCategoryRequest());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'Cannot add category'], $payload);
    }

    /**
     * Without injected factories the controller stores the order and the permissions
     * through the real collaborators on the test database.
     *
     * @throws \Exception
     */
    public function testCreateStoresOrderAndPermissionsThroughTheDefaultCollaborators(): void
    {
        $this->authenticateApiToken();

        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('create')->willReturn(777);

        $controller = new CategoryController($this->createStub(Language::class));
        $controller->setCategoryFactory(static fn(array $currentGroups): Category => $category);
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($this->createCategoryRequest(['parent-id' => 0]));

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());

        $database = $this->configuration->getDb();
        $order = $database->fetchArray($database->query(
            'SELECT parent_id FROM faqcategory_order WHERE category_id = 777',
        ));
        $this->assertSame(['parent_id' => 0], $order);
        $this->assertSame(
            1,
            $database->numRows($database->query('SELECT * FROM faqcategory_user WHERE category_id = 777')),
        );
        $this->assertSame(
            1,
            $database->numRows($database->query('SELECT * FROM faqcategory_group WHERE category_id = 777')),
        );
    }
}
