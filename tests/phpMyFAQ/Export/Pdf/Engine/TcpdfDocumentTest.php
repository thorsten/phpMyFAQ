<?php

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf\Engine;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TcpdfDocument::class)]
#[UsesClass(TcpdfEngine::class)]
final class TcpdfDocumentTest extends TestCase
{
    private function createDocument(): TcpdfDocument
    {
        // The engine defines the TCPDF configuration constants the document class depends on.
        new TcpdfEngine();

        return new TcpdfDocument();
    }

    public function testHeaderAndFooterAreSilentWithoutRenderers(): void
    {
        $document = $this->createDocument();

        $document->Header();
        $document->Footer();

        $this->expectNotToPerformAssertions();
    }

    public function testHeaderDelegatesToTheRegisteredRenderer(): void
    {
        $document = $this->createDocument();
        $calls = 0;
        $document->setHeaderRenderer(static function () use (&$calls): void {
            ++$calls;
        });

        $document->Header();
        $document->Header();

        $this->assertSame(2, $calls);
    }

    public function testFooterDelegatesToTheRegisteredRenderer(): void
    {
        $document = $this->createDocument();
        $calls = 0;
        $document->setFooterRenderer(static function () use (&$calls): void {
            ++$calls;
        });

        $document->Footer();

        $this->assertSame(1, $calls);
    }

    public function testRenderersCanBeRemovedAgain(): void
    {
        $document = $this->createDocument();
        $calls = 0;
        $renderer = static function () use (&$calls): void {
            ++$calls;
        };
        $document->setHeaderRenderer($renderer);
        $document->setFooterRenderer($renderer);

        $document->setHeaderRenderer(null);
        $document->setFooterRenderer(null);
        $document->Header();
        $document->Footer();

        $this->assertSame(0, $calls);
    }

    public function testRenderersRunWhileAPageIsGenerated(): void
    {
        $document = $this->createDocument();
        $events = [];
        $document->setHeaderRenderer(static function () use (&$events): void {
            $events[] = 'header';
        });
        $document->setFooterRenderer(static function () use (&$events): void {
            $events[] = 'footer';
        });

        $document->AddPage();
        $pdf = $document->Output('test.pdf', 'S');

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertContains('header', $events);
        $this->assertContains('footer', $events);
    }
}
