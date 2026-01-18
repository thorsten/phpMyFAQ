<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * @throws \Exception
     */
    public function testTranslateRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'contentType' => 'faq',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['question' => 'What is phpMyFAQ?'],
            'pmf-csrf-token' => 'test-token',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TranslationController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new TranslationController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'contentType' => 'faq',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['question' => 'What is phpMyFAQ?'],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TranslationController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateWithMissingContentTypeThrowsException(): void
    {
        $requestData = json_encode([
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['question' => 'What is phpMyFAQ?'],
            'pmf-csrf-token' => 'test-token',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TranslationController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateWithMissingSourceLanguageThrowsException(): void
    {
        $requestData = json_encode([
            'contentType' => 'faq',
            'targetLang' => 'de',
            'fields' => ['question' => 'What is phpMyFAQ?'],
            'pmf-csrf-token' => 'test-token',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TranslationController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateWithMissingTargetLanguageThrowsException(): void
    {
        $requestData = json_encode([
            'contentType' => 'faq',
            'sourceLang' => 'en',
            'fields' => ['question' => 'What is phpMyFAQ?'],
            'pmf-csrf-token' => 'test-token',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TranslationController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateWithEmptyFieldsThrowsException(): void
    {
        $requestData = json_encode([
            'contentType' => 'faq',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => [],
            'pmf-csrf-token' => 'test-token',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TranslationController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateWithInvalidContentTypeThrowsException(): void
    {
        $requestData = json_encode([
            'contentType' => 'invalid',
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'fields' => ['question' => 'What is phpMyFAQ?'],
            'pmf-csrf-token' => 'test-token',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new TranslationController();

        $this->expectException(\Exception::class);
        $controller->translate($request);
    }
}
