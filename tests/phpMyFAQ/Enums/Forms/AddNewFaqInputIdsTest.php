<?php

namespace phpMyFAQ\Enums\Forms;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AddNewFaqInputIds::class)]
class AddNewFaqInputIdsTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame(1, AddNewFaqInputIds::TITLE->value);
        $this->assertSame(2, AddNewFaqInputIds::MESSAGE->value);
        $this->assertSame(3, AddNewFaqInputIds::NAME->value);
        $this->assertSame(4, AddNewFaqInputIds::EMAIL->value);
        $this->assertSame(5, AddNewFaqInputIds::CATEGORY->value);
        $this->assertSame(6, AddNewFaqInputIds::QUESTION->value);
        $this->assertSame(7, AddNewFaqInputIds::ANSWER->value);
        $this->assertSame(8, AddNewFaqInputIds::KEYWORDS->value);
    }

    public function testTryFromValidValues(): void
    {
        foreach (AddNewFaqInputIds::cases() as $case) {
            $this->assertSame($case, AddNewFaqInputIds::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(AddNewFaqInputIds::tryFrom(99999));
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(AddNewFaqInputIds $case): int => $case->value, AddNewFaqInputIds::cases());

        $this->assertSame(array_values(array_unique($values)), $values);
    }
}
