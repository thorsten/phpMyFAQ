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

    public function testTextColorRawRoundTrips(): void
    {
        // Guards against accessing TCPDF's protected $TextColor from outside its
        // class hierarchy (a fatal error); it must go through the document.
        $engine = new TcpdfEngine();
        $engine->setTextColorRaw('0.250 0.500 0.750 rg');

        self::assertSame('0.250 0.500 0.750 rg', $engine->getTextColor());
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

    public function testImageResolverDrawsResolvedFile(): void
    {
        $jpegData = base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwDX4A=',
        );
        self::assertNotFalse($jpegData);

        // The resolver embeds the image as raw '@'-prefixed data, which the engine
        // must hand to TCPDF and render without error.
        $engine = new TcpdfEngine();
        $engine->onImageResolve(static fn(string $file, string $type): array => ['@' . $jpegData, 'jpg']);
        $engine->open();
        $engine->setPrintHeader(false);
        $engine->addPage();
        $engine->writeHtml('<img src="content/user/images/anything.jpg">');
        $pdf = $engine->output('test.pdf', 'S');

        self::assertStringStartsWith('%PDF', $pdf);
    }
}
