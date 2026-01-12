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
class ExportControllerTest extends TestCase
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
    public function testExportFileRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ExportController();

        $this->expectException(\Exception::class);
        $controller->exportFile($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportReportRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'data' => [
                'pmf-csrf-token' => 'test-token',
            ],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ExportController();

        $this->expectException(\Exception::class);
        $controller->exportReport($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportReportWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new ExportController();

        $this->expectException(\Exception::class);
        $controller->exportReport($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportReportWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'data' => [],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ExportController();

        $this->expectException(\Exception::class);
        $controller->exportReport($request);
    }
}
