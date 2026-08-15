<?php

namespace phpMyFAQ\Questions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\QuestionHistoryEntity;
use phpMyFAQ\Enums\QuestionHistoryEventType;
use phpMyFAQ\Language;
use phpMyFAQ\Question\QuestionHistoryRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class QuestionHistoryRepositoryTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private QuestionHistoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($this->dbHandle);
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->repository = new QuestionHistoryRepository($configuration);
    }

    protected function tearDown(): void
    {
        $this->dbHandle->query('DELETE FROM faqquestion_history');
        parent::tearDown();
    }

    public function testAdd(): void
    {
        $result = $this->repository->add(new QuestionHistoryEntity(
            questionId: 1,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: 'guest',
        ));

        $this->assertTrue($result);
    }

    public function testGetByQuestionReturnsEventsInOrder(): void
    {
        $this->repository->add(new QuestionHistoryEntity(
            questionId: 1,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: 'guest',
        ));
        $this->repository->add(new QuestionHistoryEntity(
            questionId: 1,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Answered,
            userId: 1,
            username: 'admin',
            faqId: 7,
        ));

        $rows = $this->repository->getByQuestion(1, 'en');

        $this->assertCount(2, $rows);
        $this->assertSame('submitted', $rows[0]['event_type']);
        $this->assertSame('answered', $rows[1]['event_type']);
        $this->assertSame(7, $rows[1]['faq_id']);
        $this->assertSame(1, $rows[1]['user_id']);
        $this->assertSame('admin', $rows[1]['username']);
        $this->assertNotSame('', (string) $rows[0]['created']);
    }

    public function testGetByQuestionFiltersByIdAndLanguage(): void
    {
        $this->repository->add(new QuestionHistoryEntity(
            questionId: 1,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: 'guest',
        ));
        $this->repository->add(new QuestionHistoryEntity(
            questionId: 2,
            questionLanguage: 'en',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: 'guest',
        ));
        $this->repository->add(new QuestionHistoryEntity(
            questionId: 1,
            questionLanguage: 'de',
            eventType: QuestionHistoryEventType::Submitted,
            userId: -1,
            username: 'gast',
        ));

        $rows = $this->repository->getByQuestion(1, 'en');

        $this->assertCount(1, $rows);
    }

    public function testGetByQuestionReturnsEmptyArrayForUnknownQuestion(): void
    {
        $this->assertSame([], $this->repository->getByQuestion(9999, 'en'));
    }
}
