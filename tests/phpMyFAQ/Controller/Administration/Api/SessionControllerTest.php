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
class SessionControllerTest extends TestCase
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
    public function testExportRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'firstHour' => '2024-01-01',
            'lastHour' => '2024-01-31',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new SessionController();

        $this->expectException(\Exception::class);
        $controller->export($request);
    }

    /**
     * @throws Exception
     */
    public function testExportWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new SessionController();

        $this->expectException(\Exception::class);
        $controller->export($request);
    }

    /**
     * @throws Exception
     */
    public function testExportWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'firstHour' => '2024-01-01',
            'lastHour' => '2024-01-31',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new SessionController();

        $this->expectException(\Exception::class);
        $controller->export($request);
    }

    /**
     * @throws Exception
     */
    public function testExportWithMissingDatesThrowsException(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new SessionController();

        $this->expectException(\Exception::class);
        $controller->export($request);
    }
}
