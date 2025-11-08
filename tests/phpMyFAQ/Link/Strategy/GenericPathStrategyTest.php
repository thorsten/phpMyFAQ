<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Strategy;

use phpMyFAQ\Link;
use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

final class GenericPathStrategyTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        Strings::init();
        $dbHandle = new Sqlite3();
        $dbHandle->connect(':memory:', '', '');
        $this->configuration = new Configuration($dbHandle);
    }

    public function testBuildReturnsStaticPath(): void
    {
        $strategy = new GenericPathStrategy('add-faq.html');
        $link = new Link('https://example.com/index.php?action=add', $this->configuration);
        $result = $strategy->build([], $link);
        $this->assertSame('add-faq.html', $result);
    }
}
