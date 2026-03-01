<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SitemapController::class)]
#[UsesNamespace('phpMyFAQ')]
final class SitemapControllerWebTest extends ControllerWebTestCase
{
    public function testSitemapPageRendersDefaultHeaderForZeroLetter(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/sitemap/0/en.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Sitemap', $response);
        self::assertResponseContains('Sitemap » Sitemap', $response);
    }

    public function testSitemapPageRendersSpecificLetterHeader(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/sitemap/a/en.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Sitemap » A', $response);
    }
}
