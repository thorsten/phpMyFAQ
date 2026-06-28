<?php

namespace phpMyFAQ\Entity;

use DateTime;
use phpMyFAQ\Enums\SeoType;
use PHPUnit\Framework\TestCase;

/**
 * Class SeoEntityTest
 */
class SeoEntityTest extends TestCase
{
    private SeoEntity $seoEntity;

    protected function setUp(): void
    {
        $this->seoEntity = new SeoEntity();
    }

    /**
     * Test SeoEntity instantiation
     */
    public function testSeoEntityInstantiation(): void
    {
        $this->assertInstanceOf(SeoEntity::class, $this->seoEntity);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->seoEntity->setId($id);

        $this->assertInstanceOf(SeoEntity::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->seoEntity->getId());
    }

    /**
     * Test seoType getter and setter
     */
    public function testSeoTypeGetterAndSetter(): void
    {
        $seoType = SeoType::FAQ;
        $result = $this->seoEntity->setSeoType($seoType);

        $this->assertInstanceOf(SeoEntity::class, $result); // Test fluent interface
        $this->assertSame($seoType, $this->seoEntity->getSeoType());
    }

    /**
     * Test referenceId getter and setter
     */
    public function testReferenceIdGetterAndSetter(): void
    {
        $referenceId = 456;
        $result = $this->seoEntity->setReferenceId($referenceId);

        $this->assertInstanceOf(SeoEntity::class, $result); // Test fluent interface
        $this->assertEquals($referenceId, $this->seoEntity->getReferenceId());
    }

    /**
     * Test referenceLanguage getter and setter
     */
    public function testReferenceLanguageGetterAndSetter(): void
    {
        $referenceLanguage = 'en';
        $result = $this->seoEntity->setReferenceLanguage($referenceLanguage);

        $this->assertInstanceOf(SeoEntity::class, $result); // Test fluent interface
        $this->assertEquals($referenceLanguage, $this->seoEntity->getReferenceLanguage());
    }

    /**
     * Test title getter and setter
     */
    public function testTitleGetterAndSetter(): void
    {
        $title = 'My SEO Title';
        $result = $this->seoEntity->setTitle($title);

        $this->assertInstanceOf(SeoEntity::class, $result); // Test fluent interface
        $this->assertEquals($title, $this->seoEntity->getTitle());
    }

    /**
     * Test description getter and setter
     */
    public function testDescriptionGetterAndSetter(): void
    {
        $description = 'My SEO description text.';
        $result = $this->seoEntity->setDescription($description);

        $this->assertInstanceOf(SeoEntity::class, $result); // Test fluent interface
        $this->assertEquals($description, $this->seoEntity->getDescription());
    }

    /**
     * Test slug getter and setter
     */
    public function testSlugGetterAndSetter(): void
    {
        $slug = 'my-seo-slug';
        $result = $this->seoEntity->setSlug($slug);

        $this->assertInstanceOf(SeoEntity::class, $result); // Test fluent interface
        $this->assertEquals($slug, $this->seoEntity->getSlug());
    }

    /**
     * Test created getter and setter
     */
    public function testCreatedGetterAndSetter(): void
    {
        $created = new DateTime('2026-01-15 10:00:00');
        $result = $this->seoEntity->setCreated($created);

        $this->assertInstanceOf(SeoEntity::class, $result); // Test fluent interface
        $this->assertSame($created, $this->seoEntity->getCreated());
        $this->assertInstanceOf(DateTime::class, $this->seoEntity->getCreated());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $created = new DateTime();

        $result = $this->seoEntity
            ->setId(1)
            ->setSeoType(SeoType::CATEGORY)
            ->setReferenceId(100)
            ->setReferenceLanguage('de')
            ->setTitle('Title')
            ->setDescription('Description')
            ->setSlug('slug')
            ->setCreated($created);

        $this->assertInstanceOf(SeoEntity::class, $result);
        $this->assertEquals(1, $this->seoEntity->getId());
        $this->assertSame(SeoType::CATEGORY, $this->seoEntity->getSeoType());
        $this->assertEquals(100, $this->seoEntity->getReferenceId());
        $this->assertEquals('de', $this->seoEntity->getReferenceLanguage());
        $this->assertEquals('Title', $this->seoEntity->getTitle());
        $this->assertEquals('Description', $this->seoEntity->getDescription());
        $this->assertEquals('slug', $this->seoEntity->getSlug());
        $this->assertSame($created, $this->seoEntity->getCreated());
    }

    /**
     * Test all SeoType enum cases
     */
    public function testAllSeoTypes(): void
    {
        foreach (SeoType::cases() as $seoType) {
            $this->seoEntity->setSeoType($seoType);
            $this->assertSame($seoType, $this->seoEntity->getSeoType());
        }
    }

    /**
     * Test zero and negative reference id values
     */
    public function testReferenceIdEdgeCases(): void
    {
        $this->seoEntity->setReferenceId(0);
        $this->assertEquals(0, $this->seoEntity->getReferenceId());

        $this->seoEntity->setReferenceId(-1);
        $this->assertEquals(-1, $this->seoEntity->getReferenceId());

        $this->seoEntity->setReferenceId(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $this->seoEntity->getReferenceId());
    }

    /**
     * Test empty string values for nullable string properties
     */
    public function testEmptyStringValues(): void
    {
        $this->seoEntity->setReferenceLanguage('');
        $this->seoEntity->setTitle('');
        $this->seoEntity->setDescription('');
        $this->seoEntity->setSlug('');

        $this->assertEquals('', $this->seoEntity->getReferenceLanguage());
        $this->assertEquals('', $this->seoEntity->getTitle());
        $this->assertEquals('', $this->seoEntity->getDescription());
        $this->assertEquals('', $this->seoEntity->getSlug());
    }

    /**
     * Test that nullable getters return null when not set
     */
    public function testNullableGettersReturnNullByDefault(): void
    {
        $entity = new SeoEntity();

        $this->assertNull($entity->getId());
        $this->assertNull($entity->getTitle());
        $this->assertNull($entity->getDescription());
        $this->assertNull($entity->getSlug());
    }

    /**
     * Test created with different DateTime objects
     */
    public function testCreatedWithDifferentDateTimes(): void
    {
        $dates = [
            new DateTime('2026-01-01 00:00:00'),
            new DateTime('2026-12-31 23:59:59'),
            new DateTime(),
            new DateTime('1970-01-01 00:00:00'),
        ];

        foreach ($dates as $date) {
            $this->seoEntity->setCreated($date);
            $this->assertSame($date, $this->seoEntity->getCreated());
        }
    }
}
