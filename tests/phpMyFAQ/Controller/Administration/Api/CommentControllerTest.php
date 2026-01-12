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
class CommentControllerTest extends TestCase
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
                'comments[]' => [1],
            ],
            'type' => 'faq',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'data' => [
                'comments[]' => [1],
            ],
            'type' => 'faq',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }
}
