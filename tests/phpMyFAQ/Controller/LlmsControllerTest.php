<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class LlmsControllerTest extends TestCase
{
    private Environment $twig;
    private LlmsController $controller;

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
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

        $this->twig = $this->createStub(Environment::class);
        $this->controller = new LlmsController();
    }

    /**
     * @throws \Exception
     */
    public function testIndex(): void
    {
        $response = $this->controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('phpMyFAQ LLMs.txt', $response->getContent());
        $this->assertStringContainsString('LLM training data availability', $response->getContent());
    }
}
