<?php

namespace phpMyFAQ\Enums\Forms;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AskQuestionInputIds::class)]
class AskQuestionInputIdsTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame(1, AskQuestionInputIds::TITLE->value);
        $this->assertSame(2, AskQuestionInputIds::MESSAGE->value);
        $this->assertSame(3, AskQuestionInputIds::NAME->value);
        $this->assertSame(4, AskQuestionInputIds::EMAIL->value);
        $this->assertSame(5, AskQuestionInputIds::CATEGORY->value);
        $this->assertSame(6, AskQuestionInputIds::QUESTION->value);
    }

    public function testTryFromValidValues(): void
    {
        foreach (AskQuestionInputIds::cases() as $case) {
            $this->assertSame($case, AskQuestionInputIds::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(AskQuestionInputIds::tryFrom(99999));
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(static fn(AskQuestionInputIds $case): int => $case->value, AskQuestionInputIds::cases());

        $this->assertSame(array_values(array_unique($values)), $values);
    }
}
