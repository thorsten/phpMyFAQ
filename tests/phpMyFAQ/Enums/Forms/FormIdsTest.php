<?php

namespace phpMyFAQ\Enums\Forms;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormIds::class)]
class FormIdsTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame(1, FormIds::ASK_QUESTION->value);
        $this->assertSame(2, FormIds::ADD_NEW_FAQ->value);
    }

    public function testTryFromValidValues(): void
    {
        foreach (FormIds::cases() as $case) {
            $this->assertSame($case, FormIds::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(FormIds::tryFrom(99999));
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(FormIds $case): int => $case->value, FormIds::cases());

        $this->assertSame(array_values(array_unique($values)), $values);
    }
}
