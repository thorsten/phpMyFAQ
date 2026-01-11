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
class PdfControllerTest extends TestCase
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
     */
    public function testGetByIdReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('faqId', '1');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('faqId', '1');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithNonExistentFaq(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '999999');
        $request->attributes->set('faqId', '999999');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testGetByIdReturnsJsonData(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('faqId', '1');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithZeroIds(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '0');
        $request->attributes->set('faqId', '0');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithInvalidCategoryId(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', 'invalid');
        $request->attributes->set('faqId', '1');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdWithInvalidFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('faqId', 'invalid');

        $controller = new PdfController();
        $response = $controller->getById($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
