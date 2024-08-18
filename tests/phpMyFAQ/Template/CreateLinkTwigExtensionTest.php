<?php

namespace phpMyFAQ\Template;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CreateLinkTwigExtensionTest extends TestCase
{
    private CreateLinkTwigExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new CreateLinkTwigExtension();
    }
    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(2, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertInstanceOf(TwigFunction::class, $functions[1]);
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertInstanceOf(TwigFilter::class, $filters[1]);
    }
}
