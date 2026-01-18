<?php

namespace phpMyFAQ\Translation;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HtmlPreserverTest extends TestCase
{
    private HtmlPreserver $htmlPreserver;

    protected function setUp(): void
    {
        $this->htmlPreserver = new HtmlPreserver();
    }

    public function testReplaceTagsWithSimpleHtml(): void
    {
        $html = '<p>Hello World</p>';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($html);

        $this->assertStringContainsString('##HTML_TAG_', $textWithPlaceholders);
        $this->assertStringContainsString('Hello World', $textWithPlaceholders);
        $this->assertCount(2, $tagMap); // <p> and </p>
        $this->assertContains('<p>', $tagMap);
        $this->assertContains('</p>', $tagMap);
    }

    public function testReplaceTagsWithNestedHtml(): void
    {
        $html = '<div><p>Hello <strong>World</strong></p></div>';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($html);

        $this->assertStringContainsString('Hello', $textWithPlaceholders);
        $this->assertStringContainsString('World', $textWithPlaceholders);
        $this->assertCount(6, $tagMap); // <div>, <p>, <strong>, </strong>, </p>, </div>
    }

    public function testReplaceTagsWithSelfClosingTags(): void
    {
        $html = 'Line one<br/>Line two';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($html);

        $this->assertStringContainsString('Line one', $textWithPlaceholders);
        $this->assertStringContainsString('Line two', $textWithPlaceholders);
        $this->assertCount(1, $tagMap); // <br/>
        $this->assertContains('<br/>', $tagMap);
    }

    public function testReplaceTagsWithAttributes(): void
    {
        $html = '<a href="https://example.com" target="_blank">Link</a>';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($html);

        $this->assertStringContainsString('Link', $textWithPlaceholders);
        $this->assertCount(2, $tagMap);
        $this->assertContains('<a href="https://example.com" target="_blank">', $tagMap);
        $this->assertContains('</a>', $tagMap);
    }

    public function testRestoreTags(): void
    {
        $html = '<p>Hello <strong>World</strong></p>';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($html);

        $restored = $this->htmlPreserver->restoreTags($textWithPlaceholders, $tagMap);

        $this->assertEquals($html, $restored);
    }

    public function testRestoreTagsAfterTranslation(): void
    {
        $html = '<p>Hello <strong>World</strong></p>';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($html);

        // Simulate translation by modifying the text between placeholders
        $translatedWithPlaceholders = str_replace('Hello', 'Hallo', $textWithPlaceholders);
        $translatedWithPlaceholders = str_replace('World', 'Welt', $translatedWithPlaceholders);

        $restored = $this->htmlPreserver->restoreTags($translatedWithPlaceholders, $tagMap);

        $this->assertEquals('<p>Hallo <strong>Welt</strong></p>', $restored);
    }

    public function testReplaceTagsWithEmptyString(): void
    {
        $html = '';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($html);

        $this->assertEquals('', $textWithPlaceholders);
        $this->assertEmpty($tagMap);
    }

    public function testReplaceTagsWithPlainText(): void
    {
        $text = 'Just plain text without HTML';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($text);

        $this->assertEquals($text, $textWithPlaceholders);
        $this->assertEmpty($tagMap);
    }

    public function testReplaceTagsWithComplexHtml(): void
    {
        $html = '<div class="container"><h1>Title</h1><p>This is a <a href="#" class="link">link</a> in a paragraph.</p></div>';
        [$textWithPlaceholders, $tagMap] = $this->htmlPreserver->replaceTags($html);

        // Verify all text content is preserved
        $this->assertStringContainsString('Title', $textWithPlaceholders);
        $this->assertStringContainsString('This is a', $textWithPlaceholders);
        $this->assertStringContainsString('link', $textWithPlaceholders);
        $this->assertStringContainsString('in a paragraph.', $textWithPlaceholders);

        // Verify all tags are replaced
        $this->assertCount(8, $tagMap);

        // Verify restoration works
        $restored = $this->htmlPreserver->restoreTags($textWithPlaceholders, $tagMap);
        $this->assertEquals($html, $restored);
    }
}
