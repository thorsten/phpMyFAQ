<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Strategy;

use phpMyFAQ\Link;
use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

final class SearchStrategyTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        Strings::init();
        $dbHandle = new Sqlite3();
        $dbHandle->connect(':memory:', '', '');
        $this->configuration = new Configuration($dbHandle);
    }

    public function testTagSearchWithPage(): void
    {
        $strategy = new SearchStrategy();
        $link = new Link('https://example.com/index.php?action=search', $this->configuration);
        $link->setTitle('My Tag Title');
        $params = [
            Link::LINK_GET_TAGGING_ID => '42',
            Link::LINK_GET_PAGE => '3',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('tags/42/3/my-tag-title.html', $url);
    }

    public function testNormalSearchWithQueryAndPageAndLangs(): void
    {
        $strategy = new SearchStrategy();
        $link = new Link('https://example.com/index.php?action=search', $this->configuration);
        $link->setTitle('Search Title');
        $params = [
            Link::LINK_GET_ACTION_SEARCH => 'term',
            Link::LINK_GET_PAGE => '2',
            Link::LINK_GET_LANGS => 'en,de',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('search.html?search=term&amp;seite=2&amp;langs=en,de', $url);
    }

    public function testNormalSearchWithoutPage(): void
    {
        $strategy = new SearchStrategy();
        $link = new Link('https://example.com/index.php?action=search', $this->configuration);
        $link->setTitle('Search Title');
        $params = [
            Link::LINK_GET_ACTION_SEARCH => 'lcd',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('search.html?search=lcd', $url);
    }
}
