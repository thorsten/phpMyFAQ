<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;

/**
 * Class WebAuthnControllerTest
 */
#[AllowMockObjectsWithoutExpectations]
class WebAuthnControllerTest extends TestCase
{
    private WebAuthnController $controller;
    private Request $request;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->controller = new WebAuthnController();
        $this->request = new Request();

        // Set up language in configuration to prevent getLanguage() errors
        $reflection = new ReflectionClass($this->controller);
        $configProperty = $reflection->getProperty('configuration');
        $configuration = $configProperty->getValue($this->controller);
        $sessionProperty = $reflection->getProperty('session');
        $session = $sessionProperty->getValue($this->controller);

        if ($configuration instanceof Configuration) {
            $language = new Language($configuration, $session);
            $language->setLanguageFromConfiguration('en');
            $configuration->setLanguage($language);
        }
    }

    public function testConstructorCreatesInstance(): void
    {
        $controller = new WebAuthnController();

        $this->assertInstanceOf(WebAuthnController::class, $controller);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     */
    public function testIndexReturnsResponse(): void
    {
        $response = $this->controller->index($this->request);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     */
    public function testIndexResponseHasCorrectStatusCode(): void
    {
        $response = $this->controller->index($this->request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws Exception
     * @throws LoaderError
     */
    public function testIndexResponseContainsContent(): void
    {
        $response = $this->controller->index($this->request);

        $this->assertNotEmpty($response->getContent());
    }
}
