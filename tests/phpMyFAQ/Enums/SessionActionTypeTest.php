<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SessionActionType::class)]
class SessionActionTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('404', SessionActionType::NOT_FOUND->value);
        $this->assertSame('old_session', SessionActionType::OLD_SESSION->value);
    }

    public function testCasesAreComplete(): void
    {
        $cases = SessionActionType::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(SessionActionType::NOT_FOUND, $cases);
        $this->assertContains(SessionActionType::OLD_SESSION, $cases);
    }

    public function testTryFromValidValues(): void
    {
        foreach (SessionActionType::cases() as $case) {
            $this->assertSame($case, SessionActionType::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(SessionActionType::tryFrom('definitely-invalid'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);

        SessionActionType::from('definitely-invalid');
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(SessionActionType $case): string => $case->value, SessionActionType::cases());

        $this->assertCount(count($values), array_unique($values));
    }
}
