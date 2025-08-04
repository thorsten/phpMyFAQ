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

    /**
     * Test multibyte string functions with Unicode
     */
    public function testUnicodeStrings(): void
    {
        // Test with German umlauts
        $this->assertEquals(6, Strings::strlen('Hällö!'));
        $this->assertEquals('äll', Strings::substr('Hällö', 1, 3));
        $this->assertEquals('hällö', Strings::strtolower('HÄLLÖ'));
        $this->assertEquals('HÄLLÖ', Strings::strtoupper('hällö'));

        // Test with Japanese characters
        $japanese = 'こんにちは';
        $this->assertEquals(5, Strings::strlen($japanese));
        $this->assertEquals('こん', Strings::substr($japanese, 0, 2));
    }

    /**
     * Test regular expression functions
     */
    public function testRegexFunctions(): void
    {
        // Test pattern matching
        $this->assertEquals(1, Strings::preg_match('/\d+/', 'abc123def'));
        $this->assertEquals(0, Strings::preg_match('/\d+/', 'abcdef'));

        // Test global matching
        $matches = [];
        $this->assertEquals(3, Strings::preg_match_all('/\d/', 'a1b2c3', $matches));
        $this->assertEquals(['1', '2', '3'], $matches[0]);

        // Test replacement with regex
        $result = Strings::preg_replace('/\d+/', 'X', 'abc123def456');
        $this->assertEquals('abcXdefX', $result);

        // Test splitting with regex
        $parts = Strings::preg_split('/\s+/', 'hello   world    test');
        $this->assertEquals(['hello', 'world', 'test'], $parts);
    }

    /**
     * Test performance with large datasets
     */
    public function testPerformance(): void
    {
        $largeText = str_repeat('Lorem ipsum dolor sit amet ', 1000);

        $startTime = microtime(true);

        // Perform multiple operations
        $length = Strings::strlen($largeText);
        $upper = Strings::strtoupper($largeText);
        $parts = explode(' ', $largeText);
        $joined = implode(' ', array_slice($parts, 0, 100));

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Operations should complete within reasonable time
        $this->assertLessThan(1.0, $executionTime, 'String operations took too long');
        $this->assertGreaterThan(0, $length);
        $this->assertNotEmpty($upper);
        $this->assertIsArray($parts);
        $this->assertNotEmpty($joined);
    }
}
