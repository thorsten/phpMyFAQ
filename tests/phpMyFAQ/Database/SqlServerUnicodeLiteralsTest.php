<?php

namespace phpMyFAQ\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class SqlServerUnicodeLiteralsTest
 *
 * @package phpMyFAQ
 */
class SqlServerUnicodeLiteralsTest extends TestCase
{
    public function testLeavesPureAsciiQueryUnchanged(): void
    {
        $query = "SELECT id FROM faqdata WHERE lang = 'en' AND thema = 'Hello World'";

        $this->assertSame($query, SqlServerUnicodeLiterals::apply($query));
    }

    public function testPrefixesLiteralContainingNonAsciiCharacters(): void
    {
        $this->assertSame(
            "INSERT INTO faqdata (thema) VALUES (N'こんにちは')",
            SqlServerUnicodeLiterals::apply("INSERT INTO faqdata (thema) VALUES ('こんにちは')"),
        );
    }

    public function testLeavesAlreadyPrefixedLiteralUntouched(): void
    {
        $query = "SELECT id FROM faqdata WHERE thema = N'ありがとう'";

        $this->assertSame($query, SqlServerUnicodeLiterals::apply($query));
    }

    public function testLeavesLowercasePrefixedLiteralUntouched(): void
    {
        $query = "SELECT id FROM faqdata WHERE thema = n'ありがとう'";

        $this->assertSame($query, SqlServerUnicodeLiterals::apply($query));
    }

    public function testPrefixesOnlyNonAsciiLiterals(): void
    {
        $this->assertSame(
            "UPDATE faqdata SET thema = N'ขอบคุณ', author = 'John Doe' WHERE lang = 'th'",
            SqlServerUnicodeLiterals::apply(
                "UPDATE faqdata SET thema = 'ขอบคุณ', author = 'John Doe' WHERE lang = 'th'",
            ),
        );
    }

    public function testHandlesEscapedQuotesInsideLiterals(): void
    {
        $this->assertSame(
            "INSERT INTO faqdata (thema, content) VALUES (N'日本''s FAQ', 'it''s plain ASCII')",
            SqlServerUnicodeLiterals::apply(
                "INSERT INTO faqdata (thema, content) VALUES ('日本''s FAQ', 'it''s plain ASCII')",
            ),
        );
    }

    public function testInsertsSeparatingSpaceAfterAdjacentWordCharacter(): void
    {
        $this->assertSame(
            "SELECT CASE WHEN active = 'yes' THEN N'公開' ELSE 'draft' END FROM faqdata",
            SqlServerUnicodeLiterals::apply(
                "SELECT CASE WHEN active = 'yes' THEN'公開' ELSE 'draft' END FROM faqdata",
            ),
        );
    }

    #[DataProvider('nonLiteralQuoteProvider')]
    public function testIgnoresQuotesOutsideStringLiterals(string $query): void
    {
        $this->assertSame($query, SqlServerUnicodeLiterals::apply($query));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonLiteralQuoteProvider(): array
    {
        return [
            'bracketed identifier' => ["SELECT [it's ünicode] FROM faqdata"],
            'double-quoted identifier' => ['SELECT "it\'s ünicode" FROM faqdata'],
            'line comment' => ["SELECT id FROM faqdata -- don't prefix 'ünicode' here"],
            'block comment' => ["SELECT id /* don't prefix 'ünicode' here */ FROM faqdata"],
        ];
    }

    public function testLeavesNonAsciiOutsideLiteralsUntouched(): void
    {
        $query = 'SELECT id, thema_日本語 FROM faqdata';

        $this->assertSame($query, SqlServerUnicodeLiterals::apply($query));
    }

    public function testPrefixesLiteralAfterBlockComment(): void
    {
        $this->assertSame(
            "SELECT id /* filter */ FROM faqdata WHERE thema = N'привет'",
            SqlServerUnicodeLiterals::apply("SELECT id /* filter */ FROM faqdata WHERE thema = 'привет'"),
        );
    }
}
