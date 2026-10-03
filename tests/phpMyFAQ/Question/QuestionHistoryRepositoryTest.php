<?php

declare(strict_types=1);

namespace phpMyFAQ\Question;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\QuestionHistoryEntity;
use phpMyFAQ\Enums\QuestionHistoryEventType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

#[CoversClass(QuestionHistoryRepository::class)]
#[UsesNamespace('phpMyFAQ')]
final class QuestionHistoryRepositoryTest extends TestCase
{
    private string $databaseFile;

    private QuestionHistoryRepository $repository;

    protected function setUp(): void
    {
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-question-history-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $this->repository = new QuestionHistoryRepository(new Configuration($dbHandle));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    public function testGetByQuestionReturnsEmptyArrayWithoutEvents(): void
    {
        $this->assertSame([], $this->repository->getByQuestion(1, 'en'));
    }

    public function testAddPersistsEventWithAllFields(): void
    {
        $event = new QuestionHistoryEntity(
            questionId: 7,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Answered,
            userId: 3,
            username: "Jane O'Connor",
            faqId: 42,
        );

        $this->assertTrue($this->repository->add($event));

        $events = $this->repository->getByQuestion(7, 'en');

        $this->assertCount(1, $events);
        $this->assertGreaterThan(0, $events[0]['id']);
        $this->assertSame(7, $events[0]['question_id']);
        $this->assertSame('en', $events[0]['question_lang']);
        $this->assertSame('answered', $events[0]['event_type']);
        $this->assertSame(3, $events[0]['user_id']);
        $this->assertSame("Jane O'Connor", $events[0]['username']);
        $this->assertSame(42, $events[0]['faq_id']);
        $this->assertMatchesRegularExpression('/^\d{14}$/', $events[0]['created']);
    }

    public function testEventsAreReturnedOldestFirst(): void
    {
        $this->repository->add(new QuestionHistoryEntity(7, 'en', QuestionHistoryEventType::Submitted, -1, 'Guest'));
        $this->repository->add(new QuestionHistoryEntity(7, 'en', QuestionHistoryEventType::Answered, 1, 'Admin', 42));
        $this->repository->add(new QuestionHistoryEntity(7, 'en', QuestionHistoryEventType::Reopened, 1, 'Admin'));

        $events = $this->repository->getByQuestion(7, 'en');

        $this->assertSame(['submitted', 'answered', 'reopened'], array_column($events, 'event_type'));
        $this->assertSame([0, 42, 0], array_column($events, 'faq_id'));

        $ids = array_column($events, 'id');
        $sorted = $ids;
        sort($sorted);
        $this->assertSame($sorted, $ids);
    }

    public function testGetByQuestionIsScopedToQuestionAndLanguage(): void
    {
        $this->repository->add(new QuestionHistoryEntity(7, 'en', QuestionHistoryEventType::Submitted, -1, 'Guest'));
        $this->repository->add(new QuestionHistoryEntity(7, 'de', QuestionHistoryEventType::Submitted, -1, 'Gast'));
        $this->repository->add(new QuestionHistoryEntity(8, 'en', QuestionHistoryEventType::Submitted, -1, 'Other'));

        $this->assertSame(['Guest'], array_column($this->repository->getByQuestion(7, 'en'), 'username'));
        $this->assertSame(['Gast'], array_column($this->repository->getByQuestion(7, 'de'), 'username'));
        $this->assertSame(['Other'], array_column($this->repository->getByQuestion(8, 'en'), 'username'));
        $this->assertSame([], $this->repository->getByQuestion(9, 'en'));
    }
}
