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
class QuestionControllerTest extends TestCase
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

    public function testCreateReturnsJsonResponse(): void
    {
        $requestData = json_encode([
            'category-id' => 1,
            'question' => 'Is this a test question?',
            'author' => 'Test Author',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateRequiresValidToken(): void
    {
        $requestData = json_encode([
            'category-id' => 1,
            'question' => 'Is this a test question?',
            'author' => 'Test Author',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateRequiresAllRequiredFields(): void
    {
        $requestData = json_encode([
            'category-id' => 1,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
