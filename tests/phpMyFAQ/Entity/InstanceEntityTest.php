<?php

namespace phpMyFAQ\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class InstanceEntityTest
 */
class InstanceEntityTest extends TestCase
{
    private InstanceEntity $instanceEntity;

    protected function setUp(): void
    {
        $this->instanceEntity = new InstanceEntity();
    }

    /**
     * Test InstanceEntity instantiation
     */
    public function testInstanceEntityInstantiation(): void
    {
        $this->assertInstanceOf(InstanceEntity::class, $this->instanceEntity);
    }

    /**
     * Test url getter and setter
     */
    public function testUrlGetterAndSetter(): void
    {
        $url = 'https://www.example.com';
        $result = $this->instanceEntity->setUrl($url);

        $this->assertInstanceOf(InstanceEntity::class, $result); // Test fluent interface
        $this->assertEquals($url, $this->instanceEntity->getUrl());
    }

    /**
     * Test instance getter and setter
     */
    public function testInstanceGetterAndSetter(): void
    {
        $instance = 'my-instance';
        $result = $this->instanceEntity->setInstance($instance);

        $this->assertInstanceOf(InstanceEntity::class, $result); // Test fluent interface
        $this->assertEquals($instance, $this->instanceEntity->getInstance());
    }

    /**
     * Test comment getter and setter
     */
    public function testCommentGetterAndSetter(): void
    {
        $comment = 'This is a comment.';
        $result = $this->instanceEntity->setComment($comment);

        $this->assertInstanceOf(InstanceEntity::class, $result); // Test fluent interface
        $this->assertEquals($comment, $this->instanceEntity->getComment());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $result = $this->instanceEntity
            ->setUrl('https://faq.example.org')
            ->setInstance('production')
            ->setComment('Primary instance');

        $this->assertInstanceOf(InstanceEntity::class, $result);
        $this->assertEquals('https://faq.example.org', $this->instanceEntity->getUrl());
        $this->assertEquals('production', $this->instanceEntity->getInstance());
        $this->assertEquals('Primary instance', $this->instanceEntity->getComment());
    }

    /**
     * Test empty string values
     */
    public function testEmptyStringValues(): void
    {
        $this->instanceEntity->setUrl('')->setInstance('')->setComment('');

        $this->assertEquals('', $this->instanceEntity->getUrl());
        $this->assertEquals('', $this->instanceEntity->getInstance());
        $this->assertEquals('', $this->instanceEntity->getComment());
    }

    /**
     * Test values containing special characters
     */
    public function testSpecialCharacterValues(): void
    {
        $url = 'https://example.com/path?query=1&value=äöü';
        $instance = 'instance-#1_ÄÖÜ';
        $comment = "Multi-line\ncomment with \"quotes\" & <tags>";

        $this->instanceEntity->setUrl($url)->setInstance($instance)->setComment($comment);

        $this->assertEquals($url, $this->instanceEntity->getUrl());
        $this->assertEquals($instance, $this->instanceEntity->getInstance());
        $this->assertEquals($comment, $this->instanceEntity->getComment());
    }

    /**
     * Test object state independence between instances
     */
    public function testObjectStateIndependence(): void
    {
        $first = new InstanceEntity();
        $first->setInstance('first');

        $second = new InstanceEntity();
        $second->setInstance('second');

        $this->assertEquals('first', $first->getInstance());
        $this->assertEquals('second', $second->getInstance());
    }
}
