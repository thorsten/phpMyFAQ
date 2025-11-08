<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Strategy;

use phpMyFAQ\Link;
use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

final class SitemapStrategyTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        Strings::init();
        $dbHandle = new Sqlite3();
        $dbHandle->connect(':memory:', '', '');
        $this->configuration = new Configuration($dbHandle);
    }

    public function testSitemapDefaultLetter(): void
    {
        $strategy = new SitemapStrategy();
        $link = new Link('https://example.com/index.php?action=sitemap', $this->configuration);
        $params = [
            Link::LINK_GET_LANG => 'en',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('sitemap/A/en.html', $url);
    }

    public function testSitemapCustomLetter(): void
    {
        $strategy = new SitemapStrategy();
        $link = new Link('https://example.com/index.php?action=sitemap', $this->configuration);
        $params = [
            Link::LINK_GET_LANG => 'de',
            Link::LINK_GET_LETTER => 'Z',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('sitemap/Z/de.html', $url);
    }
}
