<?php

declare(strict_types=1);

namespace phpMyFAQ\Mail;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MailContent::class)]
final class MailContentTest extends TestCase
{
    public function testPlainTextHasNoHtmlPart(): void
    {
        $content = MailContent::plainText("Line <one>\nLine two");

        $this->assertFalse($content->isHtml());
        $this->assertSame("Line <one>\nLine two", $content->text);
        $this->assertSame('', $content->html);
    }

    public function testHtmlKeepsTheTextAlternativeOfTheCaller(): void
    {
        $content = MailContent::html('<p>Hello</p>', 'Hand-written');

        $this->assertTrue($content->isHtml());
        $this->assertSame('<p>Hello</p>', $content->html);
        $this->assertSame('Hand-written', $content->text);
    }

    public function testHtmlWithoutMarkupToSendIsPlainText(): void
    {
        $content = MailContent::html('', 'Only text');

        $this->assertFalse($content->isHtml());
        $this->assertSame('Only text', $content->text);
    }

    public function testHtmlRendersAMarkupFreeTextAlternative(): void
    {
        $html =
            '<html><head><title>Ignored</title><style>p { color: red; }</style></head><body>'
            . '<h1>New   comment</h1>'
            . '<p>Author: J&uuml;rgen &amp; Co, <a href="mailto:j@example.com">j@example.com</a><br>'
            . 'FAQ: <a target="_blank" href="https://example.com/faq/1">How &quot;to&quot;?</a></p>'
            . '<ul><li>One</li><li>Two</li></ul><hr>'
            . '<a href=\'https://example.com/\'>https://example.com/</a>'
            . '<script>alert(1)</script></body></html>';

        $content = MailContent::html($html);

        $this->assertTrue($content->isHtml());
        $this->assertSame($html, $content->html);
        $this->assertSame(
            "New comment\n\n"
            . "Author: Jürgen & Co, j@example.com\n"
            . "FAQ: How \"to\"? (https://example.com/faq/1)\n\n"
            . "- One\n- Two\n\n"
            . 'https://example.com/',
            $content->text,
        );
    }

    public function testHtmlKeepsLineBreaksWrittenIntoTheMarkup(): void
    {
        $content = MailContent::html("Comment:<br />\nfirst line<br />\nsecond line");

        $this->assertSame("Comment:\nfirst line\nsecond line", $content->text);
    }

    public function testFromPreparedBodyFollowsTheContentTypeHeader(): void
    {
        $html = MailContent::fromPreparedBody('Text/HTML; charset="utf-8"', '<p>Hello</p>');
        $this->assertTrue($html->isHtml());
        $this->assertSame('Hello', $html->text);

        $plain = MailContent::fromPreparedBody('text/plain; format=flowed; charset="utf-8"', 'Hello <there>');
        $this->assertFalse($plain->isHtml());
        $this->assertSame('Hello <there>', $plain->text);

        $this->assertFalse(MailContent::fromPreparedBody('', 'Body')->isHtml());
    }
}
