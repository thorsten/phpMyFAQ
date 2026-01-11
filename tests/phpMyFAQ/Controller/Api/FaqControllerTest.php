<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class FaqControllerTest extends TestCase
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

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws Exception
     */public function testGetByCategoryIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');

        $controller = new FaqController();
        $response = $controller->getByCategoryId($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */public function testGetByIdReturnsJsonResponse(): void
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
     */public function testGetByTagIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('tagId', '1');

        $controller = new FaqController();
        $response = $controller->getByTagId($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */public function testGetPopularReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->getPopular();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */public function testGetLatestReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->getLatest();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */public function testGetTrendingReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->getTrending();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */public function testGetStickyReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->getSticky();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */public function testListReturnsJsonResponse(): void
    {
        $controller = new FaqController();
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */public function testCreateRequiresValidToken(): void
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
     */public function testUpdateRequiresValidToken(): void
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
}
