<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Enums\FaqStatus;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\MetaData as FaqMetaData;
use phpMyFAQ\Faq\Statistics as FaqStatistics;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Permission\RightGrant;
use phpMyFAQ\Permission\RightScope;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
class FaqControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
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

        $this->configuration = $this->createConfiguration();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        // create() rejects a category that does not exist; category 1 is the one the valid
        // create requests of this test case file their FAQs in.
        $this->seedCategory(1);
    }

    private function seedCategory(int $categoryId): void
    {
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT OR IGNORE INTO %sfaqcategories (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES (%d, 'en', 0, 'Seeded Category %d', '', 1, -1, 1, '', 0)",
                Database::getTablePrefix(),
                $categoryId,
                $categoryId,
            ));
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_PMF_TOKEN']);
        Language::$language = 'en';

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        parent::tearDown();
    }

    private function createConfiguration(): Configuration
    {
        return $this->createTestConfiguration();
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
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function authorizeCurrentUser(AbstractController $controller, int $userId = -1): void
    {
        $permission = $this->createMock(BasicPermission::class);
        $permission->method('hasPermission')->willReturn(true);
        $permission->method('getScopeForRight')->willReturn(RightScope::unrestricted());

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn($userId);

        new \ReflectionProperty(AbstractController::class, 'currentUser')->setValue($controller, $currentUser);
    }

    /**
     * Injects a logged-in CurrentUser who holds every right globally, but only for the given
     * categories and languages: the scoped grant of the medium permission mode.
     *
     * @param int[] $allowedCategoryIds
     * @param string[] $allowedLanguages
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function authorizeScopedUser(
        AbstractController $controller,
        array $allowedCategoryIds,
        array $allowedLanguages,
    ): void {
        $permission = $this->createMock(BasicPermission::class);
        $permission->method('hasPermission')->willReturn(true);
        $permission
            ->method('getScopeForRight')
            ->willReturn(RightScope::ofGrants(new RightGrant($allowedCategoryIds, $allowedLanguages)));

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(23);

        new \ReflectionProperty(AbstractController::class, 'currentUser')->setValue($controller, $currentUser);
    }

    private function relateFaqToCategory(int $faqId, string $language, int $categoryId): void
    {
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO %sfaqcategoryrelations (category_id, category_lang, record_id, record_lang)
             VALUES (%d, '%s', %d, '%s')",
                Database::getTablePrefix(),
                $categoryId,
                $language,
                $faqId,
                $language,
            ));
    }

    /**
     * @throws Exception
     */
    public function testGetByCategoryIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getByCategoryId($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testGetByTagIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('tagId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getByTagId($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetPopularReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getPopular(Request::create(
            '/api/v4.0/faqs/popular',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetLatestReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getLatest(Request::create(
            '/api/v4.0/faqs/latest',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetTrendingReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getTrending(Request::create(
            '/api/v4.0/faqs/trending',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetStickyReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getSticky(Request::create(
            '/api/v4.0/faqs/sticky',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateRequiresValidToken(): void
    {
        $requestData = json_encode([
            'language' => 'en',
            'category-id' => 1,
            'question' => 'Test Question?',
            'answer' => 'Test Answer',
            'keywords' => 'test',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'is-active' => true,
            'is-sticky' => false,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testUpdateRequiresValidToken(): void
    {
        $requestData = json_encode([
            'faq-id' => 1,
            'language' => 'en',
            'category-id' => 1,
            'question' => 'Updated Question?',
            'answer' => 'Updated Answer',
            'keywords' => 'test',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'is-active' => true,
            'is-sticky' => false,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws Exception
     */
    public function testGetByCategoryIdReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getByCategoryId($request);

        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getById($request);

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithNonExistentFaq(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '999999');
        $request->attributes->set('categoryId', '999999');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getById($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testGetByTagIdReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('tagId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getByTagId($request);

        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    /**
     * @throws Exception
     */
    public function testGetPopularReturnsValidStatusCode(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getPopular(Request::create(
            '/api/v4.0/faqs/popular',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetLatestReturnsValidStatusCode(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getLatest(Request::create(
            '/api/v4.0/faqs/latest',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetTrendingReturnsValidStatusCode(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getTrending(Request::create(
            '/api/v4.0/faqs/trending',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetStickyReturnsValidStatusCode(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getSticky(Request::create(
            '/api/v4.0/faqs/sticky',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsValidStatusCode(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetByCategoryIdReturnsJsonData(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getByCategoryId($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetByIdReturnsJsonData(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getById($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListReturnsArrayData(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testUpdateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws Exception
     */
    public function testGetByCategoryIdResponseHeaders(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getByCategoryId($request);

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    /**
     * @throws Exception
     */
    public function testGetByIdResponseHeaders(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getById($request);

        $this->assertTrue($response->headers->has('Content-Type'));
    }

    /**
     * @throws Exception
     */
    public function testGetPopularResponseIsNotEmpty(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getPopular(Request::create(
            '/api/v4.0/faqs/popular',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }

    /**
     * @throws Exception
     */
    public function testGetLatestResponseIsNotEmpty(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getLatest(Request::create(
            '/api/v4.0/faqs/latest',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }

    /**
     * @throws Exception
     */
    public function testGetTrendingResponseIsNotEmpty(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getTrending(Request::create(
            '/api/v4.0/faqs/trending',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }

    /**
     * @throws Exception
     */
    public function testGetStickyResponseIsNotEmpty(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getSticky(Request::create(
            '/api/v4.0/faqs/sticky',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }

    /**
     * @throws Exception
     */
    public function testListResponseIsNotEmpty(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->list();

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }

    /**
     * @throws Exception
     */
    public function testGetByCategoryIdWithMultipleCategories(): void
    {
        $categories = ['1', '2', '5', '10'];

        foreach ($categories as $categoryId) {
            $request = new Request();
            $request->attributes->set('categoryId', $categoryId);

            $controller = new FaqController(
                $this->createStub(Faq::class),
                $this->createStub(Tags::class),
                $this->createStub(FaqStatistics::class),
                $this->createStub(FaqMetaData::class),
                $this->configuration->getLanguage(),
            );
            $response = $controller->getByCategoryId($request);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertContains($response->getStatusCode(), [200, 500]);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithMultipleFaqs(): void
    {
        $faqs = [
            ['faqId' => '1', 'categoryId' => '1'],
            ['faqId' => '2', 'categoryId' => '1'],
            ['faqId' => '5', 'categoryId' => '2'],
        ];

        foreach ($faqs as $faq) {
            $request = new Request();
            $request->attributes->set('faqId', $faq['faqId']);
            $request->attributes->set('categoryId', $faq['categoryId']);

            $controller = new FaqController(
                $this->createStub(Faq::class),
                $this->createStub(Tags::class),
                $this->createStub(FaqStatistics::class),
                $this->createStub(FaqMetaData::class),
                $this->configuration->getLanguage(),
            );
            $response = $controller->getById($request);

            $this->assertInstanceOf(JsonResponse::class, $response);
        }
    }

    /**
     * @throws \Exception
     */
    public function testGetByTagIdWithMultipleTags(): void
    {
        $tagIds = ['1', '2', '5'];

        foreach ($tagIds as $tagId) {
            $request = new Request();
            $request->attributes->set('tagId', $tagId);

            $controller = new FaqController(
                $this->createStub(Faq::class),
                $this->createStub(Tags::class),
                $this->createStub(FaqStatistics::class),
                $this->createStub(FaqMetaData::class),
                $this->configuration->getLanguage(),
            );
            $response = $controller->getByTagId($request);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertContains($response->getStatusCode(), [200, 500]);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetByCategoryIdJsonStructure(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getByCategoryId($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);

        if (!empty($data) && isset($data[0])) {
            $this->assertArrayHasKey('record_id', $data[0]);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetByIdJsonStructure(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->getById($request);

        $content = $response->getContent();
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertThat($data, $this->logicalOr($this->isNull(), $this->isArray()));
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByCategoryIdReturnsFaqsForExistingCategory(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '7');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq
            ->expects($this->once())
            ->method('getAllAvailableFaqsByCategoryId')
            ->with(7)
            ->willReturn([
                ['record_id' => 1, 'record_title' => 'Question'],
            ]);

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $response = $controller->getByCategoryId($request);
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Question', $payload[0]['record_title']);
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByCategoryIdReturnsInternalServerErrorWhenFaqLookupFails(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '7');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq
            ->expects($this->once())
            ->method('getAllAvailableFaqsByCategoryId')
            ->with(7)
            ->willThrowException(new \Exception('lookup failed'));

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $response = $controller->getByCategoryId($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('lookup failed', (string) $response->getContent());
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByTagIdReturnsFaqsForExistingTag(): void
    {
        $request = new Request();
        $request->attributes->set('tagId', '3');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq
            ->expects($this->once())
            ->method('getFaqsByIds')
            ->with([5, 8])
            ->willReturn([
                ['id' => 5, 'question' => 'Tagged FAQ'],
            ]);

        $tags = $this->createMock(Tags::class);
        $tags->expects($this->once())->method('getFaqsByTagId')->with(3)->willReturn([5, 8]);

        $controller = new FaqController(
            $faq,
            $tags,
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $response = $controller->getByTagId($request);
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Tagged FAQ', $payload[0]['question']);
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByTagIdReturnsInternalServerErrorWhenFaqLookupFails(): void
    {
        $request = new Request();
        $request->attributes->set('tagId', '3');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq
            ->expects($this->once())
            ->method('getFaqsByIds')
            ->with([5, 8])
            ->willThrowException(new \Exception('tag lookup failed'));

        $tags = $this->createMock(Tags::class);
        $tags->expects($this->once())->method('getFaqsByTagId')->with(3)->willReturn([5, 8]);

        $controller = new FaqController(
            $faq,
            $tags,
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $response = $controller->getByTagId($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('tag lookup failed', (string) $response->getContent());
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByIdReturnsFaqWhenRecordIsActive(): void
    {
        $this->forceConfigurationValue('api.onlyActiveFaqs', false);

        $request = new Request();
        $request->attributes->set('faqId', '9');
        $request->attributes->set('categoryId', '4');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq
            ->expects($this->once())
            ->method('getFaqByIdAndCategoryId')
            ->with(9, 4)
            ->willReturn([
                'solution_id' => 123,
                'status' => 'published',
                'question' => 'Active FAQ',
            ]);

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $response = $controller->getById($request);
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Active FAQ', $payload['question']);
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByIdReturnsNotFoundWhenOnlyActiveFaqsAreEnabled(): void
    {
        $this->forceConfigurationValue('api.onlyActiveFaqs', true);

        $request = new Request();
        $request->attributes->set('faqId', '9');
        $request->attributes->set('categoryId', '4');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq
            ->expects($this->once())
            ->method('getFaqByIdAndCategoryId')
            ->with(9, 4)
            ->willReturn([
                'solution_id' => 123,
                'status' => 'draft',
            ]);

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $response = $controller->getById($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('{}', (string) $response->getContent());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testCreateReturnsCreatedWhenPayloadIsValid(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'en',
            'category-id' => 1,
            'question' => 'Created via API?',
            'answer' => 'Yes.',
            'keywords' => 'api, faq',
            'author' => 'API Author',
            'email' => 'api@example.com',
            'is-active' => true,
            'is-sticky' => true,
        ], JSON_THROW_ON_ERROR));

        $storedEntity = new FaqEntity()->setId(123);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq->expects($this->once())->method('hasTitleAHash')->with('Created via API?')->willReturn(false);
        $faq
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (FaqEntity $entity): bool {
                return (
                    $entity->getLanguage() === 'en'
                    && $entity->getQuestion() === 'Created via API?'
                    && $entity->getAnswer() === 'Yes.'
                    && $entity->getKeywords() === 'api, faq'
                    && $entity->getAuthor() === 'API Author'
                    && $entity->getEmail() === 'api@example.com'
                    && $entity->getStatus() === FaqStatus::Published
                    && $entity->isSticky() === true
                );
            }))
            ->willReturn($storedEntity);

        $faqMetaData = $this->createMock(FaqMetaData::class);
        $faqMetaData->expects($this->once())->method('setFaqId')->with(123)->willReturnSelf();
        $faqMetaData->expects($this->once())->method('setFaqLanguage')->with('en')->willReturnSelf();
        $faqMetaData->expects($this->once())->method('setCategories')->with([1])->willReturnSelf();
        $faqMetaData->expects($this->once())->method('save');

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $faqMetaData,
            $this->configuration->getLanguage(),
        );
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetStickyUsesAcceptLanguageForFaqLanguage(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1)->willReturnSelf();
        $faq->expects($this->once())->method('setGroups')->with([-1])->willReturnSelf();
        $faq
            ->expects($this->once())
            ->method('getStickyFaqsData')
            ->willReturnCallback(function (): array {
                $this->assertSame('de', $this->configuration->getLanguage()->getLanguage());

                return [[
                    'question' => 'Sticky FAQ',
                    'url' => 'https://localhost/content/1/1/de/sticky-faq.html',
                    'id' => 1,
                    'order' => 1,
                ]];
            });

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $request = Request::create('/api/v4.0/faqs/sticky', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de-DE']);
        $response = $controller->getSticky($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJson((string) $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetPopularUsesAcceptLanguageForStatisticsLanguage(): void
    {
        $statistics = $this->createMock(FaqStatistics::class);
        $statistics->expects($this->once())->method('setUser')->with(-1)->willReturnSelf();
        $statistics->expects($this->once())->method('setGroups')->with([-1])->willReturnSelf();
        $statistics
            ->expects($this->once())
            ->method('getTopTenData')
            ->willReturnCallback(function (): array {
                $this->assertSame('de', $this->configuration->getLanguage()->getLanguage());

                return [[
                    'question' => 'Popular FAQ',
                    'answer' => 'A good answer',
                    'visits' => 10,
                    'date' => '2026-04-13T10:00:00+02:00',
                    'url' => 'https://localhost/content/1/1/de/popular-faq.html',
                ]];
            });

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $statistics,
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $request = Request::create('/api/v4.0/faqs/popular', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de-DE']);
        $response = $controller->getPopular($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJson((string) $response->getContent());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testCreateReturnsConflictWhenCategoryNameCannotBeMapped(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'en',
            'category-id' => 1,
            'category-name' => 'Definitely Missing Category Name',
            'question' => 'Created via API?',
            'answer' => 'Yes.',
            'keywords' => 'api, faq',
            'author' => 'API Author',
            'email' => 'api@example.com',
            'is-active' => true,
            'is-sticky' => false,
        ], JSON_THROW_ON_ERROR));

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq->expects($this->never())->method('hasTitleAHash');
        $faq->expects($this->never())->method('create');

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->authorizeCurrentUser($controller);

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'The given category name was not found.'], $payload);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testCreateReturnsBadRequestWhenTitleContainsHash(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'language' => 'en',
            'category-id' => 1,
            'question' => 'Bad # title?',
            'answer' => 'No.',
            'keywords' => 'api, faq',
            'author' => 'API Author',
            'email' => 'api@example.com',
            'is-active' => true,
            'is-sticky' => false,
        ], JSON_THROW_ON_ERROR));

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq->expects($this->once())->method('hasTitleAHash')->with('Bad # title?')->willReturn(true);
        $faq->expects($this->never())->method('create');

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->authorizeCurrentUser($controller);

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['stored' => false, 'error' => 'It is not allowed, that the question title contains a hash.'],
            $payload,
        );
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testUpdateReturnsSuccessWhenPayloadIsValid(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'faq-id' => 7,
            'language' => 'en',
            'category-id' => 1,
            'question' => 'Updated via API?',
            'answer' => 'Still yes.',
            'keywords' => 'update, faq',
            'author' => 'API Updater',
            'email' => 'update@example.com',
            'is-active' => true,
            'is-sticky' => true,
        ], JSON_THROW_ON_ERROR));

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->expects($this->once())->method('hasTitleAHash')->with('Updated via API?')->willReturn(false);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);
        $faq
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(static function (FaqEntity $entity): bool {
                return (
                    $entity->getId() === 7
                    && $entity->getRevisionId() === 0
                    && $entity->getLanguage() === 'en'
                    && $entity->getQuestion() === 'Updated via API?'
                    && $entity->getAnswer() === 'Still yes.'
                    && $entity->getKeywords() === 'update, faq'
                    && $entity->getAuthor() === 'API Updater'
                    && $entity->getEmail() === 'update@example.com'
                    && $entity->getStatus() === FaqStatus::Published
                    && $entity->isSticky() === true
                );
            }))
            ->willReturn(new FaqEntity()->setId(7));

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->authorizeCurrentUser($controller);

        $response = $controller->update($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());
    }

    /**
     * The boolean "is-active" field cannot express the review state, so a deactivating
     * update must preserve it instead of silently demoting the FAQ to draft.
     *
     * @throws \JsonException|\Exception
     */
    public function testUpdatePreservesReviewStatusWhenIsActiveIsFalse(): void
    {
        $this->authenticateApiToken();

        $request = Request::create(
            '/api/v4.0/faq/7',
            'PUT',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'faq-id' => 7,
                'language' => 'en',
                'category-id' => 1,
                'question' => 'Updated via API?',
                'answer' => 'Still yes.',
                'keywords' => 'update, faq',
                'author' => 'API Updater',
                'email' => 'update@example.com',
                'is-active' => 'false',
                'is-sticky' => 'false',
            ], JSON_THROW_ON_ERROR),
        );

        $faq = $this->createMock(Faq::class);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->method('getStatus')->willReturn(FaqStatus::Review);
        $faq->method('isActive')->willReturn(false);
        $faq
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(static fn(FaqEntity $entity): bool => $entity->getStatus() === FaqStatus::Review))
            ->willReturn(new FaqEntity()->setId(7));

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->authorizeCurrentUser($controller);

        $response = $controller->update($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testUpdateReturnsBadRequestWhenTitleContainsHash(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'faq-id' => 7,
            'language' => 'en',
            'category-id' => 1,
            'question' => 'Updated # bad?',
            'answer' => 'Still no.',
            'keywords' => 'update, faq',
            'author' => 'API Updater',
            'email' => 'update@example.com',
            'is-active' => true,
            'is-sticky' => false,
        ], JSON_THROW_ON_ERROR));

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(-1);
        $faq->expects($this->once())->method('setGroups')->with([-1]);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->expects($this->once())->method('hasTitleAHash')->with('Updated # bad?')->willReturn(true);
        $faq->expects($this->never())->method('update');

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->authorizeCurrentUser($controller);

        $response = $controller->update($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['stored' => false, 'error' => 'It is not allowed, that the question title contains a hash.'],
            $payload,
        );
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testListPagesAndSortsInTheDatabaseInsteadOfSlicingEveryFaq(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->faqRecords = [['id' => 11, 'title' => 'Zulu'], ['id' => 12, 'title' => 'Yankee']];
        $faq
            ->expects($this->once())
            ->method('getAllFaqs')
            ->with(
                Faq::SORTING_TYPE_FAQTITLE,
                $this->callback(static fn(array $condition): bool => $condition['lang'] === 'en'),
                'DESC',
                10,
                10,
            );
        $faq->expects($this->once())->method('countAllFaqs')->willReturn(37);

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
        $response = $controller->list(new Request([
            'sort' => 'title',
            'order' => 'desc',
            'page' => 2,
            'per_page' => 10,
        ]));
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame([11, 12], array_column($payload['data'], 'id'));
        $this->assertSame(37, $payload['meta']['pagination']['total']);
        $this->assertSame(4, $payload['meta']['pagination']['total_pages']);
        $this->assertSame(2, $payload['meta']['pagination']['current_page']);
    }

    /**
     * Security regression: the editor-only "private notes" field must never reach the
     * unauthenticated public list endpoint (GET /faqs).
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testListDoesNotExposePrivateNotes(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->faqRecords = [
            ['id' => 11, 'title' => 'Public question', 'notes' => 'INTERNAL: ticket #4711'],
            ['id' => 12, 'title' => 'Another question', 'notes' => ''],
        ];
        $faq->method('countAllFaqs')->willReturn(2);

        $response = $this->createFaqControllerWith($faq)->list(new Request());
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame([11, 12], array_column($payload['data'], 'id'));
        foreach ($payload['data'] as $record) {
            $this->assertArrayNotHasKey('notes', $record);
        }
        $this->assertStringNotContainsString('INTERNAL', (string) $response->getContent());
    }

    /**
     * Security regression: the author's e-mail address must never reach the unauthenticated
     * public list endpoint (GET /faqs).
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testListDoesNotExposeAuthorEmail(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->faqRecords = [
            ['id' => 11, 'title' => 'Public question', 'author' => 'Jane Doe', 'email' => 'jane.doe@corp.example'],
            ['id' => 12, 'title' => 'Another question', 'author' => 'John Doe', 'email' => 'john.doe@corp.example'],
        ];
        $faq->method('countAllFaqs')->willReturn(2);

        $response = $this->createFaqControllerWith($faq)->list(new Request());
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(['Jane Doe', 'John Doe'], array_column($payload['data'], 'author'));
        foreach ($payload['data'] as $record) {
            $this->assertArrayNotHasKey('email', $record);
        }
        $this->assertStringNotContainsString('corp.example', (string) $response->getContent());
    }

    /**
     * Security regression: the author's e-mail address must never reach the unauthenticated
     * public single-record endpoint (GET /faq/{categoryId}/{faqId}).
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetByIdDoesNotExposeAuthorEmail(): void
    {
        $this->forceConfigurationValue('api.onlyActiveFaqs', false);

        $request = new Request();
        $request->attributes->set('faqId', '9');
        $request->attributes->set('categoryId', '4');

        $faq = $this->createMock(Faq::class);
        $faq->method('getFaqByIdAndCategoryId')->willReturn([
            'id' => 9,
            'solution_id' => 123,
            'status' => 'published',
            'question' => 'Active FAQ',
            'author' => 'Jane Doe',
            'email' => 'jane.doe@corp.example',
            'notes' => 'INTERNAL: ticket #4711',
        ]);

        $response = $this->createFaqControllerWith($faq)->getById($request);
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Jane Doe', $payload['author']);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('notes', $payload);
    }

    /**
     * Security regression: the "Author visibility (GDPR)" rule of the HTML frontend and the
     * PDF export also applies to the public API. A registered user who opted out of being
     * visible must not be de-anonymised through GET /faq/{categoryId}/{faqId} or GET /faqs.
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testPublicEndpointsHideAuthorOfInvisibleUser(): void
    {
        $this->forceConfigurationValue('api.onlyActiveFaqs', false);

        $request = new Request();
        $request->attributes->set('faqId', '9');
        $request->attributes->set('categoryId', '4');

        $record = [
            'id' => 9,
            'solution_id' => 123,
            'status' => 'published',
            'question' => 'Active FAQ',
            'author' => 'Jane Doe',
            'email' => 'jane.doe@corp.example',
        ];

        $faq = $this->createMock(Faq::class);
        $faq->method('getFaqByIdAndCategoryId')->willReturn($record);
        $faq->faqRecords = [$record];
        $faq->method('countAllFaqs')->willReturn(1);

        $controller = $this->createFaqControllerWith($faq);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $this->createMock(BasicPermission::class);
        $currentUser->method('getUserId')->willReturn(-1);
        $currentUser
            ->expects($this->exactly(2))
            ->method('getUserVisibilityByEmail')
            ->with('jane.doe@corp.example')
            ->willReturn(false);
        new \ReflectionProperty(AbstractController::class, 'currentUser')->setValue($controller, $currentUser);

        $single = json_decode((string) $controller->getById($request)->getContent(), true);
        $this->assertSame('n/a', $single['author']);
        $this->assertArrayNotHasKey('email', $single);

        $list = json_decode((string) $controller->list(new Request())->getContent(), true);
        $this->assertSame('n/a', $list['data'][0]['author']);
        $this->assertArrayNotHasKey('email', $list['data'][0]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @throws \JsonException
     */
    private function createUpdateRequest(array $overrides = []): Request
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
                    'faq-id' => 7,
                    'language' => 'en',
                    'category-id' => 1,
                    'question' => 'Updated via API?',
                    'answer' => 'Still yes.',
                    'keywords' => 'update, faq',
                    'author' => 'API Updater',
                    'email' => 'update@example.com',
                    'is-active' => false,
                    'is-sticky' => false,
                ],
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    private function createFaqControllerWith(Faq $faq): FaqController
    {
        return new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
    }

    /**
     * Security regression: the global "edit FAQ" right must not be sufficient to modify
     * a record the requester is not permitted to access (BOLA on PUT /faq/update).
     *
     * @throws \JsonException|\Exception
     */
    public function testUpdateReturnsNotFoundAndDoesNotWriteWhenFaqIsNotEditableForUser(): void
    {
        $this->authenticateApiToken();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('isFaqEditableForUser')->with(7, 'en')->willReturn(false);
        $faq->expects($this->never())->method('update');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeCurrentUser($controller);

        $response = $controller->update($this->createUpdateRequest());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(
            ['stored' => false, 'error' => 'The given FAQ was not found.'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @throws \JsonException|\Exception
     */
    public function testUpdateScopesTheObjectCheckToTheRequestingUser(): void
    {
        $this->authenticateApiToken();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(23);
        $faq->method('isFaqEditableForUser')->willReturn(false);

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeCurrentUser($controller, 23);

        $response = $controller->update($this->createUpdateRequest());

        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * @throws \JsonException|\Exception
     */
    public function testUpdateWritesWhenFaqIsEditableForUser(): void
    {
        $this->authenticateApiToken();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('isFaqEditableForUser')->with(7, 'en')->willReturn(true);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(
                static fn(FaqEntity $entity): bool => $entity->getId() === 7 && $entity->getLanguage() === 'en',
            ))
            ->willReturn(new FaqEntity()->setId(7));

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeCurrentUser($controller);

        $response = $controller->update($this->createUpdateRequest());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());
    }

    /**
     * Security regression: a FAQ_EDIT right limited to certain categories must not rewrite a
     * published FAQ in another category, even when the publication state stays untouched and
     * the publish check therefore never runs.
     *
     * @throws \JsonException|\Exception
     */
    public function testUpdateIsForbiddenWhenEditRightIsRestrictedToOtherCategories(): void
    {
        $this->authenticateApiToken();
        $this->relateFaqToCategory(7, 'en', 9);

        $faq = $this->createMock(Faq::class);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);
        $faq->expects($this->never())->method('update');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeScopedUser($controller, allowedCategoryIds: [5], allowedLanguages: ['en']);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for category 9 in language "en".');

        $controller->update($this->createUpdateRequest(['is-active' => true]));
    }

    /**
     * @throws \JsonException|\Exception
     */
    public function testUpdateIsForbiddenWhenEditRightIsRestrictedToOtherLanguages(): void
    {
        $this->authenticateApiToken();
        $this->relateFaqToCategory(7, 'en', 5);

        $faq = $this->createMock(Faq::class);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);
        $faq->expects($this->never())->method('update');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeScopedUser($controller, allowedCategoryIds: [5], allowedLanguages: ['de']);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for language "en".');

        $controller->update($this->createUpdateRequest(['is-active' => true]));
    }

    /**
     * @throws \JsonException|\Exception
     */
    public function testUpdateWritesWhenFaqIsWithinTheScopeOfTheEditRight(): void
    {
        $this->authenticateApiToken();
        $this->relateFaqToCategory(7, 'en', 5);

        $faq = $this->createMock(Faq::class);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);
        $faq->expects($this->once())->method('update')->willReturn(new FaqEntity()->setId(7));

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeScopedUser($controller, allowedCategoryIds: [5], allowedLanguages: ['en']);

        $response = $controller->update($this->createUpdateRequest(['is-active' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Security regression: a FAQ_ADD right limited to certain categories or languages must not
     * create FAQs outside that scope.
     *
     * @throws \JsonException|\Exception
     */
    public function testCreateIsForbiddenWhenAddRightIsRestrictedToOtherCategories(): void
    {
        $this->authenticateApiToken();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('create');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeScopedUser($controller, allowedCategoryIds: [5], allowedLanguages: ['en']);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_ADD" permission for category 9 in language "en".');

        $controller->create($this->createCreateRequest(['category-id' => 9]));
    }

    /**
     * @throws \JsonException|\Exception
     */
    public function testCreateIsForbiddenWhenAddRightIsRestrictedToOtherLanguages(): void
    {
        $this->authenticateApiToken();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('create');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeScopedUser($controller, allowedCategoryIds: [5], allowedLanguages: ['de']);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_ADD" permission for language "en".');

        $controller->create($this->createCreateRequest(['category-id' => 5]));
    }

    /**
     * The scope applies to the category resolved from "category-name" as well.
     *
     * @throws \JsonException|\Exception
     */
    public function testCreateIsForbiddenWhenMappedCategoryNameIsOutsideTheScope(): void
    {
        $this->authenticateApiToken();
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO %sfaqcategories (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES (9, 'en', 0, 'Other Team', '', 1, -1, 1, '', 0)",
                Database::getTablePrefix(),
            ));

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('create');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeScopedUser($controller, allowedCategoryIds: [5], allowedLanguages: ['en']);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_ADD" permission for category 9 in language "en".');

        $controller->create($this->createCreateRequest(['category-id' => 5, 'category-name' => 'Other Team']));
    }

    /**
     * A category id nobody checked ended up in faqcategoryrelations all the same: the FAQ was
     * stored, but reachable from no category page.
     *
     * @throws \JsonException|\Exception
     */
    public function testCreateReturnsConflictAndStoresNothingWhenTheCategoryDoesNotExist(): void
    {
        $this->authenticateApiToken();

        $faq = $this->createMock(Faq::class);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->expects($this->never())->method('create');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($this->createCreateRequest(['category-id' => 4711]));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(
            ['stored' => false, 'error' => 'The given category ID was not found.'],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * The scope is checked first: a caller limited to some categories learns that the request
     * is out of scope, not whether the category id exists.
     *
     * @throws \JsonException|\Exception
     */
    public function testCreateDoesNotRevealWhetherACategoryOutsideTheScopeExists(): void
    {
        $this->authenticateApiToken();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('create');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeScopedUser($controller, allowedCategoryIds: [5], allowedLanguages: ['en']);

        $this->expectException(ForbiddenException::class);

        $controller->create($this->createCreateRequest(['category-id' => 4711]));
    }

    private function createCreateRequest(array $overrides = []): Request
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
                    'category-id' => 5,
                    'question' => 'Created via API?',
                    'answer' => 'Yes.',
                    'keywords' => '',
                    'author' => 'API Author',
                    'email' => 'api@example.com',
                    'is-active' => false,
                    'is-sticky' => false,
                ],
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    private function createFaqControllerWithStatistics(FaqStatistics $faqStatistics): FaqController
    {
        return new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $faqStatistics,
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetLatestReturnsTheLatestFaqs(): void
    {
        $latest = [['id' => 1, 'question' => 'Latest?', 'answer' => 'Yes.', 'visits' => 3]];
        $faqStatistics = $this->createStub(FaqStatistics::class);
        $faqStatistics->method('getLatestData')->willReturn(['first' => $latest[0]]);

        $response = $this->createFaqControllerWithStatistics($faqStatistics)->getLatest(Request::create(
            '/api/v4.0/faqs/latest',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($latest, json_decode((string) $response->getContent(), true));
    }

    /**
     * @throws Exception
     */
    public function testGetTrendingReturnsTheTrendingFaqs(): void
    {
        $trending = [['id' => 2, 'question' => 'Trending?', 'answer' => 'Yes.', 'visits' => 42]];
        $faqStatistics = $this->createStub(FaqStatistics::class);
        $faqStatistics->method('getTrendingData')->willReturn(['first' => $trending[0]]);

        $response = $this->createFaqControllerWithStatistics($faqStatistics)->getTrending(Request::create(
            '/api/v4.0/faqs/trending',
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT_LANGUAGE' => 'en'],
        ));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($trending, json_decode((string) $response->getContent(), true));
    }

    /**
     * @throws \JsonException|\Exception
     */
    public function testCreateReturnsBadRequestWhenNoFaqIdIsAssigned(): void
    {
        $this->authenticateApiToken();
        $this->seedCategory(5);

        $faq = $this->createMock(Faq::class);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->expects($this->once())->method('create')->willReturn(new FaqEntity());

        $faqMetaData = $this->createMock(FaqMetaData::class);
        $faqMetaData->expects($this->never())->method('save');

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $faqMetaData,
            $this->configuration->getLanguage(),
        );
        $this->authorizeCurrentUser($controller);

        $response = $controller->create($this->createCreateRequest());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'Cannot add FAQ'], $payload);
    }

    /**
     * @throws \JsonException|\Exception
     */
    public function testUpdateReturnsBadRequestWithoutAFaqId(): void
    {
        $this->authenticateApiToken();

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('isFaqEditableForUser');
        $faq->expects($this->never())->method('update');

        $controller = $this->createFaqControllerWith($faq);
        $this->authorizeCurrentUser($controller);

        $response = $controller->update($this->createUpdateRequest(['faq-id' => null]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['stored' => false, 'error' => 'Cannot update FAQ'], $payload);
    }

    /**
     * Publishing a draft through an update changes the publication state, which needs the
     * publish right on top of the edit right.
     *
     * @throws \JsonException|\Exception
     */
    public function testUpdateRequiresThePublishRightWhenADraftIsPublished(): void
    {
        $this->authenticateApiToken();
        $this->relateFaqToCategory(7, 'en', 5);

        $faq = $this->createMock(Faq::class);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(static fn(FaqEntity $entity): bool => $entity->getStatus() === FaqStatus::Published))
            ->willReturn(new FaqEntity()->setId(7));

        $controller = $this->createFaqControllerWith($faq);
        $permission = $this->createMock(BasicPermission::class);
        $permission
            ->expects($this->atLeastOnce())
            ->method('hasPermission')
            ->willReturnCallback(static fn(int $userId, mixed $right): bool => true);
        $permission->method('getScopeForRight')->willReturn(RightScope::unrestricted());
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(-1);
        new \ReflectionProperty(AbstractController::class, 'currentUser')->setValue($controller, $currentUser);

        $response = $controller->update($this->createUpdateRequest(['is-active' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws \JsonException|\Exception
     */
    public function testUpdateIsForbiddenWhenADraftIsPublishedWithoutThePublishRight(): void
    {
        $this->authenticateApiToken();
        $this->relateFaqToCategory(7, 'en', 5);

        $faq = $this->createMock(Faq::class);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq->expects($this->never())->method('update');

        $controller = $this->createFaqControllerWith($faq);
        $permission = $this->createMock(BasicPermission::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $right !== PermissionType::FAQ_PUBLISH->value,
            );
        $permission->method('getScopeForRight')->willReturn(RightScope::unrestricted());
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(-1);
        new \ReflectionProperty(AbstractController::class, 'currentUser')->setValue($controller, $currentUser);

        $this->expectException(ForbiddenException::class);
        $controller->update($this->createUpdateRequest(['is-active' => true]));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function sortFieldProvider(): iterable
    {
        yield 'author' => ['author', Faq::SORTING_TYPE_AUTHOR];
        yield 'updated' => ['updated', Faq::SORTING_TYPE_UPDATED];
        yield 'created' => ['created', Faq::SORTING_TYPE_CREATED];
    }

    /**
     * @throws Exception
     */
    #[DataProvider('sortFieldProvider')]
    public function testListMapsTheSortFieldToTheFaqSorting(string $field, int $expectedSorting): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->faqRecords = [];
        $faq
            ->expects($this->once())
            ->method('getAllFaqs')
            ->with($expectedSorting, $this->anything(), 'ASC', $this->anything(), $this->anything());
        $faq->method('countAllFaqs')->willReturn(0);

        $response = $this->createFaqControllerWith($faq)->list(new Request(['sort' => $field]));

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @throws \JsonException|\Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('incompleteCreatePayloadProvider')]
    public function testCreateReturnsBadRequestAndStoresNothingWhenPayloadIsIncomplete(
        array $payload,
        string $expectedError,
    ): void {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR));

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('create');

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->authorizeCurrentUser($controller);

        $response = $controller->create($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['stored' => false, 'error' => $expectedError],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function incompleteCreatePayloadProvider(): array
    {
        $payload = [
            'language' => 'en',
            'category-id' => 1,
            'question' => 'Created via API?',
            'answer' => 'Yes.',
            'keywords' => '',
            'author' => 'API Author',
            'email' => 'api@example.com',
            'is-active' => false,
            'is-sticky' => false,
        ];

        return [
            'no language' => [
                array_diff_key($payload, ['language' => true]),
                'The given language is not supported.',
            ],
            'unknown language' => [['language' => 'xx'] + $payload, 'The given language is not supported.'],
            'no category' => [
                array_diff_key($payload, ['category-id' => true]),
                'A category ID or a category name is required.',
            ],
            'category zero' => [['category-id' => 0] + $payload, 'A category ID or a category name is required.'],
            'no question' => [array_diff_key($payload, ['question' => true]), 'The question must not be empty.'],
            'blank question' => [['question' => '   '] + $payload, 'The question must not be empty.'],
        ];
    }

    /**
     * FaqRepository::update() writes every column, so the columns the endpoint does not
     * accept must come from the stored record.
     *
     * @throws \JsonException|\Exception
     */
    public function testUpdateKeepsTheStoredColumnsTheEndpointDoesNotAccept(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'faq-id' => 7,
            'language' => 'en',
            'category-id' => 1,
            'question' => 'Updated via API?',
            'answer' => 'Still yes.',
            'keywords' => 'update, faq',
            'author' => 'API Updater',
            'email' => 'update@example.com',
            'is-active' => true,
            'is-sticky' => false,
        ], JSON_THROW_ON_ERROR));

        $faq = $this->createMock(Faq::class);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);
        $faq->expects($this->once())->method('getFaq')->with(7, null, true, 'en');
        $faq->faqRecord = [
            'revision_id' => 4,
            'comment' => 'y',
            'notes' => 'Internal note',
            'dateStart' => '20250102030405',
            'dateEnd' => '20301231235959',
        ];
        $faq
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(static function (FaqEntity $entity): bool {
                return (
                    $entity->getRevisionId() === 4
                    && $entity->isComment() === true
                    && $entity->getNotes() === 'Internal note'
                    // No window on the entity: the repository leaves date_start / date_end alone.
                    && $entity->getPublicationWindow() === null
                );
            }))
            ->willReturn(new FaqEntity()->setId(7));

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->authorizeCurrentUser($controller);

        $this->assertSame(200, $controller->update($request)->getStatusCode());
    }

    /**
     * The endpoint accepts no publication window, so it must not decide about one: an entity
     * with a window would overwrite the stored date_start / date_end, and the "no start"
     * sentinel "00000000000000" used to come back as the moment of the update.
     *
     * @throws \JsonException|\Exception
     */
    public function testUpdateDoesNotDecideAboutThePublicationWindow(): void
    {
        $this->authenticateApiToken();

        $request = new Request([], [], [], [], [], [], json_encode([
            'faq-id' => 7,
            'language' => 'en',
            'question' => 'Updated via API?',
            'answer' => 'Still yes.',
            'is-active' => true,
        ], JSON_THROW_ON_ERROR));

        $faq = $this->createMock(Faq::class);
        $faq->method('isFaqEditableForUser')->willReturn(true);
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);
        $faq->faqRecord = ['dateStart' => '00000000000000', 'dateEnd' => '99991231235959'];
        $faq
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(static function (FaqEntity $entity): bool {
                return $entity->getPublicationWindow() === null;
            }))
            ->willReturn(new FaqEntity()->setId(7));

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $this->authorizeCurrentUser($controller);

        $this->assertSame(200, $controller->update($request)->getStatusCode());
    }

    private const int LISTED_PUBLISHED_FAQ = 9101;
    private const int LISTED_DRAFT_FAQ = 9102;
    private const int LISTED_SCHEDULED_FAQ = 9103;
    private const int LISTED_EXPIRED_FAQ = 9104;

    /**
     * One FAQ a reader can see and three a reader cannot: a draft, a published FAQ whose
     * publication window has not opened yet and one whose window has closed.
     */
    private function seedFaqsOfEveryVisibility(): void
    {
        $this->seedListedFaq(self::LISTED_PUBLISHED_FAQ, FaqStatus::Published, '00000000000000', '99991231235959');
        $this->seedListedFaq(self::LISTED_DRAFT_FAQ, FaqStatus::Draft, '00000000000000', '99991231235959');
        $this->seedListedFaq(self::LISTED_SCHEDULED_FAQ, FaqStatus::Published, '99990101000000', '99991231235959');
        $this->seedListedFaq(self::LISTED_EXPIRED_FAQ, FaqStatus::Published, '00000000000000', '20200101000000');
    }

    private function seedListedFaq(int $faqId, FaqStatus $status, string $dateStart, string $dateEnd): void
    {
        $db = $this->configuration->getDb();
        $prefix = Database::getTablePrefix();

        $db->query(sprintf(
            "INSERT INTO %sfaqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end, created, notes, sticky_order)
             VALUES (%d, 'en', %d, 0, '%s', 0, '', 'Question %d', 'Answer %d', 'Author', 'author@example.com', 'y', '20260301010101', '%s', '%s', '2026-03-01 01:01:01', '', 0)",
            $prefix,
            $faqId,
            $faqId,
            $status->value,
            $faqId,
            $faqId,
            $dateStart,
            $dateEnd,
        ));
        $db->query(sprintf('INSERT INTO %sfaqdata_user (record_id, user_id) VALUES (%d, -1)', $prefix, $faqId));
        $db->query(sprintf('INSERT INTO %sfaqdata_group (record_id, group_id) VALUES (%d, -1)', $prefix, $faqId));
        $this->relateFaqToCategory($faqId, 'en', 1);
    }

    /**
     * Forces the setting and the installation-wide API token in one step: forceConfigurationValue()
     * reloads the stored configuration first, so a second call would undo the first.
     */
    private function forceOnlyActiveFaqsAndApiToken(bool $onlyActiveFaqs): void
    {
        $this->configuration->getAll();
        $property = new \ReflectionProperty(Configuration::class, 'config');
        $config = $property->getValue($this->configuration);
        $config['api.onlyActiveFaqs'] = $onlyActiveFaqs;
        $config['api.apiClientToken'] = 'test-token';
        $property->setValue($this->configuration, $config);
    }

    /**
     * The seeded FAQs GET /faqs lists, in ascending order.
     *
     * @return int[]
     * @throws Exception
     */
    private function listedSeededFaqIds(?Request $request = null): array
    {
        $controller = new FaqController(
            new Faq($this->configuration),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        $response = $controller->list($request ?? new Request(['per_page' => 100]));
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());

        $seeded = [
            self::LISTED_PUBLISHED_FAQ,
            self::LISTED_DRAFT_FAQ,
            self::LISTED_SCHEDULED_FAQ,
            self::LISTED_EXPIRED_FAQ,
        ];
        $listed = array_map(intval(...), array_column($payload['data'], 'id'));

        return array_values(array_intersect($seeded, $listed));
    }

    /**
     * Security regression: switching "api.onlyActiveFaqs" off must not hand drafts, scheduled
     * and expired FAQs to everybody. An anonymous caller keeps the reader's view.
     *
     * @throws Exception
     */
    public function testListHidesFaqsInvisibleToReadersFromAnonymousCallersWhenInactiveFaqsAreAllowed(): void
    {
        $this->forceOnlyActiveFaqsAndApiToken(onlyActiveFaqs: false);
        $this->seedFaqsOfEveryVisibility();

        $this->assertSame([self::LISTED_PUBLISHED_FAQ], $this->listedSeededFaqIds());
    }

    /**
     * @throws Exception
     */
    public function testListIncludesFaqsInvisibleToReadersForAValidApiTokenWhenInactiveFaqsAreAllowed(): void
    {
        $this->forceOnlyActiveFaqsAndApiToken(onlyActiveFaqs: false);
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'test-token';
        $this->seedFaqsOfEveryVisibility();

        $this->assertSame(
            [
                self::LISTED_PUBLISHED_FAQ,
                self::LISTED_DRAFT_FAQ,
                self::LISTED_SCHEDULED_FAQ,
                self::LISTED_EXPIRED_FAQ,
            ],
            $this->listedSeededFaqIds(),
        );
    }

    /**
     * @throws Exception
     */
    public function testListForbidsCachingAListWidenedByACredential(): void
    {
        $this->forceOnlyActiveFaqsAndApiToken(onlyActiveFaqs: false);
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'test-token';
        $this->seedFaqsOfEveryVisibility();

        $cacheControl = $this->listCacheControl();

        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringNotContainsString('public', $cacheControl);
    }

    /**
     * @throws Exception
     */
    public function testListStaysPubliclyCacheableForAnonymousCallers(): void
    {
        $this->forceOnlyActiveFaqsAndApiToken(onlyActiveFaqs: false);
        $this->seedFaqsOfEveryVisibility();

        $cacheControl = $this->listCacheControl();

        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringNotContainsString('no-store', $cacheControl);
    }

    /**
     * @throws Exception
     */
    private function listCacheControl(): string
    {
        $controller = new FaqController(
            new Faq($this->configuration),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
            $this->configuration->getLanguage(),
        );

        return (string) $controller->list(new Request(['per_page' => 100]))->headers->get('Cache-Control');
    }

    /**
     * An invalid token is not rejected with 401 on this read endpoint: the caller is treated
     * as anonymous.
     *
     * @throws Exception
     */
    public function testListHidesFaqsInvisibleToReadersFromAnInvalidApiTokenWhenInactiveFaqsAreAllowed(): void
    {
        $this->forceOnlyActiveFaqsAndApiToken(onlyActiveFaqs: false);
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'wrong-token';
        $this->seedFaqsOfEveryVisibility();

        $this->assertSame([self::LISTED_PUBLISHED_FAQ], $this->listedSeededFaqIds());
    }

    /**
     * @throws Exception
     */
    public function testListHidesFaqsInvisibleToReadersFromAnInvalidBearerCredentialWhenInactiveFaqsAreAllowed(): void
    {
        $this->forceOnlyActiveFaqsAndApiToken(onlyActiveFaqs: false);
        $this->seedFaqsOfEveryVisibility();

        $request = new Request(['per_page' => 100]);
        $request->headers->set('Authorization', 'Bearer not-a-credential');

        $this->assertSame([self::LISTED_PUBLISHED_FAQ], $this->listedSeededFaqIds($request));
    }

    /**
     * A credential does not override the setting: with "api.onlyActiveFaqs" on, nobody gets
     * more than a reader.
     *
     * @throws Exception
     */
    public function testListHidesFaqsInvisibleToReadersFromAValidApiTokenWhenOnlyActiveFaqsAreEnabled(): void
    {
        $this->forceOnlyActiveFaqsAndApiToken(onlyActiveFaqs: true);
        $_SERVER['HTTP_X_PMF_TOKEN'] = 'test-token';
        $this->seedFaqsOfEveryVisibility();

        $this->assertSame([self::LISTED_PUBLISHED_FAQ], $this->listedSeededFaqIds());
    }
}
