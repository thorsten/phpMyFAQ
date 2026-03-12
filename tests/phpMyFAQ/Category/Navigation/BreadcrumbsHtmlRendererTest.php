<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Navigation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Link;
use phpMyFAQ\Link\Strategy\GenericPathStrategy;
use phpMyFAQ\Link\Strategy\ShowStrategy;
use phpMyFAQ\Link\Strategy\StrategyRegistry;
use phpMyFAQ\Link\Util\LinkQueryParser;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Strings;
use phpMyFAQ\Strings\AbstractString;
use phpMyFAQ\Strings\Mbstring;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(BreadcrumbsHtmlRenderer::class)]
#[UsesClass(Link::class)]
#[UsesClass(GenericPathStrategy::class)]
#[UsesClass(ShowStrategy::class)]
#[UsesClass(StrategyRegistry::class)]
#[UsesClass(LinkQueryParser::class)]
#[UsesClass(TitleSlugifier::class)]
#[UsesClass(Strings::class)]
#[UsesClass(AbstractString::class)]
#[UsesClass(Mbstring::class)]
final class BreadcrumbsHtmlRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Strings::init('en');
    }

    public function testRenderUsesDefaultBreadcrumbClassAndEscapesOutput(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/');

        $renderer = new BreadcrumbsHtmlRenderer();
        $html = $renderer->render($configuration, [
            [
                'id' => -1,
                'name' => 'Home & Start',
                'description' => 'Main <page>',
            ],
            [
                'id' => 12,
                'name' => 'Category <One>',
                'description' => 'Desc & more',
            ],
        ]);

        self::assertStringStartsWith('<ol class="breadcrumb">', $html);
        self::assertStringContainsString('href="https://localhost/"', $html);
        self::assertStringContainsString('rel="index"', $html);
        self::assertStringContainsString('title="Main &lt;page&gt;"', $html);
        self::assertStringContainsString('Home &amp; Start', $html);
        self::assertStringContainsString('href="https://localhost/category/12/category-&amp;ltone&amp;gt.html"', $html);
        self::assertStringContainsString('Category &lt;One&gt;', $html);
        self::assertStringContainsString('title="Desc &amp; more"', $html);
        self::assertStringEndsWith('</ol>', $html);
    }

    public function testRenderUsesCustomCssClassAndEmptyDescriptionFallback(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/faq/');

        $renderer = new BreadcrumbsHtmlRenderer();
        $html = $renderer->render(
            $configuration,
            [
                [
                    'id' => 99,
                    'name' => 'Only Segment',
                    'description' => '',
                ],
            ],
            'breadcrumb breadcrumb-lg',
        );

        self::assertStringStartsWith('<ol class="breadcrumb breadcrumb-lg">', $html);
        self::assertStringContainsString('href="https://localhost/faq/category/99/only-segment.html"', $html);
        self::assertStringContainsString('rel="index"', $html);
        self::assertSame(1, substr_count($html, '<li class="breadcrumb-item">'));
    }

    public function testRenderReturnsEmptyListForNoSegments(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://localhost/');

        $renderer = new BreadcrumbsHtmlRenderer();

        self::assertSame('<ol class="breadcrumb"></ol>', $renderer->render($configuration, []));
    }
}
