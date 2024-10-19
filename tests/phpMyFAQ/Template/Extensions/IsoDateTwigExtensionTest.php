<?php

namespace phpMyFAQ\Template\Extensions;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class IsoDateTwigExtensionTest extends TestCase
{
    private IsoDateTwigExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new IsoDateTwigExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('createIsoDate', $filters[0]->getName());
    }
}
