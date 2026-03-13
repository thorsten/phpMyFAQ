<?php

namespace phpMyFAQ\Strings;

use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Mbstring::class)]
#[UsesClass(Language::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageCodes::class)]
class MbstringTest extends TestCase
{
    private Mbstring $mbString;

    protected function setUp(): void
    {
        $this->resetMbstringSingleton();
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

    public function testGetInstanceFallsBackToDefaultLanguageForUnsupportedLanguage(): void
    {
        $this->resetMbstringSingleton();

        $instance = Mbstring::getInstance('unsupported-language');

        self::assertSame(AbstractString::DEFAULT_LANGUAGE, $this->readLanguage($instance));
        self::assertSame(AbstractString::DEFAULT_ENCODING, $this->readEncoding($instance));
    }

    public function testGetInstanceReturnsSameSingletonInstance(): void
    {
        $this->resetMbstringSingleton();

        $first = Mbstring::getInstance('en');
        $second = Mbstring::getInstance('de');

        self::assertSame($first, $second);
        self::assertSame('en', $this->readLanguage($second));
    }

    public function testPregReplaceCallbackHandlesPatternArrays(): void
    {
        $count = 0;
        $result = $this->mbString->preg_replace_callback(
            ['/ä/', '/ö/'],
            static fn(array $matches): string => '[' . $matches[0] . ']',
            'färöer',
            count: $count,
        );

        self::assertSame('f[ä]r[ö]er', $result);
        self::assertSame(2, $count);
    }

    public function testPregReplaceHandlesPatternArrays(): void
    {
        $count = 0;
        $result = $this->mbString->preg_replace(
            ['/ä/', '/ö/'],
            ['ae', 'oe'],
            'färöer',
            count: $count,
        );

        self::assertSame('faeroeer', $result);
        self::assertSame(2, $count);
    }

    private function resetMbstringSingleton(): void
    {
        $reflection = new \ReflectionClass(Mbstring::class);
        $property = $reflection->getProperty('mbstring');
        $property->setValue(null, null);
    }

    private function readLanguage(Mbstring $instance): string
    {
        $reflection = new \ReflectionClass(AbstractString::class);
        $property = $reflection->getProperty('language');

        return (string) $property->getValue($instance);
    }

    private function readEncoding(Mbstring $instance): string
    {
        $reflection = new \ReflectionClass(AbstractString::class);
        $property = $reflection->getProperty('encoding');

        return (string) $property->getValue($instance);
    }
}
