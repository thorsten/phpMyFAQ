<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Strategy;

use InvalidArgumentException;use phpMyFAQ\Link;
use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

final class NewsStrategyTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        Strings::init();
        $dbHandle = new Sqlite3();
        $dbHandle->connect(':memory:', '', '');
        $this->configuration = new Configuration($dbHandle);
    }

    public function testNewsUrlBuild(): void
    {
        $strategy = new NewsStrategy();
        $link = new Link('https://example.com/index.php?action=news', $this->configuration);
        $link->setTitle('Release Notes');
        $params = [
            Link::LINK_GET_NEWS_ID => '777',
            Link::LINK_GET_NEWS_LANG => 'en',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('news/777/en/release-notes.html', $url);
    }

    public function testMissingNewsIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: newsid');
        $strategy = new NewsStrategy();
        $link = new Link('https://example.com/index.php?action=news', $this->configuration);
        $link->setTitle('X');
        $strategy->build([Link::LINK_GET_NEWS_LANG => 'en'], $link);
    }

    public function testMissingNewsLangThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: newslang');
        $strategy = new NewsStrategy();
        $link = new Link('https://example.com/index.php?action=news', $this->configuration);
        $link->setTitle('X');
        $strategy->build([Link::LINK_GET_NEWS_ID => '1'], $link);
    }
}
