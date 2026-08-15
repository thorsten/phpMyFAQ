<?php

declare(strict_types=1);

namespace phpMyFAQ\Entity;

use InvalidArgumentException;
use phpMyFAQ\Enums\QuestionHistoryEventType;
use PHPUnit\Framework\TestCase;

final class QuestionHistoryEntityTest extends TestCase
{
    public function testConstructionExposesAllValues(): void
    {
        $entity = new QuestionHistoryEntity(
            questionId: 42,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Answered,
            userId: 1,
            username: 'admin',
            faqId: 7,
            created: '2026-08-15T10:00:00+0000',
        );

        $this->assertSame(42, $entity->getQuestionId());
        $this->assertSame('en', $entity->getQuestionLanguage());
        $this->assertSame(QuestionHistoryEventType::Answered, $entity->getEventType());
        $this->assertSame(1, $entity->getUserId());
        $this->assertSame('admin', $entity->getUsername());
        $this->assertSame(7, $entity->getFaqId());
        $this->assertSame('2026-08-15T10:00:00+0000', $entity->getCreated());
    }

    public function testDefaultsForFaqIdAndCreated(): void
    {
        $entity = new QuestionHistoryEntity(
            questionId: 1,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: 'guest',
        );

        $this->assertSame(0, $entity->getFaqId());
        $this->assertNull($entity->getCreated());
    }

    public function testRejectsNonPositiveQuestionId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new QuestionHistoryEntity(
            questionId: 0,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: 'guest',
        );
    }

    public function testRejectsEmptyLanguage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new QuestionHistoryEntity(
            questionId: 1,
            questionLanguage: '',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: 'guest',
        );
    }

    public function testRejectsEmptyUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new QuestionHistoryEntity(
            questionId: 1,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: '',
        );
    }
}
