<?php

namespace phpMyFAQ\Template\Extensions;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class FormatDateTwigExtensionTest extends TestCase
{
    private FormatDateTwigExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FormatDateTwigExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('formatDate', $filters[0]->getName());
    }
}
