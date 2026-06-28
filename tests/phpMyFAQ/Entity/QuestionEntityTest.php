<?php

namespace phpMyFAQ\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class QuestionEntityTest
 */
class QuestionEntityTest extends TestCase
{
    private QuestionEntity $question;

    protected function setUp(): void
    {
        $this->question = new QuestionEntity();
    }

    /**
     * Test QuestionEntity instantiation
     */
    public function testQuestionEntityInstantiation(): void
    {
        $this->assertInstanceOf(QuestionEntity::class, $this->question);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->question->setId($id);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->question->getId());
    }

    /**
     * Test language getter and setter
     */
    public function testLanguageGetterAndSetter(): void
    {
        $language = 'en';
        $result = $this->question->setLanguage($language);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertEquals($language, $this->question->getLanguage());
    }

    /**
     * Test username getter and setter
     */
    public function testUsernameGetterAndSetter(): void
    {
        $username = 'john.doe';
        $result = $this->question->setUsername($username);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertEquals($username, $this->question->getUsername());
    }

    /**
     * Test email getter and setter
     */
    public function testEmailGetterAndSetter(): void
    {
        $email = 'john.doe@example.com';
        $result = $this->question->setEmail($email);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertEquals($email, $this->question->getEmail());
    }

    /**
     * Test categoryId getter and setter
     */
    public function testCategoryIdGetterAndSetter(): void
    {
        $categoryId = 7;
        $result = $this->question->setCategoryId($categoryId);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertEquals($categoryId, $this->question->getCategoryId());
    }

    /**
     * Test question getter and setter
     */
    public function testQuestionGetterAndSetter(): void
    {
        $questionText = 'How do I reset my password?';
        $result = $this->question->setQuestion($questionText);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertEquals($questionText, $this->question->getQuestion());
    }

    /**
     * Test created getter and setter
     */
    public function testCreatedGetterAndSetter(): void
    {
        $created = '2026-06-28 10:15:00';
        $result = $this->question->setCreated($created);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertEquals($created, $this->question->getCreated());
    }

    /**
     * Test answerId getter and setter
     */
    public function testAnswerIdGetterAndSetter(): void
    {
        $answerId = 99;
        $result = $this->question->setAnswerId($answerId);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertEquals($answerId, $this->question->getAnswerId());
    }

    /**
     * Test isVisible getter and setter
     */
    public function testIsVisibleGetterAndSetter(): void
    {
        $result = $this->question->setIsVisible(true);

        $this->assertInstanceOf(QuestionEntity::class, $result); // Test fluent interface
        $this->assertTrue($this->question->isVisible());

        $this->question->setIsVisible(false);
        $this->assertFalse($this->question->isVisible());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $result = $this->question
            ->setId(1)
            ->setLanguage('de')
            ->setUsername('jane')
            ->setEmail('jane@example.com')
            ->setCategoryId(3)
            ->setQuestion('What is phpMyFAQ?')
            ->setCreated('2026-01-01 00:00:00')
            ->setAnswerId(42)
            ->setIsVisible(true);

        $this->assertInstanceOf(QuestionEntity::class, $result);
        $this->assertEquals(1, $this->question->getId());
        $this->assertEquals('de', $this->question->getLanguage());
        $this->assertEquals('jane', $this->question->getUsername());
        $this->assertEquals('jane@example.com', $this->question->getEmail());
        $this->assertEquals(3, $this->question->getCategoryId());
        $this->assertEquals('What is phpMyFAQ?', $this->question->getQuestion());
        $this->assertEquals('2026-01-01 00:00:00', $this->question->getCreated());
        $this->assertEquals(42, $this->question->getAnswerId());
        $this->assertTrue($this->question->isVisible());
    }

    /**
     * Test zero values
     */
    public function testZeroValues(): void
    {
        $this->question->setId(0)->setCategoryId(0)->setAnswerId(0);

        $this->assertEquals(0, $this->question->getId());
        $this->assertEquals(0, $this->question->getCategoryId());
        $this->assertEquals(0, $this->question->getAnswerId());
    }

    /**
     * Test negative values
     */
    public function testNegativeValues(): void
    {
        $this->question->setId(-1)->setCategoryId(-3)->setAnswerId(-99);

        $this->assertEquals(-1, $this->question->getId());
        $this->assertEquals(-3, $this->question->getCategoryId());
        $this->assertEquals(-99, $this->question->getAnswerId());
    }

    /**
     * Test integer boundaries
     */
    public function testIntegerBoundaries(): void
    {
        $this->question->setId(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $this->question->getId());

        $this->question->setId(PHP_INT_MIN);
        $this->assertEquals(PHP_INT_MIN, $this->question->getId());
    }

    /**
     * Test empty string values
     */
    public function testEmptyStringValues(): void
    {
        $this->question
            ->setLanguage('')
            ->setUsername('')
            ->setEmail('')
            ->setQuestion('')
            ->setCreated('');

        $this->assertEquals('', $this->question->getLanguage());
        $this->assertEquals('', $this->question->getUsername());
        $this->assertEquals('', $this->question->getEmail());
        $this->assertEquals('', $this->question->getQuestion());
        $this->assertEquals('', $this->question->getCreated());
    }

    /**
     * Test question with multiline and special characters
     */
    public function testQuestionWithSpecialCharacters(): void
    {
        $questionText = "How do I use äöü?\nAnd <html> & \"quotes\"?";
        $this->question->setQuestion($questionText);
        $this->assertEquals($questionText, $this->question->getQuestion());
    }

    /**
     * Test various language codes
     */
    public function testVariousLanguageCodes(): void
    {
        $languages = ['en', 'de', 'fr', 'es', 'pt-br', 'zh'];

        foreach ($languages as $language) {
            $this->question->setLanguage($language);
            $this->assertEquals($language, $this->question->getLanguage());
        }
    }

    /**
     * Test isVisible toggling
     */
    public function testIsVisibleToggling(): void
    {
        $this->question->setIsVisible(false);
        $this->assertFalse($this->question->isVisible());

        $this->question->setIsVisible(true);
        $this->assertTrue($this->question->isVisible());
    }

    /**
     * Test object state independence between instances
     */
    public function testObjectStateIndependence(): void
    {
        $first = new QuestionEntity();
        $first->setId(1)->setUsername('first')->setCategoryId(10);

        $second = new QuestionEntity();
        $second->setId(2)->setUsername('second')->setCategoryId(20);

        $this->assertEquals(1, $first->getId());
        $this->assertEquals('first', $first->getUsername());
        $this->assertEquals(10, $first->getCategoryId());

        $this->assertEquals(2, $second->getId());
        $this->assertEquals('second', $second->getUsername());
        $this->assertEquals(20, $second->getCategoryId());
    }
}
