<?php

namespace phpMyFAQ\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class TagTest
 */
class TagTest extends TestCase
{
    private Tag $tag;

    protected function setUp(): void
    {
        $this->tag = new Tag();
    }

    /**
     * Test Tag entity instantiation
     */
    public function testTagInstantiation(): void
    {
        $this->assertInstanceOf(Tag::class, $this->tag);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->tag->setId($id);

        $this->assertInstanceOf(Tag::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->tag->getId());
    }

    /**
     * Test name getter and setter
     */
    public function testNameGetterAndSetter(): void
    {
        $name = 'php';
        $result = $this->tag->setName($name);

        $this->assertInstanceOf(Tag::class, $result); // Test fluent interface
        $this->assertEquals($name, $this->tag->getName());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $result = $this->tag->setId(7)->setName('faq');

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals(7, $this->tag->getId());
        $this->assertEquals('faq', $this->tag->getName());
    }

    /**
     * Test zero id value
     */
    public function testZeroId(): void
    {
        $this->tag->setId(0);
        $this->assertEquals(0, $this->tag->getId());
    }

    /**
     * Test negative id value
     */
    public function testNegativeId(): void
    {
        $this->tag->setId(-5);
        $this->assertEquals(-5, $this->tag->getId());
    }

    /**
     * Test id value boundaries
     */
    public function testIdValueBoundaries(): void
    {
        $this->tag->setId(PHP_INT_MIN);
        $this->assertEquals(PHP_INT_MIN, $this->tag->getId());

        $this->tag->setId(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $this->tag->getId());
    }

    /**
     * Test empty string name
     */
    public function testEmptyStringName(): void
    {
        $this->tag->setName('');
        $this->assertEquals('', $this->tag->getName());
    }

    /**
     * Test name with various values
     */
    public function testNameWithVariousValues(): void
    {
        $names = [
            'php',
            'PHP & MySQL',
            'tag-with-dashes',
            'tag with spaces',
            'Ümläüt',
            '日本語',
        ];

        foreach ($names as $name) {
            $this->tag->setName($name);
            $this->assertEquals($name, $this->tag->getName());
        }
    }

    /**
     * Test object state consistency and independence
     */
    public function testObjectStateConsistency(): void
    {
        $firstTag = new Tag();
        $firstTag->setId(1)->setName('first');

        $secondTag = new Tag();
        $secondTag->setId(2)->setName('second');

        $this->assertEquals(1, $firstTag->getId());
        $this->assertEquals('first', $firstTag->getName());
        $this->assertEquals(2, $secondTag->getId());
        $this->assertEquals('second', $secondTag->getName());
    }
}
