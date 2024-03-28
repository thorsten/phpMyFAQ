<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\InstanceEntity;
use PHPUnit\Framework\TestCase;

class InstanceTest extends TestCase
{
    private Instance $instance;
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $this->instance = new Instance($configuration);
    }

    public function testCreate(): void
    {
        $instance = new InstanceEntity();
        $instance
            ->setUrl('http://two.localhost')
            ->setInstance('Second localhost')
            ->setComment('Test instance');

        $this->assertEquals(2, $this->instance->create($instance));
        $this->instance->delete(2);
    }

    public function testGetAll(): void
    {
        $instances = $this->instance->getAll();
        $this->assertCount(1, $instances); // Only one instance is created by default

        $instance = new InstanceEntity();
        $instance
            ->setUrl('http://two.localhost')
            ->setInstance('Second localhost')
            ->setComment('Test instance');
        $this->instance->create($instance);

        $this->assertCount(2, $this->instance->getAll());
        $this->instance->delete(2);
    }

    public function testGetById(): void
    {
        $instance = new InstanceEntity();
        $instance
            ->setUrl('http://two.localhost')
            ->setInstance('Second localhost')
            ->setComment('Test instance');
        $id = $this->instance->create($instance);

        $instance = $this->instance->getById($id);
        $this->assertEquals('http://two.localhost', $instance->url);
        $this->assertEquals('Second localhost', $instance->instance);
        $this->assertEquals('Test instance', $instance->comment);

        $this->instance->delete($id);
    }

    public function testUpdate(): void
    {
        $instance = new InstanceEntity();
        $instance
            ->setUrl('http://two.localhost')
            ->setInstance('Second localhost')
            ->setComment('Test instance');
        $id = $this->instance->create($instance);

        $instance
            ->setUrl('http://three.localhost')
            ->setInstance('Third localhost')
            ->setComment('Test instance');
        $this->assertTrue($this->instance->update($id, $instance));

        $instance = $this->instance->getById($id);
        $this->assertEquals('http://three.localhost', $instance->url);
        $this->assertEquals('Third localhost', $instance->instance);
        $this->assertEquals('Test instance', $instance->comment);

        $this->instance->delete($id);
    }

    public function testAddConfig(): void
    {
        $instance = new InstanceEntity();
        $instance
            ->setUrl('http://two.localhost')
            ->setInstance('Second localhost')
            ->setComment('Test instance');
        $id = $this->instance->create($instance);

        $this->instance->addConfig('foo', 'bar');
        $this->assertEquals('bar', $this->instance->getConfig('foo'));

        $this->instance->delete($id);
    }

    public function testGetInstanceConfig(): void
    {
        $instance = new InstanceEntity();
        $instance
            ->setUrl('http://two.localhost')
            ->setInstance('Second localhost')
            ->setComment('Test instance');
        $id = $this->instance->create($instance);

        $this->instance->addConfig('foo', 'bar');
        $this->assertEquals(['foo' => 'bar'], $this->instance->getInstanceConfig($id));

        $this->instance->delete($id);
    }
}
