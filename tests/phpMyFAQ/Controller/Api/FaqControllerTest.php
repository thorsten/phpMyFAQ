<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\MetaData as FaqMetaData;
use phpMyFAQ\Faq\Statistics as FaqStatistics;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
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
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_PMF_TOKEN']);

        parent::tearDown();
    }

    private function createConfiguration(): Configuration
    {
        try {
            return Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $db = new Sqlite3();
            $db->connect(PMF_TEST_DIR . '/test.db', '', '');
            $configuration = new Configuration($db);

            $configurationReflection = new \ReflectionClass(Configuration::class);
            $configurationProperty = $configurationReflection->getProperty('configuration');
            $configurationProperty->setValue(null, $configuration);

            return $configuration;
        }
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
     * @throws Exception
     */ public function testGetByCategoryIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );
        $response = $controller->getByCategoryId($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetByIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws \Exception
     */ public function testGetByTagIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('tagId', '1');

        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );
        $response = $controller->getByTagId($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetPopularReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );
        $response = $controller->getPopular();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetLatestReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );
        $response = $controller->getLatest();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetTrendingReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );
        $response = $controller->getTrending();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetStickyReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );
        $response = $controller->getSticky();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testListReturnsJsonResponse(): void
    {
        $controller = new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateRequiresValidToken(): void
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
        );

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */ public function testUpdateRequiresValidToken(): void
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
        );
        $response = $controller->getPopular();

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
        );
        $response = $controller->getLatest();

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
        );
        $response = $controller->getTrending();

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
        );
        $response = $controller->getSticky();

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
        );
        $response = $controller->getPopular();

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
        );
        $response = $controller->getLatest();

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
        );
        $response = $controller->getTrending();

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
        );
        $response = $controller->getSticky();

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
                'active' => 'yes',
                'question' => 'Active FAQ',
            ]);

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
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
                'active' => 'no',
            ]);

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
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
                    && $entity->isActive() === true
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
        );

        $response = $controller->create($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());
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
        );

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
        );

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
        $faq->expects($this->once())->method('hasTitleAHash')->with('Updated via API?')->willReturn(false);
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
                    && $entity->isActive() === true
                    && $entity->isSticky() === true
                );
            }))
            ->willReturn(new FaqEntity()->setId(7));

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );

        $response = $controller->update($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"stored":true}', (string) $response->getContent());
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
        $faq->expects($this->once())->method('hasTitleAHash')->with('Updated # bad?')->willReturn(true);
        $faq->expects($this->never())->method('update');

        $controller = new FaqController(
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(FaqStatistics::class),
            $this->createStub(FaqMetaData::class),
        );

        $response = $controller->update($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            ['stored' => false, 'error' => 'It is not allowed, that the question title contains a hash.'],
            $payload,
        );
    }
}
