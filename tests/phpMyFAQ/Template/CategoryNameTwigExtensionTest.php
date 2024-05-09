<?php

namespace phpMyFAQ\Template;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class CategoryNameTwigExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $extension = new CategoryNameTwigExtension();

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('categoryName', $filters[0]->getName());
    }
}
