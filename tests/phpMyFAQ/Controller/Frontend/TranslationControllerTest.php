<?php

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class TranslationControllerTest extends TestCase
{
    /**
     * @throws \phpMyFAQ\Core\Exception
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
    }

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testTranslationsWithSupportedLanguage(): void
    {
        $language = 'en';
        Translation::getInstance()->setCurrentLanguage($language);
        $translations = Translation::getAll();

        $request = new Request([], [], ['language' => $language]);

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($translations, json_decode($response->getContent(), true));
    }

    /**
     * @throws Exception
     */
    public function testTranslationsWithUnsupportedLanguage(): void
    {
        $language = 'unsupported';

        $request = new Request([], [], ['language' => $language]);

        $controller = new TranslationController();
        $response = $controller->translations($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(['error' => 'Language not supported'], json_decode($response->getContent(), true));
    }
}
