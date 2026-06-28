<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DownloadHostType::class)]
class DownloadHostTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('https://github.com/', DownloadHostType::GITHUB->value);
        $this->assertSame('https://download.phpmyfaq.de/', DownloadHostType::PHPMYFAQ->value);
    }

    public function testCasesAreComplete(): void
    {
        $cases = DownloadHostType::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(DownloadHostType::GITHUB, $cases);
        $this->assertContains(DownloadHostType::PHPMYFAQ, $cases);
    }

    public function testTryFromValidValues(): void
    {
        foreach (DownloadHostType::cases() as $case) {
            $this->assertSame($case, DownloadHostType::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(DownloadHostType::tryFrom('definitely-invalid'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);

        DownloadHostType::from('definitely-invalid');
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(DownloadHostType $case): string => $case->value, DownloadHostType::cases());

        $this->assertCount(count($values), array_unique($values));
    }
}
