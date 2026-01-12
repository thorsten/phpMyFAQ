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
class ElasticsearchControllerTest extends TestCase
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
    public function testCreateRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ElasticsearchController();

        $this->expectException(\Exception::class);
        $controller->create();
    }

    /**
     * @throws \Exception
     */
    public function testDropRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ElasticsearchController();

        $this->expectException(\Exception::class);
        $controller->drop();
    }

    /**
     * @throws \Exception
     */
    public function testImportRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ElasticsearchController();

        $this->expectException(\Exception::class);
        $controller->import();
    }

    /**
     * @throws \Exception
     */
    public function testStatisticsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ElasticsearchController();

        $this->expectException(\Exception::class);
        $controller->statistics();
    }

    /**
     * @throws \Exception
     */
    public function testHealthcheckRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ElasticsearchController();

        $this->expectException(\Exception::class);
        $controller->healthcheck();
    }
}
