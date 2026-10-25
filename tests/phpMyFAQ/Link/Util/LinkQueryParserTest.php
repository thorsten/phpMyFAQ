<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Util;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class LinkQueryParserTest extends TestCase
{
    public function testParseSimpleQuery(): void
    {
        $params = LinkQueryParser::parse('https://example.org/index.php?foo=bar');
        $this->assertSame(['foo' => 'bar'], $params);
    }

    public function testParseHtmlEncodedAmpersands(): void
    {
        $params = LinkQueryParser::parse('https://example.org/index.php?foo=bar&amp;baz=qux');
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $params);
    }

    public function testParseFragment(): void
    {
        $params = LinkQueryParser::parse('https://example.org/index.php?foo=bar#frag');
        $this->assertSame(['foo' => 'bar', '#' => 'frag', 'fragment' => 'frag'], $params);
    }

    public function testEmptyUrl(): void
    {
        $params = LinkQueryParser::parse('');
        $this->assertSame([], $params);
    }

    public function testUnparsableUrlYieldsNoParameters(): void
    {
        $params = LinkQueryParser::parse('http:///example.org/index.php?foo=bar');
        $this->assertSame([], $params);
    }

    public function testArrayParametersAreSkipped(): void
    {
        $params = LinkQueryParser::parse('https://example.org/index.php?foo[]=bar&baz=qux');
        $this->assertSame(['baz' => 'qux'], $params);
    }
}
