<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReleaseType::class)]
class ReleaseTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('stable', ReleaseType::STABLE->value);
        $this->assertSame('development', ReleaseType::DEVELOPMENT->value);
        $this->assertSame('nightly', ReleaseType::NIGHTLY->value);
    }

    public function testCasesAreComplete(): void
    {
        $cases = ReleaseType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(ReleaseType::STABLE, $cases);
        $this->assertContains(ReleaseType::DEVELOPMENT, $cases);
        $this->assertContains(ReleaseType::NIGHTLY, $cases);
    }

    public function testTryFromValidValues(): void
    {
        foreach (ReleaseType::cases() as $case) {
            $this->assertSame($case, ReleaseType::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(ReleaseType::tryFrom('definitely-invalid'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);

        ReleaseType::from('definitely-invalid');
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(ReleaseType $case): string => $case->value, ReleaseType::cases());

        $this->assertCount(count($values), array_unique($values));
    }
}
