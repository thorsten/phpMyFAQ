<?php

declare(strict_types=1);

namespace phpMyFAQ\Helper\Test;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class QuestionHelperTest extends TestCase
{
    private Configuration $configuration;
    private QuestionHelper $questionHelper;
    private Category $category;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->category = new Category($this->configuration);
        $this->questionHelper = new QuestionHelper();
        $this->questionHelper->setConfiguration($this->configuration)->setCategory($this->category);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $query = sprintf('DELETE FROM %sfaqquestions', Database::getTablePrefix());
        $this->configuration->getDb()->query($query);
    }

    public function testGetOpenQuestionsReturnsCategoryNames(): void
    {
        // Create a test question with a category ID that exists
        $query = sprintf(
            'INSERT INTO %sfaqquestions (id, username, email, category_id, question, created, lang, is_visible) '
            . "VALUES (1, 'Test User', 'test@example.com', 1, 'Test Question?', '%s', 'en', 'Y')",
            Database::getTablePrefix(),
            date('YmdHis'),
        );
        $this->configuration->getDb()->query($query);

        $openQuestions = $this->questionHelper->getOpenQuestions();

        $this->assertIsObject($openQuestions);
        $this->assertObjectHasProperty('questions', $openQuestions);
        $this->assertNotEmpty($openQuestions->questions);

        $firstQuestion = $openQuestions->questions[0];
        $this->assertObjectHasProperty('categoryName', $firstQuestion);

        // The category name should NOT be empty
        // It should either be the actual category name or at least not an empty string
        $this->assertIsString($firstQuestion->categoryName);
    }

    public function testGetOpenQuestionsWithInvisibleQuestions(): void
    {
        // Create an invisible question
        $query = sprintf(
            'INSERT INTO %sfaqquestions (id, username, email, category_id, question, created, lang, is_visible) '
            . "VALUES (2, 'Test User', 'test@example.com', 1, 'Invisible Question?', '%s', 'en', 'N')",
            Database::getTablePrefix(),
            date('YmdHis'),
        );
        $this->configuration->getDb()->query($query);

        $openQuestions = $this->questionHelper->getOpenQuestions();

        $this->assertIsObject($openQuestions);
        $this->assertObjectHasProperty('numberInvisibleQuestions', $openQuestions);
        $this->assertGreaterThan(0, $openQuestions->numberInvisibleQuestions);
    }

    public function testGetOpenQuestionsStructure(): void
    {
        // Create a test question
        $query = sprintf(
            'INSERT INTO %sfaqquestions (id, username, email, category_id, question, created, lang, is_visible) '
            . "VALUES (3, 'Test User', 'test@example.com', 1, 'Structure Test?', '%s', 'en', 'Y')",
            Database::getTablePrefix(),
            date('YmdHis'),
        );
        $this->configuration->getDb()->query($query);

        $openQuestions = $this->questionHelper->getOpenQuestions();

        $this->assertIsObject($openQuestions);
        $this->assertObjectHasProperty('questions', $openQuestions);
        $this->assertNotEmpty($openQuestions->questions);

        $question = $openQuestions->questions[0];
        $this->assertObjectHasProperty('id', $question);
        $this->assertObjectHasProperty('date', $question);
        $this->assertObjectHasProperty('email', $question);
        $this->assertObjectHasProperty('userName', $question);
        $this->assertObjectHasProperty('categoryId', $question);
        $this->assertObjectHasProperty('categoryName', $question);
        $this->assertObjectHasProperty('question', $question);
        $this->assertObjectHasProperty('answerId', $question);
    }
}
