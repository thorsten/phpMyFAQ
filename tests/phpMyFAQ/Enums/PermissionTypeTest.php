<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PermissionType::class)]
class PermissionTypeTest extends TestCase
{
    public function testCasesNotEmpty(): void
    {
        $this->assertNotEmpty(PermissionType::cases());
    }

    public function testTryFromRoundtripsForAllCases(): void
    {
        foreach (PermissionType::cases() as $case) {
            $this->assertSame($case, PermissionType::tryFrom($case->value));
        }
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(PermissionType $case): string => $case->value, PermissionType::cases());

        $this->assertSame(array_values(array_unique($values)), $values);
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(PermissionType::tryFrom('this-is-not-a-valid-permission'));
    }

    public function testRepresentativeValues(): void
    {
        $this->assertSame('add_faq', PermissionType::FAQ_ADD->value);
        $this->assertSame('delete_faq', PermissionType::FAQ_DELETE->value);
        $this->assertSame('editconfig', PermissionType::CONFIGURATION_EDIT->value);
        $this->assertSame('addcateg', PermissionType::CATEGORY_ADD->value);
        $this->assertSame('viewadminlink', PermissionType::VIEW_ADMIN_LINK->value);
    }
}
