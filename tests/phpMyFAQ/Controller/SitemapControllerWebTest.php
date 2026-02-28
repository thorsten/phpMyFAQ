<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SitemapController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractController::class)]
final class SitemapControllerWebTest extends ControllerWebTestCase
{
    public function testXmlSitemapReturnsXml(): void
    {
        $response = $this->requestAny('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful($response);
        self::assertSame('text/xml', $response->headers->get('Content-Type'));
        self::assertResponseContains('<?xml', $response);
    }

    public function testGzipSitemapReturnsGzipPayload(): void
    {
        $response = $this->requestAny('GET', '/sitemap.gz');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/x-gzip', $response->headers->get('Content-Type'));
        self::assertSame('gzip', $response->headers->get('Content-Encoding'));
    }

    public function testXmlGzipSitemapReturnsGzipPayload(): void
    {
        $response = $this->requestAny('GET', '/sitemap.xml.gz');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/x-gzip', $response->headers->get('Content-Type'));
        self::assertSame('gzip', $response->headers->get('Content-Encoding'));
    }
}
