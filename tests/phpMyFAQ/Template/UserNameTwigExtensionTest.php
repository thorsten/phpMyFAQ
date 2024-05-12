<?php

namespace phpMyFAQ\Template;

use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class UserNameTwigExtensionTest extends TestCase
{
    private UserNameTwigExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new UserNameTwigExtension();
    }

    public function testGetFilters(): void
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertEquals('userName', $filters[0]->getName());
    }
}
