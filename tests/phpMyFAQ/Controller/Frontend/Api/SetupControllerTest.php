<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class SetupControllerTest extends TestCase
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
    public function testIndexReturnsResponse(): void
    {
        $request = new Request();
        $controller = new SetupController();
        $response = $controller->index($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsOkStatusCode(): void
    {
        $request = new Request();
        $controller = new SetupController();
        $response = $controller->index($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testInstallReturnsResponse(): void
    {
        $controller = new SetupController();
        $response = $controller->install();

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testInstallReturnsOkStatusCode(): void
    {
        $controller = new SetupController();
        $response = $controller->install();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsResponse(): void
    {
        $request = new Request();
        $controller = new SetupController();
        $response = $controller->update($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsOkStatusCode(): void
    {
        $request = new Request();
        $controller = new SetupController();
        $response = $controller->update($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateWithStepParameter(): void
    {
        $request = new Request(['step' => '2']);
        $controller = new SetupController();
        $response = $controller->update($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testRenderReturnsResponse(): void
    {
        $controller = new SetupController();
        $response = $controller->render('@setup/index.twig', []);

        $this->assertInstanceOf(Response::class, $response);
    }
}
