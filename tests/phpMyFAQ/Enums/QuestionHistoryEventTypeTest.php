<?php

declare(strict_types=1);

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QuestionHistoryEventType::class)]
final class QuestionHistoryEventTypeTest extends TestCase
{
    public function testLifecycleEventsAreComplete(): void
    {
        $this->assertSame(
            ['submitted', 'answered', 'reopened'],
            array_map(static fn(QuestionHistoryEventType $case): string => $case->value, QuestionHistoryEventType::cases()),
        );
    }

    public function testTryFromRoundtripsForAllCases(): void
    {
        foreach (QuestionHistoryEventType::cases() as $case) {
            $this->assertSame($case, QuestionHistoryEventType::tryFrom($case->value));
        }
    }

    public function testUnknownValueIsRejected(): void
    {
        $this->assertNull(QuestionHistoryEventType::tryFrom('deleted'));
        $this->assertNull(QuestionHistoryEventType::tryFrom('Answered'));
    }

    public function testValuesFitTheDatabaseColumn(): void
    {
        // faqquestion_history.event_type is a VARCHAR(20).
        foreach (QuestionHistoryEventType::cases() as $case) {
            $this->assertLessThanOrEqual(20, strlen($case->value));
        }
    }
}
