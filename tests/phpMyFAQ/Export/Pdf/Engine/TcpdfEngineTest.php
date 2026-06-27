<?php

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf\Engine;

use PHPUnit\Framework\TestCase;

final class TcpdfEngineTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        self::assertInstanceOf(PdfEngineInterface::class, new TcpdfEngine());
    }

    public function testOutputsNonEmptyPdfStringForASimplePage(): void
    {
        $engine = new TcpdfEngine();
        $engine->open();
        $engine->setPrintHeader(false);
        $engine->addPage();
        $engine->writeHtml('<p>hello</p>');
        $pdf = $engine->output('test.pdf', 'S');

        self::assertStringStartsWith('%PDF', $pdf);
    }

    public function testImageResolverSkipWhenResolverReturnsNull(): void
    {
        $engine = new TcpdfEngine();
        $engine->onImageResolve(static fn(string $file, string $type): ?array => null);
        $engine->open();
        $engine->setPrintHeader(false);
        $engine->addPage();
        $engine->writeHtml('<img src="content/user/images/does-not-matter.png">');
        $pdf = $engine->output('test.pdf', 'S');

        self::assertStringStartsWith('%PDF', $pdf);
    }
}
