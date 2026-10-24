<?php

declare(strict_types=1);

namespace phpMyFAQ\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

#[CoversClass(IframeSourceSanitizer::class)]
final class IframeSourceSanitizerTest extends TestCase
{
    public function testSupportsOnlyTheSourceOfIframes(): void
    {
        $sanitizer = new IframeSourceSanitizer(['www.youtube.com']);

        self::assertSame(['iframe'], $sanitizer->getSupportedElements());
        self::assertSame(['src'], $sanitizer->getSupportedAttributes());
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function sourceProvider(): array
    {
        return [
            'allowed host' => ['https://www.youtube.com/embed/abc', 'https://www.youtube.com/embed/abc'],
            'other host' => ['https://evil.example.com/embed/abc', null],
            'lookalike host' => ['https://www.youtube.com.evil.example.com/embed/abc', null],
            'http instead of https' => ['http://www.youtube.com/embed/abc', null],
            'javascript scheme' => ['javascript:alert(1)', null],
            'data scheme' => ['data:text/html,<script>alert(1)</script>', null],
            'relative url' => ['/embed/abc', null],
            'protocol relative url' => ['//www.youtube.com/embed/abc', null],
        ];
    }

    #[DataProvider('sourceProvider')]
    public function testKeepsOnlyHttpsSourcesOfAllowedHosts(string $source, ?string $expected): void
    {
        $sanitizer = new IframeSourceSanitizer(['www.youtube.com', 'player.vimeo.com']);

        self::assertSame($expected, $sanitizer->sanitizeAttribute('iframe', 'src', $source, new HtmlSanitizerConfig()));
    }

    public function testIgnoresThePortOfAnAllowlistEntry(): void
    {
        $sanitizer = new IframeSourceSanitizer([' player.vimeo.com:443 ']);

        self::assertSame('https://player.vimeo.com/video/1', $sanitizer->sanitizeAttribute(
            'iframe',
            'src',
            'https://player.vimeo.com/video/1',
            new HtmlSanitizerConfig(),
        ));
    }

    public function testRejectsEverySourceWithoutAllowedHosts(): void
    {
        $sanitizer = new IframeSourceSanitizer(['', '  ']);

        self::assertNull($sanitizer->sanitizeAttribute(
            'iframe',
            'src',
            'https://www.youtube.com/embed/abc',
            new HtmlSanitizerConfig(),
        ));
    }
}
