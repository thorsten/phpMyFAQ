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

    public function testWriteHtmlWithInlineBase64ImageRenders(): void
    {
        $gif = base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
        self::assertNotFalse($gif);

        $engine = new TcpdfEngine();
        $engine->open();
        $engine->setPrintHeader(false);
        $engine->addPage();
        $engine->writeHtml('<img src="@' . base64_encode($gif) . '">');

        self::assertStringStartsWith('%PDF', $engine->output('test.pdf', 'S'));
    }
}
