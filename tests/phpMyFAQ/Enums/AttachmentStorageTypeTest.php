<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttachmentStorageType::class)]
class AttachmentStorageTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame(0, AttachmentStorageType::FILESYSTEM->value);
        $this->assertSame(1, AttachmentStorageType::DATABASE->value);
        $this->assertSame(2, AttachmentStorageType::S3->value);
    }

    public function testCasesAreComplete(): void
    {
        $cases = AttachmentStorageType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(AttachmentStorageType::FILESYSTEM, $cases);
        $this->assertContains(AttachmentStorageType::DATABASE, $cases);
        $this->assertContains(AttachmentStorageType::S3, $cases);
    }

    public function testTryFromValidValues(): void
    {
        foreach (AttachmentStorageType::cases() as $case) {
            $this->assertSame($case, AttachmentStorageType::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(AttachmentStorageType::tryFrom(9999));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);

        AttachmentStorageType::from(9999);
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(
            static fn(AttachmentStorageType $case): int => $case->value,
            AttachmentStorageType::cases(),
        );

        $this->assertCount(count($values), array_unique($values));
    }
}
