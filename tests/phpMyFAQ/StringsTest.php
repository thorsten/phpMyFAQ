<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

class StringsTest extends TestCase
{
    protected function setUp(): void
    {
        Strings::init();
    }

    public function testStrlen(): void
    {
        $this->assertEquals(13, Strings::strlen('Hello, World!'));
    }

    public function testSubstr(): void
    {
        $this->assertEquals('ell', Strings::substr('hello', 1, 3));
    }

    public function testStrtolower(): void
    {
        $this->assertEquals('hello', Strings::strtolower('HELLO'));
    }

    public function testStrtoupper(): void
    {
        $this->assertEquals('HELLO', Strings::strtoupper('hello'));
    }

    public function testStrstr(): void
    {
        $this->assertEquals('ello', Strings::strstr('hello', 'e'));
        $this->assertEquals('h', Strings::strstr('hello', 'e', true));
    }

    public function testPregMatch(): void
    {
        $this->assertEquals(1, Strings::preg_match('/[a-z]/', 'hello'));
    }

    public function testPregMatchAll(): void
    {
        $matches = [];
        $this->assertEquals(2, Strings::preg_match_all('/[aeiou]/', 'hello', $matches));
    }

    public function testPregSplit(): void
    {
        $this->assertEquals(['h', 'llo'], Strings::preg_split('/e/', 'hello'));
    }

    public function testPregReplaceCallback(): void
    {
        $result = Strings::preg_replace_callback(
            '/[a-z]/',
            function ($matches) {
                return strtoupper($matches[0]);
            },
            'hello'
        );
        $this->assertEquals('HELLO', $result);
    }

    public function testPregReplace(): void
    {
        $this->assertEquals('h*ll*', Strings::preg_replace('/[aeiou]/', '*', 'hello'));
    }

    public function testHtmlspecialchars(): void
    {
        $this->assertEquals('&lt;div&gt;', Strings::htmlspecialchars('<div>'));
    }

    public function testHtmlentities(): void
    {
        $this->assertEquals('&lt;div&gt;', Strings::htmlentities('<div>'));
    }
}
