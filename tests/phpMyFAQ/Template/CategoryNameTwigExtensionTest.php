<?php

namespace phpMyFAQ\Template;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CategoryNameTwigExtensionTest extends TestCase
{
    private CategoryNameTwigExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new CategoryNameTwigExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('categoryName', $filters[0]->getName());
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(1, $functions);

        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertEquals('categoryName', $functions[0]->getName());
    }
}
