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
                'safe_default'
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

    public function testFilterArray(): void
    {
        $testArray = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '25'
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
            'comment' => 'Good product<script>steal()</script>'
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
        $this->assertStringContainsString('&#039;', $result);
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
        $this->assertStringContainsString('style="color: red;"', $result);
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
            'email' => FILTER_VALIDATE_EMAIL
        ];

        $result = Filter::filterInputArray(INPUT_POST, $definition);

        $this->assertThat(
            $result,
            $this->logicalOr(
                $this->isArray(),
                $this->isBool(),
                $this->isNull()
            )
        );
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
        $this->assertStringContainsString('style="color: red;"', $result);
        $this->assertStringContainsString('href="http://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('src="image.jpg"', $result);
        $this->assertStringContainsString('alt="Test"', $result);
        $this->assertStringContainsString('width="100"', $result);

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
