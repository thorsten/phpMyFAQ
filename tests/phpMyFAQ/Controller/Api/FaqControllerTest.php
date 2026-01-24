<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
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

        $this->configuration = Configuration::getConfigurationInstance();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    /**
     * @throws Exception
     */ public function testGetByCategoryIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController();
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

        $controller = new FaqController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws \Exception
     */ public function testGetByTagIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('tagId', '1');

        $controller = new FaqController();
        $response = $controller->getByTagId($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetPopularReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->getPopular();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetLatestReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->getLatest();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetTrendingReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->getTrending();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testGetStickyReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->getSticky();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */ public function testListReturnsJsonResponse(): void
    {
        $controller = new FaqController();
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
        $controller = new FaqController();

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
        $controller = new FaqController();

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

        $controller = new FaqController();
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

        $controller = new FaqController();
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

        $controller = new FaqController();
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

        $controller = new FaqController();
        $response = $controller->getByTagId($request);

        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    /**
     * @throws Exception
     */
    public function testGetPopularReturnsValidStatusCode(): void
    {
        $controller = new FaqController();
        $response = $controller->getPopular();

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetLatestReturnsValidStatusCode(): void
    {
        $controller = new FaqController();
        $response = $controller->getLatest();

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetTrendingReturnsValidStatusCode(): void
    {
        $controller = new FaqController();
        $response = $controller->getTrending();

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetStickyReturnsValidStatusCode(): void
    {
        $controller = new FaqController();
        $response = $controller->getSticky();

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsValidStatusCode(): void
    {
        $controller = new FaqController();
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

        $controller = new FaqController();
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

        $controller = new FaqController();
        $response = $controller->getById($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListReturnsArrayData(): void
    {
        $controller = new FaqController();
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
        $controller = new FaqController();

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
        $controller = new FaqController();

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

        $controller = new FaqController();
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

        $controller = new FaqController();
        $response = $controller->getById($request);

        $this->assertTrue($response->headers->has('Content-Type'));
    }

    /**
     * @throws Exception
     */
    public function testGetPopularResponseIsNotEmpty(): void
    {
        $controller = new FaqController();
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
        $controller = new FaqController();
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
        $controller = new FaqController();
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
        $controller = new FaqController();
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
        $controller = new FaqController();
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

            $controller = new FaqController();
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

            $controller = new FaqController();
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

            $controller = new FaqController();
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

        $controller = new FaqController();
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

        $controller = new FaqController();
        $response = $controller->getById($request);

        $content = $response->getContent();
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertThat($data, $this->logicalOr($this->isNull(), $this->isArray()));
    }
}
