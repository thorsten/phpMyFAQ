<?php

namespace phpMyFAQ\Template;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LanguageCodeTwigExtensionTest extends TestCase
{
    private LanguageCodeTwigExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new LanguageCodeTwigExtension();
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(1, $functions);

        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertEquals('getFromLanguageCode', $functions[0]->getName());
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('getFromLanguageCode', $filters[0]->getName());
    }
}
