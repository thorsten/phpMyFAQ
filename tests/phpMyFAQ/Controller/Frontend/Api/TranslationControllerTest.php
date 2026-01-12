<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class TranslationControllerTest extends TestCase
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

    public function testTranslationsWithSupportedLanguageReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testTranslationsWithSupportedLanguageReturnsOk(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testTranslationsWithUnsupportedLanguageReturnsBadRequest(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'xyz');

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testTranslationsWithUnsupportedLanguageReturnsError(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'invalid');

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testTranslationsReturnsValidJsonContent(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $this->assertJson($response->getContent());
    }

    public function testTranslationsReturnsArrayData(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testTranslationsResponseHasCorrectContentType(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function testTranslationsWithMultipleSupportedLanguages(): void
    {
        $languages = ['en', 'de', 'fr', 'es'];

        foreach ($languages as $lang) {
            $request = new Request();
            $request->attributes->set('language', $lang);

            $controller = new TranslationController();
            $response = $controller->translations($request);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_BAD_REQUEST]);
        }
    }

    public function testTranslationsResponseIsNotEmpty(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }
}
