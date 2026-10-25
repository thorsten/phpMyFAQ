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

    public function testMetadataAndLayoutSettersAreAppliedToTheDocument(): void
    {
        $engine = new TcpdfEngine();
        $engine->open();
        $engine->setPrintHeader(false);
        $engine->setTitle('Engine title');
        $engine->setAuthor('Engine author');
        $engine->setCreator('Engine creator');
        $engine->setHeaderMargin(7.0);
        $engine->setFooterMargin(9.0);
        $engine->setFontSubsetting(false);
        $engine->setImageScale(1.25);
        $engine->setDefaultMonospacedFont('courier');
        $engine->addPage();
        $engine->setFont('helvetica', '', 10.0);
        $engine->write(5.0, 'plain text line');

        $pdf = $engine->output('test.pdf', 'S');

        self::assertStringStartsWith('%PDF', $pdf);
        // TCPDF stores the metadata strings as UTF-16BE with a byte order mark
        foreach (['Engine title', 'Engine author', 'Engine creator'] as $metadata) {
            self::assertStringContainsString("\xFE\xFF" . mb_convert_encoding($metadata, 'UTF-16BE', 'UTF-8'), $pdf);
        }
    }
}
