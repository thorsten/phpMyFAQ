<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TemplateException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class SitemapControllerTest extends TestCase
{
    private Environment $twig;
    private SitemapController $controller;

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
        $this->controller = new SitemapController();
    }

    /**
     * @throws TemplateException
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testEmptyIndex(): void
    {
        $expectedXml =
            '<?xml version="1.0" encoding="UTF-8"?>'
            . "\n"
            . '<urlset'
            . "\n"
            . '  xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"'
            . "\n"
            . '  xmlns:xhtml="https://www.w3.org/1999/xhtml"'
            . "\n"
            . '  xmlns:image="https://www.google.com/schemas/sitemap-image/1.1"'
            . "\n"
            . '  xmlns:xsi="https://www.w3.org/2001/XMLSchema-instance"'
            . "\n"
            . '  xsi:schemaLocation="https://www.sitemaps.org/schemas/sitemap/0.9'
            . "\n"
            . '        https://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'
            . "\n"
            . '  </urlset>'
            . "\n";

        $this->twig->method('render')->willReturn($expectedXml);

        $response = $this->controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($expectedXml, $response->getContent());
    }
}
