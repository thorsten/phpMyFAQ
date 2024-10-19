<?php

namespace phpMyFAQ\Template\Extensions;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class FormatBytesTwigExtensionTest extends TestCase
{
    private FormatBytesTwigExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FormatBytesTwigExtension();
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('formatBytes', $filters[0]->getName());
    }
}
