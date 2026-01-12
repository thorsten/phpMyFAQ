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

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'data' => [
                'pmf-csrf-token' => 'test-token',
                'questions[]' => [1],
            ],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testToggleRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrfToken' => 'test-token', 'questionId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->toggle($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testToggleWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->toggle($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'data' => [
                'questions[]' => [1],
            ],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testToggleWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['questionId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new QuestionController();

        $this->expectException(\Exception::class);
        $controller->toggle($request);
    }
}
