<?php

namespace phpMyFAQ\Strings;

use PHPUnit\Framework\TestCase;

class StringBasicTest extends TestCase
{
    private StringBasic $stringBasic;

    protected function setUp(): void
    {
        $this->stringBasic = StringBasic::getStringBasic();
    }

    public function testStrlen(): void
    {
        // Test case 1: Check the length of a regular string
        $result = $this->stringBasic->strlen("Hello, World!");
        $this->assertEquals(13, $result);

        // Test case 2: Check the length of an empty string
        $result = $this->stringBasic->strlen("");
        $this->assertEquals(0, $result);

        // Test case 3: Check the length of a string with German umlauts
        $result = $this->stringBasic->strlen("äöü");
        $this->assertEquals(6, $result); // 3 characters, 6 bytes
    }

    public function testSubstr(): void
    {
        $this->assertEquals('ell', $this->stringBasic->substr('hello', 1, 3));
    }

    public function testStrpos(): void
    {
        $this->assertEquals(1, $this->stringBasic->strpos('hello', 'e'));
    }

    public function testStrtolower(): void
    {
        $this->assertEquals('hello', $this->stringBasic->strtolower('HELLO'));
    }

    public function testStrtoupper(): void
    {
        $this->assertEquals('HELLO', $this->stringBasic->strtoupper('hello'));
    }

    public function testStrstr(): void
    {
        $this->assertEquals('ello', $this->stringBasic->strstr('hello', 'e'));
        $this->assertEquals('h', $this->stringBasic->strstr('hello', 'e', true));
    }

    public function testPregMatch(): void
    {
        $this->assertEquals(1, $this->stringBasic->preg_match('/[a-z]/', 'hello'));
    }

    public function testPregMatchAll(): void
    {
        $matches = [];
        $this->assertEquals(2, $this->stringBasic->preg_match_all('/[aeiou]/', 'hello', $matches));
    }

    public function testPregSplit(): void
    {
        $this->assertEquals(['h', 'llo'], $this->stringBasic->preg_split('/e/', 'hello'));
    }

    public function testPregReplaceCallback(): void
    {
        $result = $this->stringBasic->preg_replace_callback(
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
        $this->assertEquals('h*ll*', $this->stringBasic->preg_replace('/[aeiou]/', '*', 'hello'));
    }
}
