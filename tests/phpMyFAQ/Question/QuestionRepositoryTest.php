<?php

declare(strict_types=1);

namespace phpMyFAQ\Question;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\QuestionEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

#[CoversClass(QuestionRepository::class)]
#[UsesNamespace('phpMyFAQ')]
final class QuestionRepositoryTest extends TestCase
{
    private string $databaseFile;

    private QuestionRepository $repository;

    protected function setUp(): void
    {
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-question-repository-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $this->repository = new QuestionRepository(new Configuration($dbHandle));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    private function createQuestion(string $language = 'en', string $text = 'How do I reset my password?', bool $visible = true): QuestionEntity
    {
        return new QuestionEntity()
            ->setLanguage($language)
            ->setUsername('Jane Doe')
            ->setEmail('jane@example.org')
            ->setCategoryId(1)
            ->setQuestion($text)
            ->setIsVisible($visible);
    }

    public function testAddReturnsNewIdAndPersistsQuestion(): void
    {
        $questionId = $this->repository->add($this->createQuestion());

        $this->assertGreaterThan(0, $questionId);

        $question = $this->repository->getById($questionId, 'en');

        $this->assertSame($questionId, $question['id']);
        $this->assertSame('en', $question['lang']);
        $this->assertSame('Jane Doe', $question['username']);
        $this->assertSame('jane@example.org', $question['email']);
        $this->assertSame(1, $question['category_id']);
        $this->assertSame('How do I reset my password?', $question['question']);
        $this->assertSame('Y', $question['is_visible']);
        $this->assertMatchesRegularExpression('/^\d{14}$/', $question['created']);
    }

    public function testAddAssignsIncreasingIds(): void
    {
        $first = $this->repository->add($this->createQuestion());
        $second = $this->repository->add($this->createQuestion(text: 'Second question'));

        $this->assertGreaterThan($first, $second);
    }

    public function testAddEscapesQuotesInQuestionText(): void
    {
        $questionId = $this->repository->add($this->createQuestion(text: "What's the \"best\" way?"));

        $this->assertSame("What's the \"best\" way?", $this->repository->getById($questionId, 'en')['question']);
    }

    public function testGetByIdReturnsEmptyArrayForUnknownQuestionOrWrongLanguage(): void
    {
        $questionId = $this->repository->add($this->createQuestion());

        $this->assertSame([], $this->repository->getById(999_999, 'en'));
        $this->assertSame([], $this->repository->getById($questionId, 'de'));
    }

    public function testGetAllFiltersByLanguageAndVisibility(): void
    {
        $visibleEn = $this->repository->add($this->createQuestion());
        $hiddenEn = $this->repository->add($this->createQuestion(text: 'Hidden', visible: false));
        $visibleDe = $this->repository->add($this->createQuestion(language: 'de', text: 'Sichtbar'));

        $this->assertEqualsCanonicalizing([$visibleEn, $hiddenEn], array_column($this->repository->getAll('en'), 'id'));
        $this->assertSame([$visibleEn], array_column($this->repository->getAll('en', showAll: false), 'id'));
        $this->assertSame([$visibleDe], array_column($this->repository->getAll('de'), 'id'));
        $this->assertEqualsCanonicalizing(
            [$visibleEn, $hiddenEn, $visibleDe],
            array_column($this->repository->getAll(''), 'id'),
        );
    }

    public function testGetAllReturnsTypedRowsIncludingAnswerId(): void
    {
        $questionId = $this->repository->add($this->createQuestion());

        $rows = $this->repository->getAll('en');

        $this->assertCount(1, $rows);
        $this->assertSame($questionId, $rows[0]['id']);
        $this->assertSame(0, $rows[0]['answer_id']);
        $this->assertSame('Y', $rows[0]['is_visible']);
        $this->assertSame(1, $rows[0]['category_id']);
    }

    public function testGetAllReturnsEmptyArrayWhenNoQuestionsExist(): void
    {
        $this->assertSame([], $this->repository->getAll('en'));
    }

    public function testVisibilityCanBeReadAndChanged(): void
    {
        $questionId = $this->repository->add($this->createQuestion());

        $this->assertSame('Y', $this->repository->getVisibility($questionId, 'en'));

        $this->assertTrue($this->repository->setVisibility($questionId, 'N', 'en'));

        $this->assertSame('N', $this->repository->getVisibility($questionId, 'en'));
        $this->assertSame('N', $this->repository->getById($questionId, 'en')['is_visible']);
    }

    public function testGetVisibilityReturnsEmptyStringForUnknownQuestion(): void
    {
        $this->assertSame('', $this->repository->getVisibility(999_999, 'en'));
    }

    public function testUpdateQuestionAnswerLinksFaqAndMovesCategory(): void
    {
        $questionId = $this->repository->add($this->createQuestion());

        $this->assertTrue($this->repository->updateQuestionAnswer($questionId, 42, 7));

        $row = $this->repository->getAll('en')[0];
        $this->assertSame(42, $row['answer_id']);
        $this->assertSame(7, $row['category_id']);
    }

    public function testReopenClearsAnswerOnlyOnce(): void
    {
        $questionId = $this->repository->add($this->createQuestion());
        $this->repository->updateQuestionAnswer($questionId, 42, 1);

        $this->assertTrue($this->repository->reopen($questionId));
        $this->assertSame(0, $this->repository->getAll('en')[0]['answer_id']);

        // Already open: nothing changes, so the repository reports false.
        $this->assertFalse($this->repository->reopen($questionId));
    }

    public function testReopenReturnsFalseForUnknownQuestion(): void
    {
        $this->assertFalse($this->repository->reopen(999_999));
    }

    public function testDeleteRemovesOnlyTheMatchingLanguage(): void
    {
        $englishId = $this->repository->add($this->createQuestion());
        $germanId = $this->repository->add($this->createQuestion(language: 'de'));

        $this->assertTrue($this->repository->delete($englishId, 'en'));

        $this->assertSame([], $this->repository->getById($englishId, 'en'));
        $this->assertSame($germanId, $this->repository->getById($germanId, 'de')['id']);
    }
}
