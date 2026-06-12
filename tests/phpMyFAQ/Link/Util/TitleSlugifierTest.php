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

    public function testApostrophe(): void
    {
        $this->assertSame('l_intention-de-faire', TitleSlugifier::slug("L'intention de faire"));
    }

    public function testEncodedApostrophe(): void
    {
        $this->assertSame('l_intention-de-faire', TitleSlugifier::slug('L&#039;intention de faire'));
        $this->assertSame('l_intention-de-faire', TitleSlugifier::slug('L&#39;intention de faire'));
    }
}
