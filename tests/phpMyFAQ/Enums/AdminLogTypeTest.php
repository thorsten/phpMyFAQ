<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminLogType::class)]
class AdminLogTypeTest extends TestCase
{
    public function testCasesNotEmpty(): void
    {
        $this->assertNotEmpty(AdminLogType::cases());
    }

    public function testTryFromRoundtripsForAllCases(): void
    {
        foreach (AdminLogType::cases() as $case) {
            $this->assertSame($case, AdminLogType::tryFrom($case->value));
        }
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(AdminLogType $case): string => $case->value, AdminLogType::cases());

        $this->assertSame(array_values(array_unique($values)), $values);
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(AdminLogType::tryFrom('this-is-not-a-valid-admin-log-type'));
    }

    public function testRepresentativeValues(): void
    {
        $this->assertSame('faq-add', AdminLogType::FAQ_ADD->value);
        $this->assertSame('category-delete', AdminLogType::CATEGORY_DELETE->value);
        $this->assertSame('auth-login-success', AdminLogType::AUTH_LOGIN_SUCCESS->value);
        $this->assertSame('security-csrf-violation', AdminLogType::SECURITY_CSRF_VIOLATION->value);
        $this->assertSame('system-update-completed', AdminLogType::SYSTEM_UPDATE_COMPLETED->value);
    }
}
