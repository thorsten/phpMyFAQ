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
class AdminLogControllerTest extends TestCase
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
     * @throws \JsonException
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrfToken' => 'test-token']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new AdminLogController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws Exception
     */
    public function testExportRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new AdminLogController();

        $this->expectException(\Exception::class);
        $controller->export($request);
    }

    /**
     * @throws Exception
     */
    public function testVerifyRequiresAuthentication(): void
    {
        $request = new Request(['csrf' => 'test-token']);
        $controller = new AdminLogController();

        $this->expectException(\Exception::class);
        $controller->verify($request);
    }

    /**
     * @throws \JsonException
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new AdminLogController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws Exception
     */
    public function testExportWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new AdminLogController();

        $this->expectException(\Exception::class);
        $controller->export($request);
    }
}
