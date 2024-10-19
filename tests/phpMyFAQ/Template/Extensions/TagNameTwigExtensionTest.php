<?php

namespace phpMyFAQ\Template\Extensions;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TagNameTwigExtensionTest extends TestCase
{
    private TagNameTwigExtension $tagNameTwigExtension;

    protected function setUp(): void
    {
        $this->tagNameTwigExtension = new TagNameTwigExtension();
    }

    public function testGetFunctions(): void
    {
        $functions = $this->tagNameTwigExtension->getFunctions();
        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertEquals('tagName', $functions[0]->getName());
    }

    public function testGetFilters(): void
    {
        $filters = $this->tagNameTwigExtension->getFilters();
        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('tagName', $filters[0]->getName());
    }
}
