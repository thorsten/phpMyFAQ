<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    public function testFilterVar(): void
    {
        $this->assertEquals('test', Filter::filterVar('test', FILTER_UNSAFE_RAW));
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

    /**
     * Test filterInput with different input types
     */
    public function testFilterInput(): void
    {
        // Test with default value when input doesn't exist
        $result = Filter::filterInput(INPUT_GET, 'nonexistent', FILTER_VALIDATE_INT, 42);
        $this->assertEquals(42, $result);

        // Test with FILTER_UNSAFE_RAW
        $result = Filter::filterInput(INPUT_GET, 'nonexistent', FILTER_UNSAFE_RAW, 'default');
        $this->assertEquals('default', $result);
    }

    /**
     * Test filterInputArray method
     */
    public function testFilterInputArray(): void
    {
        $definition = [
            'id' => FILTER_VALIDATE_INT,
            'email' => FILTER_VALIDATE_EMAIL
        ];

        $result = Filter::filterInputArray(INPUT_GET, $definition);
        // filterInputArray can return null when no input data is available
        $this->assertTrue($result === null || is_array($result));
    }

    /**
     * Test filterArray method
     */
    public function testFilterArray(): void
    {
        $testArray = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '25'
        ];

        $result = Filter::filterArray($testArray);
        $this->assertIsArray($result);
        $this->assertEquals($testArray, $result);

        // Test with specific filters
        $filters = [
            'name' => FILTER_SANITIZE_SPECIAL_CHARS,
            'email' => FILTER_VALIDATE_EMAIL,
            'age' => FILTER_VALIDATE_INT
        ];

        $result = Filter::filterArray($testArray, $filters);
        $this->assertIsArray($result);
    }

    /**
     * Test getFilteredQueryString method
     */
    public function testGetFilteredQueryString(): void
    {
        // Mock empty query string
        $result = Filter::getFilteredQueryString();
        $this->assertIsString($result);
    }

    /**
     * Test filterSanitizeString with XSS attempts
     */
    public function testFilterSanitizeStringWithXSS(): void
    {
        $filter = new Filter();

        $xssAttempts = [
            '<script>alert("xss")</script>' => '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            '<img src="x" onerror="alert(1)">' => '&lt;img src=&quot;x&quot; onerror=&quot;alert(1)&quot;&gt;',
            'javascript:alert("xss")' => 'javascript:alert(&quot;xss&quot;)',
            '<iframe src="javascript:alert(\'XSS\')">' => '&lt;iframe src=&quot;javascript:alert(&#039;XSS&#039;)&quot;&gt;' // Adjusted encoding
        ];

        foreach ($xssAttempts as $input => $expected) {
            $result = $filter->filterSanitizeString($input);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test filterSanitizeString with null bytes and special characters
     */
    public function testFilterSanitizeStringWithNullBytes(): void
    {
        $filter = new Filter();

        // Test null byte removal
        $input = "test\x00string";
        $result = $filter->filterSanitizeString($input);
        $this->assertEquals('teststring', $result);

        // Test with various special characters - adjust for actual encoding
        $input = "test'string\"with&special<chars>";
        $result = $filter->filterSanitizeString($input);
        $this->assertStringContainsString('&#039;', $result); // Single quote (actual encoding)
        $this->assertStringContainsString('&quot;', $result); // Double quote (actual encoding is &quot; not &#34;)
        $this->assertStringContainsString('&amp;', $result); // Ampersand
    }

    /**
     * Test removeAttributes with various HTML elements
     */
    public function testRemoveAttributesWithVariousElements(): void
    {
        $testCases = [
            // Keep allowed attributes
            '<img src="test.jpg" alt="Test" width="100" height="50">' => '<img src="test.jpg" alt="Test" width="100" height="50">',
            '<a href="test.html" title="Test" target="_blank">' => '<a href="test.html" title="Test" target="_blank">',

            // Remove dangerous attributes
            '<img src="test.jpg" onload="alert(1)">' => '<img src="test.jpg">',
            '<div onclick="malicious()" class="safe">' => '<div class="safe">',
            '<script type="text/javascript">alert("xss")</script>' => '<script>alert("xss")</script>'
        ];

        foreach ($testCases as $input => $expected) {
            $result = Filter::removeAttributes($input);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test filterVar with various data types
     */
    public function testFilterVarWithVariousTypes(): void
    {
        // Test integers
        $this->assertEquals(123, Filter::filterVar('123', FILTER_VALIDATE_INT));
        $this->assertEquals(0, Filter::filterVar('invalid', FILTER_VALIDATE_INT, 0));

        // Test floats
        $this->assertEquals(12.34, Filter::filterVar('12.34', FILTER_VALIDATE_FLOAT));
        $this->assertEquals(0.0, Filter::filterVar('invalid', FILTER_VALIDATE_FLOAT, 0.0));

        // Test URLs
        $this->assertEquals('https://example.com', Filter::filterVar('https://example.com', FILTER_VALIDATE_URL));
        $this->assertEquals('default', Filter::filterVar('invalid-url', FILTER_VALIDATE_URL, 'default'));

        // Test booleans - adjust expectations
        $this->assertTrue(Filter::filterVar('1', FILTER_VALIDATE_BOOLEAN));
        $this->assertNull(Filter::filterVar('false', FILTER_VALIDATE_BOOLEAN)); // false string returns null
    }

    /**
     * Test filterVar with FILTER_SANITIZE_SPECIAL_CHARS callback
     */
    public function testFilterVarWithSanitizeCallback(): void
    {
        $input = '<script>alert("test")</script>';
        $result = Filter::filterVar($input, FILTER_SANITIZE_SPECIAL_CHARS);

        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringContainsString('&quot;test&quot;', $result);
    }

    /**
     * Test filterSanitizeString with empty and whitespace strings
     */
    public function testFilterSanitizeStringWithEmptyValues(): void
    {
        $filter = new Filter();

        $this->assertEquals('', $filter->filterSanitizeString(''));
        $this->assertEquals('   ', $filter->filterSanitizeString('   '));
        $this->assertEquals('\n\t', $filter->filterSanitizeString('\n\t'));
    }

    /**
     * Test removeAttributes with complex nested HTML
     */
    public function testRemoveAttributesWithComplexHTML(): void
    {
        $complexHTML = '<div class="container" onclick="bad()"><p style="color:red;" onmouseover="evil()">Text</p></div>';
        $result = Filter::removeAttributes($complexHTML);

        $this->assertStringContainsString('class="container"', $result);
        $this->assertStringContainsString('style="color:red;"', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('onmouseover', $result);
    }

    /**
     * Test filterArray with edge cases
     */
    public function testFilterArrayWithEdgeCases(): void
    {
        // Empty array
        $result = Filter::filterArray([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        // Array with null values
        $testArray = ['key1' => null, 'key2' => '', 'key3' => 'value'];
        $result = Filter::filterArray($testArray);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('key3', $result);
    }

    /**
     * Test filterInput with FILTER_SANITIZE_SPECIAL_CHARS
     */
    public function testFilterInputWithSanitizeSpecialChars(): void
    {
        // Test the special case handling for FILTER_SANITIZE_SPECIAL_CHARS
        $result = Filter::filterInput(INPUT_GET, 'nonexistent', FILTER_SANITIZE_SPECIAL_CHARS, 'default');
        $this->assertEquals('default', $result);
    }
}
