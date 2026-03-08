<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\InstanceEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class InstanceTest extends TestCase
{
    private Instance $instance;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-instance-test-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $configuration = new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $this->instance = new Instance($configuration);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    public function testCreate(): void
    {
        $initialCount = count($this->instance->getAll());

        $instance = new InstanceEntity();
        $instance->setUrl('http://two.localhost')->setInstance('Second localhost')->setComment('Test instance');

        $id = $this->instance->create($instance);

        $this->assertGreaterThan($initialCount, $id);
        $this->instance->delete($id);
    }

    public function testGetAll(): void
    {
        $instances = $this->instance->getAll();
        $initialCount = count($instances);
        $this->assertGreaterThanOrEqual(1, $initialCount);

        $instance = new InstanceEntity();
        $instance->setUrl('http://two.localhost')->setInstance('Second localhost')->setComment('Test instance');
        $id = $this->instance->create($instance);

        $this->assertCount($initialCount + 1, $this->instance->getAll());
        $this->instance->delete($id);
    }

    public function testGetById(): void
    {
        $instance = new InstanceEntity();
        $instance->setUrl('http://two.localhost')->setInstance('Second localhost')->setComment('Test instance');
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
        $instance->setUrl('http://two.localhost')->setInstance('Second localhost')->setComment('Test instance');
        $id = $this->instance->create($instance);

        $instance->setUrl('http://three.localhost')->setInstance('Third localhost')->setComment('Test instance');
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
        $instance->setUrl('http://two.localhost')->setInstance('Second localhost')->setComment('Test instance');
        $id = $this->instance->create($instance);

        $this->instance->addConfig('foo', 'bar');
        $this->assertEquals('bar', $this->instance->getConfig('foo'));

        $this->instance->delete($id);
    }

    public function testGetInstanceConfig(): void
    {
        $instance = new InstanceEntity();
        $instance->setUrl('http://two.localhost')->setInstance('Second localhost')->setComment('Test instance');
        $id = $this->instance->create($instance);

        $this->instance->addConfig('foo', 'bar');
        $this->assertEquals(['foo' => 'bar'], $this->instance->getInstanceConfig($id));

        $this->instance->delete($id);
    }
}
