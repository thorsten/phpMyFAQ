<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Strategy;

use InvalidArgumentException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FaqStrategyTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        Strings::init();
        $dbHandle = new Sqlite3();
        $dbHandle->connect(':memory:', '', '');
        $this->configuration = new Configuration($dbHandle);
    }

    public function testBuildBasicFaqUrl(): void
    {
        $strategy = new FaqStrategy();
        $link = new Link('https://example.com/index.php?action=faq', $this->configuration);
        $link->setTitle('HD Ready');
        $params = [
            Link::LINK_GET_CATEGORY => '12',
            Link::LINK_GET_ID => '34',
            Link::LINK_GET_ARTLANG => 'en',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('content/12/34/en/hd-ready.html', $url);
    }

    public function testBuildFaqWithHighlightAndFragment(): void
    {
        $strategy = new FaqStrategy();
        $link = new Link('https://example.com/index.php?action=faq', $this->configuration);
        $link->setTitle('HD Ready');
        $params = [
            Link::LINK_GET_CATEGORY => '5',
            Link::LINK_GET_ID => '99',
            Link::LINK_GET_ARTLANG => 'de',
            Link::LINK_GET_HIGHLIGHT => 'monitor',
            Link::LINK_FRAGMENT_SEPARATOR => 'top',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('content/5/99/de/hd-ready.html?highlight=monitor#top', $url);
    }

    public function testMissingCategoryThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: category');
        $strategy = new FaqStrategy();
        $link = new Link('https://example.com/index.php?action=faq', $this->configuration);
        $link->setTitle('X');
        $strategy->build([Link::LINK_GET_ID => '1'], $link);
    }

    public function testMissingIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: id');
        $strategy = new FaqStrategy();
        $link = new Link('https://example.com/index.php?action=faq', $this->configuration);
        $link->setTitle('X');
        $strategy->build([Link::LINK_GET_CATEGORY => '1'], $link);
    }
}
