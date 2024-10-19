<?php

namespace phpMyFAQ\Template\Extensions;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class FaqTwigExtensionTest extends TestCase
{
    private FaqTwigExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FaqTwigExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('faqQuestion', $filters[0]->getName());
    }
}
