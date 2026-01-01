<?php

declare(strict_types=1);

namespace phpMyFAQ\Link;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Link;
use phpMyFAQ\Link\Strategy\StrategyInterface;
use phpMyFAQ\Link\Strategy\StrategyRegistry;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LinkStrategyRegistryDiTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        Strings::init();
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
    }

    public function testInjectEmptyRegistryMergesDefaults(): void
    {
        $registry = new StrategyRegistry();
        $link = new Link('https://example.com/index.php?action=show', $this->configuration, $registry);

        // Defaults should be merged in
        $this->assertTrue($link->getStrategyRegistry()->has(Link::LINK_GET_ACTION_SHOW));

        $showStrategy = $link->getStrategyRegistry()->get(Link::LINK_GET_ACTION_SHOW);
        $this->assertNotNull($showStrategy);

        $urlPart = $showStrategy->build([Link::LINK_GET_CATEGORY => '0'], $link);
        $this->assertSame(Link::LINK_HTML_SHOW_CATEGORIES, $urlPart);
    }

    public function testOverrideExistingStrategy(): void
    {
        $custom = new class implements StrategyInterface {
            public function build(array $params, Link $link): string
            {
                return 'overridden.html';
            }
        };

        $registry = new StrategyRegistry([
            Link::LINK_GET_ACTION_SHOW => $custom,
        ]);
        $link = new Link('https://example.com/index.php?action=show', $this->configuration, $registry);

        // ensure our custom strategy is still in place (not overridden by defaults)
        $this->assertTrue($link->getStrategyRegistry()->has(Link::LINK_GET_ACTION_SHOW));
        $strategy = $link->getStrategyRegistry()->get(Link::LINK_GET_ACTION_SHOW);
        $this->assertSame('overridden.html', $strategy->build([], $link));

        // Also verify runtime registration API
        $link->registerStrategy('custom', $custom);
        $this->assertTrue($link->getStrategyRegistry()->has('custom'));
    }
}
