<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SeoType::class)]
class SeoTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('category', SeoType::CATEGORY->value);
        $this->assertSame('faq', SeoType::FAQ->value);
        $this->assertSame('page', SeoType::PAGE->value);
    }

    public function testCasesAreComplete(): void
    {
        $cases = SeoType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(SeoType::CATEGORY, $cases);
        $this->assertContains(SeoType::FAQ, $cases);
        $this->assertContains(SeoType::PAGE, $cases);
    }

    public function testTryFromValidValues(): void
    {
        foreach (SeoType::cases() as $case) {
            $this->assertSame($case, SeoType::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(SeoType::tryFrom('definitely-invalid'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);

        SeoType::from('definitely-invalid');
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(SeoType $case): string => $case->value, SeoType::cases());

        $this->assertCount(count($values), array_unique($values));
    }
}
