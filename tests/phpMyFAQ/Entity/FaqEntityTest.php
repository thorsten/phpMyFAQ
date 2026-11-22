<?php

namespace phpMyFAQ\Entity;

use DateTime;
use phpMyFAQ\Enums\FaqStatus;
use phpMyFAQ\Faq\PublicationWindow;
use PHPUnit\Framework\TestCase;

/**
 * Class FaqEntityTest
 */
class FaqEntityTest extends TestCase
{
    private FaqEntity $faqEntity;

    protected function setUp(): void
    {
        $this->faqEntity = new FaqEntity();
    }

    /**
     * Test FaqEntity instantiation
     */
    public function testFaqEntityInstantiation(): void
    {
        $this->assertInstanceOf(FaqEntity::class, $this->faqEntity);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $result = $this->faqEntity->setId(101);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame(101, $this->faqEntity->getId());
    }

    /**
     * Test id defaults to null
     */
    public function testIdDefaultsToNull(): void
    {
        $this->assertNull($this->faqEntity->getId());
    }

    /**
     * Test language getter and setter
     */
    public function testLanguageGetterAndSetter(): void
    {
        $result = $this->faqEntity->setLanguage('en');

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertEquals('en', $this->faqEntity->getLanguage());
    }

    /**
     * Test solutionId getter and setter
     */
    public function testSolutionIdGetterAndSetter(): void
    {
        $result = $this->faqEntity->setSolutionId(9000);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame(9000, $this->faqEntity->getSolutionId());
    }

    /**
     * Test solutionId defaults to null
     */
    public function testSolutionIdDefaultsToNull(): void
    {
        $this->assertNull($this->faqEntity->getSolutionId());
    }

    /**
     * Test revisionId getter and setter
     */
    public function testRevisionIdGetterAndSetter(): void
    {
        $result = $this->faqEntity->setRevisionId(3);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame(3, $this->faqEntity->getRevisionId());
    }

    /**
     * Test status getter and setter
     */
    public function testStatusGetterAndSetter(): void
    {
        $result = $this->faqEntity->setStatus(FaqStatus::Published);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame(FaqStatus::Published, $this->faqEntity->getStatus());

        $this->faqEntity->setStatus(FaqStatus::Draft);
        $this->assertSame(FaqStatus::Draft, $this->faqEntity->getStatus());
    }

    /**
     * Test a new entity starts as draft
     */
    public function testANewEntityStartsAsDraft(): void
    {
        $this->assertSame(FaqStatus::Draft, new FaqEntity()->getStatus());
    }

    /**
     * Test sticky getter and setter
     */
    public function testStickyGetterAndSetter(): void
    {
        $result = $this->faqEntity->setSticky(true);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertTrue($this->faqEntity->isSticky());

        $this->faqEntity->setSticky(false);
        $this->assertFalse($this->faqEntity->isSticky());
    }

    /**
     * Test keywords getter and setter
     */
    public function testKeywordsGetterAndSetter(): void
    {
        $result = $this->faqEntity->setKeywords('php, faq, test');

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertEquals('php, faq, test', $this->faqEntity->getKeywords());
    }

    /**
     * Test question getter and setter
     */
    public function testQuestionGetterAndSetter(): void
    {
        $result = $this->faqEntity->setQuestion('What is phpMyFAQ?');

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertEquals('What is phpMyFAQ?', $this->faqEntity->getQuestion());
    }

    /**
     * Test answer getter and setter
     */
    public function testAnswerGetterAndSetter(): void
    {
        $result = $this->faqEntity->setAnswer('A FAQ system.');

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertEquals('A FAQ system.', $this->faqEntity->getAnswer());
    }

    /**
     * Test author getter and setter
     */
    public function testAuthorGetterAndSetter(): void
    {
        $result = $this->faqEntity->setAuthor('Thorsten Rinne');

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertEquals('Thorsten Rinne', $this->faqEntity->getAuthor());
    }

    /**
     * Test email getter and setter
     */
    public function testEmailGetterAndSetter(): void
    {
        $result = $this->faqEntity->setEmail('thorsten@phpmyfaq.de');

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertEquals('thorsten@phpmyfaq.de', $this->faqEntity->getEmail());
    }

    /**
     * Test comment getter and setter
     */
    public function testCommentGetterAndSetter(): void
    {
        $result = $this->faqEntity->setComment(true);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertTrue($this->faqEntity->isComment());

        $this->faqEntity->setComment(false);
        $this->assertFalse($this->faqEntity->isComment());
    }

    /**
     * Test notes getter and setter
     */
    public function testNotesGetterAndSetter(): void
    {
        $result = $this->faqEntity->setNotes('Internal notes');

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertEquals('Internal notes', $this->faqEntity->getNotes());
    }

    /**
     * Test publicationWindow getter and setter
     */
    public function testPublicationWindowGetterAndSetter(): void
    {
        $publicationWindow = PublicationWindow::fromInput('2026-01-01T00:00', '2026-12-31T23:59:59');
        $result = $this->faqEntity->setPublicationWindow($publicationWindow);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame($publicationWindow, $this->faqEntity->getPublicationWindow());
    }

    /**
     * An entity nobody gave a window to must not invent one: the former defaults ("from now on",
     * "until 9999") were written back on every save and reset the stored window.
     */
    public function testPublicationWindowIsUndecidedWhenNotSet(): void
    {
        $this->assertNull($this->faqEntity->getPublicationWindow());
    }

    /**
     * Test createdDate getter and setter
     */
    public function testCreatedDateGetterAndSetter(): void
    {
        $dateTime = new DateTime('2026-06-28 12:00:00');
        $result = $this->faqEntity->setCreatedDate($dateTime);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame($dateTime, $this->faqEntity->getCreatedDate());
    }

    /**
     * Test createdDate returns a DateTime even when not set
     */
    public function testCreatedDateDefaultsToNow(): void
    {
        $this->assertInstanceOf(DateTime::class, $this->faqEntity->getCreatedDate());
    }

    /**
     * Test updatedDate getter and setter
     */
    public function testUpdatedDateGetterAndSetter(): void
    {
        $dateTime = new DateTime('2026-06-28 15:30:00');
        $result = $this->faqEntity->setUpdatedDate($dateTime);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame($dateTime, $this->faqEntity->getUpdatedDate());
    }

    /**
     * Test updatedDate defaults to null
     */
    public function testUpdatedDateDefaultsToNull(): void
    {
        $this->assertNull($this->faqEntity->getUpdatedDate());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $publicationWindow = PublicationWindow::fromInput('2026-01-01T00:00', '2026-12-31T23:59:59');
        $createdDate = new DateTime('2026-06-28 12:00:00');
        $updatedDate = new DateTime('2026-06-28 13:00:00');

        $result = $this->faqEntity
            ->setId(1)
            ->setLanguage('en')
            ->setSolutionId(1000)
            ->setRevisionId(2)
            ->setStatus(FaqStatus::Published)
            ->setSticky(false)
            ->setKeywords('keyword')
            ->setQuestion('Question?')
            ->setAnswer('Answer.')
            ->setAuthor('Author')
            ->setEmail('author@example.com')
            ->setComment(true)
            ->setNotes('Notes')
            ->setPublicationWindow($publicationWindow)
            ->setCreatedDate($createdDate)
            ->setUpdatedDate($updatedDate);

        $this->assertInstanceOf(FaqEntity::class, $result);
        $this->assertSame(1, $this->faqEntity->getId());
        $this->assertEquals('en', $this->faqEntity->getLanguage());
        $this->assertSame(1000, $this->faqEntity->getSolutionId());
        $this->assertSame(2, $this->faqEntity->getRevisionId());
        $this->assertSame(FaqStatus::Published, $this->faqEntity->getStatus());
        $this->assertFalse($this->faqEntity->isSticky());
        $this->assertEquals('keyword', $this->faqEntity->getKeywords());
        $this->assertEquals('Question?', $this->faqEntity->getQuestion());
        $this->assertEquals('Answer.', $this->faqEntity->getAnswer());
        $this->assertEquals('Author', $this->faqEntity->getAuthor());
        $this->assertEquals('author@example.com', $this->faqEntity->getEmail());
        $this->assertTrue($this->faqEntity->isComment());
        $this->assertEquals('Notes', $this->faqEntity->getNotes());
        $this->assertSame($publicationWindow, $this->faqEntity->getPublicationWindow());
        $this->assertSame($createdDate, $this->faqEntity->getCreatedDate());
        $this->assertSame($updatedDate, $this->faqEntity->getUpdatedDate());
    }

    /**
     * Test getJson returns valid JSON containing the entity values
     */
    public function testGetJsonReturnsValidJson(): void
    {
        $this->faqEntity->setId(5)->setLanguage('de')->setQuestion('Frage?')->setAnswer('Antwort.');

        $json = $this->faqEntity->getJson();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame(5, $decoded['id']);
        $this->assertSame('de', $decoded['language']);
        $this->assertSame('Frage?', $decoded['question']);
        $this->assertSame('Antwort.', $decoded['answer']);
        $this->assertSame('draft', $decoded['status']);
    }

    /**
     * Test zero values for integer fields
     */
    public function testZeroValues(): void
    {
        $this->faqEntity->setId(0)->setSolutionId(0)->setRevisionId(0);

        $this->assertSame(0, $this->faqEntity->getId());
        $this->assertSame(0, $this->faqEntity->getSolutionId());
        $this->assertSame(0, $this->faqEntity->getRevisionId());
    }

    /**
     * Test object state independence between instances
     */
    public function testObjectStateIndependence(): void
    {
        $first = new FaqEntity();
        $first->setId(1)->setQuestion('First');

        $second = new FaqEntity();
        $second->setId(2)->setQuestion('Second');

        $this->assertSame(1, $first->getId());
        $this->assertEquals('First', $first->getQuestion());
        $this->assertSame(2, $second->getId());
        $this->assertEquals('Second', $second->getQuestion());
    }
}
