<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Seo\SitemapXmlService;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class SitemapControllerTest extends TestCase
{
    private SitemapXmlService $sitemapXmlService;
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

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        new Configuration($dbHandle);

        $this->sitemapXmlService = $this->createStub(SitemapXmlService::class);
        $this->controller = new SitemapController($this->sitemapXmlService);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testEmptyIndex(): void
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>'
            . "\n"
            . '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';

        $this->sitemapXmlService->method('generateXml')->willReturn($xml);

        $response = $this->controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('text/xml', $response->headers->get('Content-Type'));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"', $content);
        $this->assertStringContainsString('</urlset>', $content);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testIndexReturns404WhenDisabled(): void
    {
        $this->sitemapXmlService->method('generateXml')->willReturn(null);

        $response = $this->controller->index();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('XML Sitemap is disabled.', $response->getContent());
    }
}
