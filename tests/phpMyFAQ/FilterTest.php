<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

/**
 *
 * @testdox Filter should
 */
class FilterTest extends TestCase
{
    /**
     * @testdox return filtered strings
     */
    public function testFilterVar(): void
    {
        $this->assertEquals('test', Filter::filterVar('test', FILTER_DEFAULT));
        $this->assertEquals(null, Filter::filterVar('test', FILTER_VALIDATE_INT));
        $this->assertEquals('test@phpmyfaq.de', Filter::filterVar('test@phpmyfaq.de', FILTER_VALIDATE_EMAIL));
        $this->assertEquals(null, Filter::filterVar('test#phpmyfaq.de', FILTER_VALIDATE_EMAIL));

        // Test with callback
        $this->assertEquals('test', Filter::filterVar('test', FILTER_UNSAFE_RAW));
        $this->assertEquals('&lt;b&gt;foo&lt;/b&gt;', Filter::filterVar('<b>foo</b>', FILTER_UNSAFE_RAW));
        $this->assertEquals(
            '&lt;script onload=&quot;alert(1)&quot; /&gt;foo',
            Filter::filterVar('<script onload="alert(1)" />foo', FILTER_UNSAFE_RAW)
        );
    }

    /**
     * @testdox return sanitized strings
     */
    public function testFilterSanitizeString(): void
    {
        $this->assertEquals('test', (new Filter())->filterSanitizeString('test'));
        $this->assertEquals('&lt;b&gt;foo&lt;/b&gt;', (new Filter())->filterSanitizeString('<b>foo</b>'));
        $this->assertEquals(
            '&lt;script onload=&quot;alert(1)&quot; /&gt;foo',
            (new Filter())->filterSanitizeString('<script onload="alert(1)" />foo')
        );
    }

    /**
     * @testdox return strings without HTML tag attributes
     */
    public function testRemoveAttributes(): void
    {
        $this->assertEquals('<video />', Filter::removeAttributes('<video preload="auto" />'));
        $this->assertEquals('<video controls />', Filter::removeAttributes('<video controls />'));
    }
}
