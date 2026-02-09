<?php

namespace phpMyFAQ\Bootstrap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpConfigurator::class)]
class PhpConfiguratorTest extends TestCase
{
    public function testFixIncludePathEnsuresDotIsPresent(): void
    {
        PhpConfigurator::fixIncludePath();

        $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
        $this->assertContains('.', $paths);
    }

    public function testConfigurePcreSetsLimits(): void
    {
        PhpConfigurator::configurePcre();

        $this->assertEquals('100000000', ini_get('pcre.backtrack_limit'));
        $this->assertEquals('100000000', ini_get('pcre.recursion_limit'));
    }
}
