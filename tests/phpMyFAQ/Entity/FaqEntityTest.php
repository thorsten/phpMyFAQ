<?php

namespace phpMyFAQ\Entity;

use DateTime;
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
     * Test active getter and setter
     */
    public function testActiveGetterAndSetter(): void
    {
        $result = $this->faqEntity->setActive(true);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertTrue($this->faqEntity->isActive());

        $this->faqEntity->setActive(false);
        $this->assertFalse($this->faqEntity->isActive());
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
     * Test validFrom getter and setter
     */
    public function testValidFromGetterAndSetter(): void
    {
        $dateTime = new DateTime('2026-01-01 00:00:00');
        $result = $this->faqEntity->setValidFrom($dateTime);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame($dateTime, $this->faqEntity->getValidFrom());
    }

    /**
     * Test validFrom returns a DateTime even when not set
     */
    public function testValidFromDefaultsToNow(): void
    {
        $this->assertInstanceOf(DateTime::class, $this->faqEntity->getValidFrom());
    }

    /**
     * Test validTo getter and setter
     */
    public function testValidToGetterAndSetter(): void
    {
        $dateTime = new DateTime('2026-12-31 23:59:59');
        $result = $this->faqEntity->setValidTo($dateTime);

        $this->assertInstanceOf(FaqEntity::class, $result); // Test fluent interface
        $this->assertSame($dateTime, $this->faqEntity->getValidTo());
    }

    /**
     * Test validTo returns the far-future default when not set
     */
    public function testValidToDefaultsToFarFuture(): void
    {
        $validTo = $this->faqEntity->getValidTo();

        $this->assertInstanceOf(DateTime::class, $validTo);
        $this->assertEquals('9999', $validTo->format('Y'));
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
        $validFrom = new DateTime('2026-01-01 00:00:00');
        $validTo = new DateTime('2026-12-31 23:59:59');
        $createdDate = new DateTime('2026-06-28 12:00:00');
        $updatedDate = new DateTime('2026-06-28 13:00:00');

        $result = $this->faqEntity
            ->setId(1)
            ->setLanguage('en')
            ->setSolutionId(1000)
            ->setRevisionId(2)
            ->setActive(true)
            ->setSticky(false)
            ->setKeywords('keyword')
            ->setQuestion('Question?')
            ->setAnswer('Answer.')
            ->setAuthor('Author')
            ->setEmail('author@example.com')
            ->setComment(true)
            ->setNotes('Notes')
            ->setValidFrom($validFrom)
            ->setValidTo($validTo)
            ->setCreatedDate($createdDate)
            ->setUpdatedDate($updatedDate);

        $this->assertInstanceOf(FaqEntity::class, $result);
        $this->assertSame(1, $this->faqEntity->getId());
        $this->assertEquals('en', $this->faqEntity->getLanguage());
        $this->assertSame(1000, $this->faqEntity->getSolutionId());
        $this->assertSame(2, $this->faqEntity->getRevisionId());
        $this->assertTrue($this->faqEntity->isActive());
        $this->assertFalse($this->faqEntity->isSticky());
        $this->assertEquals('keyword', $this->faqEntity->getKeywords());
        $this->assertEquals('Question?', $this->faqEntity->getQuestion());
        $this->assertEquals('Answer.', $this->faqEntity->getAnswer());
        $this->assertEquals('Author', $this->faqEntity->getAuthor());
        $this->assertEquals('author@example.com', $this->faqEntity->getEmail());
        $this->assertTrue($this->faqEntity->isComment());
        $this->assertEquals('Notes', $this->faqEntity->getNotes());
        $this->assertSame($validFrom, $this->faqEntity->getValidFrom());
        $this->assertSame($validTo, $this->faqEntity->getValidTo());
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
