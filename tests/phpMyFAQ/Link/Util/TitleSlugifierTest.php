<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Util;

use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TitleSlugifierTest extends TestCase
{
    protected function setUp(): void
    {
        Strings::init();
    }

    public function testBasic(): void
    {
        $this->assertSame('hello-world', TitleSlugifier::slug('Hello World'));
    }

    public function testUmlauts(): void
    {
        $this->assertSame('hd-raedy', TitleSlugifier::slug('HD Rädy'));
    }

    public function testMultipleSpacesAndPunctuation(): void
    {
        $this->assertSame('foo-bar', TitleSlugifier::slug(' Foo   , bar !! '));
    }

    public function testKeepsSingleDash(): void
    {
        $this->assertSame('foo-bar-baz', TitleSlugifier::slug('foo   bar---baz'));
    }
}
