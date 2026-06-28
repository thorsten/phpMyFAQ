<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BackupType::class)]
class BackupTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('data', BackupType::BACKUP_TYPE_DATA->value);
        $this->assertSame('logs', BackupType::BACKUP_TYPE_LOGS->value);
        $this->assertSame('content', BackupType::BACKUP_TYPE_CONTENT->value);
    }

    public function testCasesAreComplete(): void
    {
        $cases = BackupType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(BackupType::BACKUP_TYPE_DATA, $cases);
        $this->assertContains(BackupType::BACKUP_TYPE_LOGS, $cases);
        $this->assertContains(BackupType::BACKUP_TYPE_CONTENT, $cases);
    }

    public function testTryFromValidValues(): void
    {
        foreach (BackupType::cases() as $case) {
            $this->assertSame($case, BackupType::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(BackupType::tryFrom('definitely-invalid'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);

        BackupType::from('definitely-invalid');
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(BackupType $case): string => $case->value, BackupType::cases());

        $this->assertCount(count($values), array_unique($values));
    }
}
