<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class RobotsControllerTest extends TestCase
{
    private Environment $twig;
    private RobotsController $controller;

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->twig = $this->createMock(Environment::class);
        $this->controller = new RobotsController();
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
        $this->assertEquals('User-agent: *\nDisallow: /admin/\nSitemap: /sitemap.xml', $response->getContent());
    }
}
