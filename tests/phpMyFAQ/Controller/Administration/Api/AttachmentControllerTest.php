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
class AttachmentControllerTest extends TestCase
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
        $requestData = json_encode(['csrf' => 'test-token', 'attId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testRefreshRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'attId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->refresh($request);
    }

    /**
     * @throws \Exception
     */
    public function testUploadRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->upload($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testRefreshWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->refresh($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['attId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testRefreshWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['attId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->refresh($request);
    }
}
