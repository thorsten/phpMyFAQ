<?php

namespace phpMyFAQ\Questions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\QuestionEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Question\QuestionRepository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class QuestionRepositoryTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private QuestionRepository $repository;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($this->dbHandle);
        $language = new Language($configuration, $this->createMock(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->repository = new QuestionRepository($configuration);
    }

    protected function tearDown(): void
    {
        $this->dbHandle->query('DELETE FROM faqquestions');
        parent::tearDown();
    }

    public function testAdd(): void
    {
        $questionEntity = new QuestionEntity();
        $questionEntity
            ->setUsername('testuser')
            ->setEmail('test@example.org')
            ->setCategoryId(1)
            ->setQuestion('Test question')
            ->setLanguage('en')
            ->setIsVisible(true);

        $result = $this->repository->add($questionEntity);

        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        // Add a question first
        $questionEntity = new QuestionEntity();
        $questionEntity
            ->setUsername('testuser')
            ->setEmail('test@example.org')
            ->setCategoryId(1)
            ->setQuestion('Test question to delete')
            ->setLanguage('en')
            ->setIsVisible(true);

        $this->repository->add($questionEntity);

        // Delete the question
        $result = $this->repository->delete(1, 'en');

        $this->assertTrue($result);

        // Verify it was deleted
        $question = $this->repository->getById(1, 'en');
        $this->assertEmpty($question);
    }

    public function testGetById(): void
    {
        // Add a question first
        $questionEntity = new QuestionEntity();
        $questionEntity
            ->setUsername('testuser')
            ->setEmail('test@example.org')
            ->setCategoryId(1)
            ->setQuestion('Test question')
            ->setLanguage('en')
            ->setIsVisible(true);

        $this->repository->add($questionEntity);

        // Get the question
        $question = $this->repository->getById(1, 'en');

        $this->assertIsArray($question);
        $this->assertCount(8, $question);
        $this->assertEquals('testuser', $question['username']);
        $this->assertEquals('test@example.org', $question['email']);
        $this->assertEquals('Test question', $question['question']);
    }

    public function testGetByIdReturnsEmptyArrayForNonExistent(): void
    {
        $question = $this->repository->getById(9999, 'en');

        $this->assertIsArray($question);
        $this->assertEmpty($question);
    }

    public function testGetAll(): void
    {
        // Add multiple questions
        $questionEntity1 = new QuestionEntity();
        $questionEntity1
            ->setUsername('user1')
            ->setEmail('user1@example.org')
            ->setCategoryId(1)
            ->setQuestion('First question')
            ->setLanguage('en')
            ->setIsVisible(true);

        $this->repository->add($questionEntity1);

        $questionEntity2 = new QuestionEntity();
        $questionEntity2
            ->setUsername('user2')
            ->setEmail('user2@example.org')
            ->setCategoryId(2)
            ->setQuestion('Second question')
            ->setLanguage('en')
            ->setIsVisible(false);

        $this->repository->add($questionEntity2);

        // Get all questions
        $questions = $this->repository->getAll('en', true);

        $this->assertIsArray($questions);
        $this->assertCount(2, $questions);
        $this->assertEquals('First question', $questions[0]['question']);
        $this->assertEquals('Second question', $questions[1]['question']);
    }

    public function testGetAllShowOnlyVisible(): void
    {
        // Add a visible question
        $questionEntity1 = new QuestionEntity();
        $questionEntity1
            ->setUsername('user1')
            ->setEmail('user1@example.org')
            ->setCategoryId(1)
            ->setQuestion('Visible question')
            ->setLanguage('en')
            ->setIsVisible(true);

        $this->repository->add($questionEntity1);

        // Add invisible question
        $questionEntity2 = new QuestionEntity();
        $questionEntity2
            ->setUsername('user2')
            ->setEmail('user2@example.org')
            ->setCategoryId(2)
            ->setQuestion('Invisible question')
            ->setLanguage('en')
            ->setIsVisible(false);

        $this->repository->add($questionEntity2);

        // Get only visible questions
        $questions = $this->repository->getAll('en', false);

        $this->assertIsArray($questions);
        $this->assertCount(1, $questions);
        $this->assertEquals('Visible question', $questions[0]['question']);
        $this->assertEquals('Y', $questions[0]['is_visible']);
    }

    public function testGetVisibility(): void
    {
        // Add a question with visibility set to false
        $questionEntity = new QuestionEntity();
        $questionEntity
            ->setUsername('testuser')
            ->setEmail('test@example.org')
            ->setCategoryId(1)
            ->setQuestion('Test question')
            ->setLanguage('en')
            ->setIsVisible(false);

        $this->repository->add($questionEntity);

        $visibility = $this->repository->getVisibility(1, 'en');

        $this->assertEquals('N', $visibility);
    }

    public function testGetVisibilityReturnsEmptyForNonExistent(): void
    {
        $visibility = $this->repository->getVisibility(9999, 'en');

        $this->assertEquals('', $visibility);
    }

    public function testSetVisibility(): void
    {
        // Add a question
        $questionEntity = new QuestionEntity();
        $questionEntity
            ->setUsername('testuser')
            ->setEmail('test@example.org')
            ->setCategoryId(1)
            ->setQuestion('Test question')
            ->setLanguage('en')
            ->setIsVisible(true);

        $this->repository->add($questionEntity);

        // Verify initial visibility
        $this->assertEquals('Y', $this->repository->getVisibility(1, 'en'));

        // Change visibility
        $result = $this->repository->setVisibility(1, 'N', 'en');

        $this->assertTrue($result);
        $this->assertEquals('N', $this->repository->getVisibility(1, 'en'));
    }

    public function testUpdateQuestionAnswer(): void
    {
        // Add a question
        $questionEntity = new QuestionEntity();
        $questionEntity
            ->setUsername('testuser')
            ->setEmail('test@example.org')
            ->setCategoryId(1)
            ->setQuestion('Test question')
            ->setLanguage('en')
            ->setIsVisible(true);

        $this->repository->add($questionEntity);

        // Update the answer_id
        $result = $this->repository->updateQuestionAnswer(1, 42, 2);

        $this->assertTrue($result);

        // Verify the update
        $question = $this->repository->getById(1, 'en');
        $this->assertEquals(2, $question['category_id']);
    }

    public function testGetAllReturnsEmptyArrayForNonExistentLanguage(): void
    {
        // Add a question in English
        $questionEntity = new QuestionEntity();
        $questionEntity
            ->setUsername('testuser')
            ->setEmail('test@example.org')
            ->setCategoryId(1)
            ->setQuestion('Test question')
            ->setLanguage('en')
            ->setIsVisible(true);

        $this->repository->add($questionEntity);

        // Try to get questions for a different language
        $questions = $this->repository->getAll('de');

        $this->assertIsArray($questions);
        $this->assertEmpty($questions);
    }
}
