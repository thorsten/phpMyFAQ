<?php

namespace phpMyFAQ\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class CategoryEntityTest
 */
class CategoryEntityTest extends TestCase
{
    private CategoryEntity $categoryEntity;

    protected function setUp(): void
    {
        $this->categoryEntity = new CategoryEntity();
    }

    /**
     * Test CategoryEntity instantiation
     */
    public function testCategoryEntityInstantiation(): void
    {
        $this->assertInstanceOf(CategoryEntity::class, $this->categoryEntity);
    }

    /**
     * Test default group id value
     */
    public function testDefaultGroupId(): void
    {
        $this->assertEquals(-1, $this->categoryEntity->getGroupId());
    }

    /**
     * Test default image value
     */
    public function testDefaultImage(): void
    {
        $this->assertEquals('', $this->categoryEntity->getImage());
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setId(42);

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertSame(42, $this->categoryEntity->getId());
    }

    /**
     * Test lang getter and setter
     */
    public function testLangGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setLang('en');

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertEquals('en', $this->categoryEntity->getLang());
    }

    /**
     * Test parentId getter and setter
     */
    public function testParentIdGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setParentId(7);

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertSame(7, $this->categoryEntity->getParentId());
    }

    /**
     * Test name getter and setter
     */
    public function testNameGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setName('General');

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertEquals('General', $this->categoryEntity->getName());
    }

    /**
     * Test name defaults to empty string when not set
     */
    public function testNameDefaultsToEmptyString(): void
    {
        $this->assertEquals('', $this->categoryEntity->getName());
    }

    /**
     * Test description getter and setter
     */
    public function testDescriptionGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setDescription('A category description');

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertEquals('A category description', $this->categoryEntity->getDescription());
    }

    /**
     * Test description accepts null
     */
    public function testDescriptionAcceptsNull(): void
    {
        $this->categoryEntity->setDescription(null);
        $this->assertNull($this->categoryEntity->getDescription());
    }

    /**
     * Test userId getter and setter
     */
    public function testUserIdGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setUserId(99);

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertSame(99, $this->categoryEntity->getUserId());
    }

    /**
     * Test groupId getter and setter
     */
    public function testGroupIdGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setGroupId(5);

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertSame(5, $this->categoryEntity->getGroupId());
    }

    /**
     * Test active getter and setter
     */
    public function testActiveGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setActive(true);

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertTrue($this->categoryEntity->getActive());

        $this->categoryEntity->setActive(false);
        $this->assertFalse($this->categoryEntity->getActive());
    }

    /**
     * Test image getter and setter
     */
    public function testImageGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setImage('image.png');

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertEquals('image.png', $this->categoryEntity->getImage());
    }

    /**
     * Test image accepts null
     */
    public function testImageAcceptsNull(): void
    {
        $this->categoryEntity->setImage(null);
        $this->assertNull($this->categoryEntity->getImage());
    }

    /**
     * Test showHome getter and setter
     */
    public function testShowHomeGetterAndSetter(): void
    {
        $result = $this->categoryEntity->setShowHome(true);

        $this->assertInstanceOf(CategoryEntity::class, $result); // Test fluent interface
        $this->assertTrue($this->categoryEntity->getShowHome());

        $this->categoryEntity->setShowHome(false);
        $this->assertFalse($this->categoryEntity->getShowHome());
    }

    /**
     * Test showHome defaults to false when null
     */
    public function testShowHomeDefaultsToFalse(): void
    {
        $this->categoryEntity->setShowHome(null);
        $this->assertFalse($this->categoryEntity->getShowHome());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $result = $this->categoryEntity
            ->setId(1)
            ->setLang('de')
            ->setParentId(0)
            ->setName('Root')
            ->setDescription('Root category')
            ->setUserId(2)
            ->setGroupId(3)
            ->setActive(true)
            ->setShowHome(true)
            ->setImage('root.png');

        $this->assertInstanceOf(CategoryEntity::class, $result);
        $this->assertSame(1, $this->categoryEntity->getId());
        $this->assertEquals('de', $this->categoryEntity->getLang());
        $this->assertSame(0, $this->categoryEntity->getParentId());
        $this->assertEquals('Root', $this->categoryEntity->getName());
        $this->assertEquals('Root category', $this->categoryEntity->getDescription());
        $this->assertSame(2, $this->categoryEntity->getUserId());
        $this->assertSame(3, $this->categoryEntity->getGroupId());
        $this->assertTrue($this->categoryEntity->getActive());
        $this->assertTrue($this->categoryEntity->getShowHome());
        $this->assertEquals('root.png', $this->categoryEntity->getImage());
    }

    /**
     * Test zero values for integer fields
     */
    public function testZeroValues(): void
    {
        $this->categoryEntity->setId(0)->setParentId(0)->setUserId(0)->setGroupId(0);

        $this->assertSame(0, $this->categoryEntity->getId());
        $this->assertSame(0, $this->categoryEntity->getParentId());
        $this->assertSame(0, $this->categoryEntity->getUserId());
        $this->assertSame(0, $this->categoryEntity->getGroupId());
    }

    /**
     * Test object state independence between instances
     */
    public function testObjectStateIndependence(): void
    {
        $first = new CategoryEntity();
        $first->setId(1)->setName('First');

        $second = new CategoryEntity();
        $second->setId(2)->setName('Second');

        $this->assertSame(1, $first->getId());
        $this->assertEquals('First', $first->getName());
        $this->assertSame(2, $second->getId());
        $this->assertEquals('Second', $second->getName());
    }
}
