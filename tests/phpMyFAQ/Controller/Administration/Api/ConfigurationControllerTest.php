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
class ConfigurationControllerTest extends TestCase
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
    public function testSendTestMailRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController();

        $this->expectException(\Exception::class);
        $controller->sendTestMail($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateMaintenanceModeRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController();

        $this->expectException(\Exception::class);
        $controller->activateMaintenanceMode($request);
    }

    /**
     * @throws Exception
     */
    public function testSendTestMailWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new ConfigurationController();

        $this->expectException(\Exception::class);
        $controller->sendTestMail($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateMaintenanceModeWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new ConfigurationController();

        $this->expectException(\Exception::class);
        $controller->activateMaintenanceMode($request);
    }

    /**
     * @throws Exception
     */
    public function testSendTestMailWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController();

        $this->expectException(\Exception::class);
        $controller->sendTestMail($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateMaintenanceModeWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController();

        $this->expectException(\Exception::class);
        $controller->activateMaintenanceMode($request);
    }
}
