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
class DashboardControllerTest extends TestCase
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
    public function testVerifyRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], '{}');
        $controller = new DashboardController();

        $this->expectException(\Exception::class);
        $controller->verify($request);
    }

    /**
     * @throws Exception
     */
    public function testVersionsRequiresAuthentication(): void
    {
        $controller = new DashboardController();

        $this->expectException(\Exception::class);
        $controller->versions();
    }

    /**
     * @throws Exception
     */
    public function testVisitsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new DashboardController();

        $this->expectException(\Exception::class);
        $controller->visits($request);
    }

    /**
     * @throws Exception
     */
    public function testTopTenRequiresAuthentication(): void
    {
        $controller = new DashboardController();

        $this->expectException(\Exception::class);
        $controller->topTen();
    }

    /**
     * @throws \JsonException
     */
    public function testVerifyWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new DashboardController();

        $this->expectException(\Exception::class);
        $controller->verify($request);
    }

    /**
     * @throws \JsonException
     */
    public function testVerifyWithEmptyDataThrowsException(): void
    {
        $request = new Request();
        $controller = new DashboardController();

        $this->expectException(\Exception::class);
        $controller->verify($request);
    }
}
