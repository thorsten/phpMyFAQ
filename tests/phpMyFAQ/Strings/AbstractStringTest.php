<?php

namespace phpMyFAQ\Strings;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractString::class)]
final class AbstractStringTest extends TestCase
{
    public function testIsUtf8ReturnsTrueForAsciiAndUtf8Strings(): void
    {
        self::assertTrue(AbstractString::isUTF8('plain ascii'));
        self::assertTrue(AbstractString::isUTF8('Grüße aus Köln'));
        self::assertTrue(AbstractString::isUTF8('こんにちは世界'));
    }

    public function testIsUtf8ReturnsFalseForInvalidByteSequences(): void
    {
        self::assertFalse(AbstractString::isUTF8("\xC3\x28"));
        self::assertFalse(AbstractString::isUTF8("\xA0\xA1"));
    }
}
