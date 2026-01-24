<?php

namespace phpMyFAQ\Strings;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MbstringTest extends TestCase
{
    private Mbstring $mbString;

    protected function setUp(): void
    {
        $this->mbString = Mbstring::getInstance();
    }

    public function testStrlen(): void
    {
        // Test case 1: Check the length of a regular string
        $result = $this->mbString->strlen('Hello, World!');
        $this->assertEquals(13, $result);

        // Test case 2: Check the length of an empty string
        $result = $this->mbString->strlen('');
        $this->assertEquals(0, $result);

        // Test case 3: Check the length of a string with German umlauts
        $result = $this->mbString->strlen('äöü');
        $this->assertEquals(3, $result); // 3 characters, 6 bytes
    }

    public function testSubstr(): void
    {
        $this->assertEquals('ell', $this->mbString->substr('hello', 1, 3));
    }

    public function testStrtolower(): void
    {
        $this->assertEquals('hello', $this->mbString->strtolower('HELLO'));
    }

    public function testStrtoupper(): void
    {
        $this->assertEquals('HELLO', $this->mbString->strtoupper('hello'));
    }

    public function testStrstr(): void
    {
        $this->assertEquals('ello', $this->mbString->strstr('hello', 'e'));
        $this->assertEquals('h', $this->mbString->strstr('hello', 'e', true));
    }

    public function testPregMatch(): void
    {
        $this->assertEquals(1, $this->mbString->preg_match('/[a-z]/', 'hello'));
    }

    public function testPregMatchAll(): void
    {
        $matches = [];
        $this->assertEquals(2, $this->mbString->preg_match_all('/[aeiou]/', 'hello', $matches));
    }

    public function testPregSplit(): void
    {
        $this->assertEquals(['h', 'llo'], $this->mbString->preg_split('/e/', 'hello'));
    }

    public function testPregReplaceCallback(): void
    {
        $result = $this->mbString->preg_replace_callback(
            '/[a-z]/',
            function ($matches) {
                return strtoupper($matches[0]);
            },
            'hello',
        );
        $this->assertEquals('HELLO', $result);
    }

    public function testPregReplace(): void
    {
        $this->assertEquals('h*ll*', $this->mbString->preg_replace('/[aeiou]/', '*', 'hello'));
    }
}
