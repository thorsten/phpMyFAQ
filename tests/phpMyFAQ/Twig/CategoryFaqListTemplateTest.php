<?php

namespace phpMyFAQ\Twig;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CategoryFaqListTemplateTest extends TestCase
{
    private TwigWrapper $twigWrapper;

    protected function setUp(): void
    {
        parent::setUp();

        TwigWrapper::setTemplateSetName();
        $this->twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    }

    public function testRendersCategoryFaqListMarkup(): void
    {
        $template = $this->twigWrapper->loadTemplate('category-faq-list.twig');

        $html = $template->render([
            'page' => 1,
            'pages' => 2,
            'msgPage' => 'Page',
            'msgVoteFrom' => 'of',
            'msgPages' => 'Pages',
            'items' => [
                [
                    'anchor' => '<a href="/content/1/5/en/my-faq.html" class="text-decoration-none">My FAQ</a>',
                    'preview' => 'A short preview',
                    'views' => '3 Views',
                    'sticky' => true,
                ],
                [
                    'anchor' => '<a href="/content/1/6/en/other-faq.html" class="text-decoration-none">Other FAQ</a>',
                    'preview' => 'Another preview',
                    'views' => '1 View',
                    'sticky' => false,
                ],
            ],
            'pagination' => '<nav class="pmf-pagination">PAGER</nav>',
        ]);

        // List scaffolding
        $this->assertStringContainsString('<ul class="list-group list-group-flush mb-4 pmf-category-faq-list">', $html);
        $this->assertStringContainsString('list-group-item d-flex justify-content-between align-items-start', $html);
        $this->assertStringContainsString('badge text-bg-primary rounded-pill', $html);

        // Page indicator (pages > 1)
        $this->assertStringContainsString('<strong>1</strong>', $html);
        $this->assertStringContainsString('<strong>2</strong>', $html);

        // Sticky item gets the sticky classes, non-sticky does not
        $this->assertStringContainsString('list-group-item-primary rounded mb-3', $html);

        // Reusable components are passed through raw
        $this->assertStringContainsString(
            '<a href="/content/1/5/en/my-faq.html" class="text-decoration-none">My FAQ</a>',
            $html,
        );
        $this->assertStringContainsString('A short preview', $html);
        $this->assertStringContainsString('3 Views', $html);
        $this->assertStringContainsString('<nav class="pmf-pagination">PAGER</nav>', $html);
    }

    public function testOmitsPageIndicatorForSinglePage(): void
    {
        $template = $this->twigWrapper->loadTemplate('category-faq-list.twig');

        $html = $template->render([
            'page' => 1,
            'pages' => 1,
            'msgPage' => 'Page',
            'msgVoteFrom' => 'of',
            'msgPages' => 'Pages',
            'items' => [
                [
                    'anchor' => '<a href="/content/1/5/en/my-faq.html">My FAQ</a>',
                    'preview' => 'A short preview',
                    'views' => '3 Views',
                    'sticky' => false,
                ],
            ],
            'pagination' => '',
        ]);

        $this->assertStringContainsString('pmf-category-faq-list', $html);
        $this->assertStringNotContainsString('<strong>', $html);
    }
}
