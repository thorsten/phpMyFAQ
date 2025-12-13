<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Strategy;

use phpMyFAQ\Link;
use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ShowStrategyTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        Strings::init();
        $dbHandle = new Sqlite3();
        $dbHandle->connect(':memory:', '', '');
        $this->configuration = new Configuration($dbHandle);
    }

    public function testShowCategoriesWhenCatZero(): void
    {
        $strategy = new ShowStrategy();
        $link = new Link('https://example.com/index.php?action=show', $this->configuration);
        $link->setTitle('Category Title');
        $params = [
            Link::LINK_GET_CATEGORY => '0',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('show-categories.html', $url);
    }

    public function testShowCategoryWithPageAndSlug(): void
    {
        $strategy = new ShowStrategy();
        $link = new Link('https://example.com/index.php?action=show', $this->configuration);
        $link->setTitle('My Category');
        $params = [
            Link::LINK_GET_CATEGORY => '55',
            Link::LINK_GET_PAGE => '2',
        ];
        $url = $strategy->build($params, $link);
        $this->assertSame('category/55/2/my-category.html', $url);
    }
}
