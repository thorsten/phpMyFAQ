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
        $response = $this->controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        // Check that it's valid XML with the sitemap structure
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"', $content);
        $this->assertStringContainsString('</urlset>', $content);
    }
}
