<?php

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

use PHPUnit\Framework\TestCase;

final class FilenameTest extends TestCase
{
    public function testReturnsOriginalWhenCustomIsNull(): void
    {
        self::assertSame('report.pdf', Filename::compose('report.pdf', null));
    }

    public function testReturnsOriginalWhenCustomIsBlank(): void
    {
        self::assertSame('report.pdf', Filename::compose('report.pdf', '   '));
    }

    public function testAppendsOriginalExtensionWhenCustomHasNoExtension(): void
    {
        self::assertSame('invoice.pdf', Filename::compose('report.pdf', 'invoice'));
    }

    public function testKeepsOriginalExtensionWhenCustomSuppliesADifferentOne(): void
    {
        self::assertSame('invoice.pdf', Filename::compose('report.pdf', 'invoice.txt'));
    }

    public function testStripsPathComponentsFromCustomName(): void
    {
        self::assertSame('invoice.pdf', Filename::compose('report.pdf', '../../etc/invoice'));
    }

    public function testUsesCustomNameAsIsWhenOriginalHasNoExtension(): void
    {
        self::assertSame('invoice', Filename::compose('report', 'invoice'));
    }

    public function testFallsBackToOriginalWhenCustomSanitizesToEmpty(): void
    {
        self::assertSame('report.pdf', Filename::compose('report.pdf', '.pdf'));
    }
}
