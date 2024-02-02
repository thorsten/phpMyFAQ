<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    public function testFilterVar(): void
    {
        $this->assertEquals('test', Filter::filterVar('test', FILTER_DEFAULT));
        $this->assertEquals(null, Filter::filterVar('test', FILTER_VALIDATE_INT));
        $this->assertEquals('test@phpmyfaq.de', Filter::filterVar('test@phpmyfaq.de', FILTER_VALIDATE_EMAIL));
        $this->assertEquals(null, Filter::filterVar('test#phpmyfaq.de', FILTER_VALIDATE_EMAIL));

        // Test with callback
        $this->assertEquals('test', Filter::filterVar('test', FILTER_SANITIZE_SPECIAL_CHARS));
        $this->assertEquals('&lt;b&gt;foo&lt;/b&gt;', Filter::filterVar('<b>foo</b>', FILTER_SANITIZE_SPECIAL_CHARS));
        $this->assertEquals(
            '&lt;script onload=&quot;alert(1)&quot; /&gt;foo',
            Filter::filterVar('<script onload="alert(1)" />foo', FILTER_SANITIZE_SPECIAL_CHARS)
        );
    }


    public function testFilterSanitizeString(): void
    {
        $this->assertEquals('test', (new Filter())->filterSanitizeString('test'));
        $this->assertEquals('&lt;b&gt;foo&lt;/b&gt;', (new Filter())->filterSanitizeString('<b>foo</b>'));
        $this->assertEquals(
            '&lt;script onload=&quot;alert(1)&quot; /&gt;foo',
            (new Filter())->filterSanitizeString('<script onload="alert(1)" />foo')
        );
    }

    public function testRemoveAttributes(): void
    {
        $this->assertEquals('<video />', Filter::removeAttributes('<video preload="auto" />'));
        $this->assertEquals('<video controls />', Filter::removeAttributes('<video controls />'));

        $expected = '<a href="#">phpMyFAQ</a>';
        $actual = Filter::removeAttributes($expected);
        $this->assertEquals($expected, $actual);

        $expected = '<a href="#">phpMyFAQ</a>';
        $toTest = '<a href="#" onchange="bar()">phpMyFAQ</a>';
        $actual = Filter::removeAttributes($toTest);
        $this->assertEquals($expected, $actual);

        $expected = '<a href="#">phpMyFAQ</a>';
        $toTest = '<a href="#" disabled="disabled">phpMyFAQ</a>';
        $actual = Filter::removeAttributes($toTest);
        $this->assertEquals($expected, $actual);

        $expected = 'To: sslEnabledProtocols="TLSv1.2"';
        $actual = Filter::removeAttributes($expected);
        $this->assertEquals($expected, $actual);
    }
}
