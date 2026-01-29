<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConfigAddOperationTest extends TestCase
{
    private MockObject&Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetType(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'test.key', 'value');

        $this->assertEquals('config_add', $operation->getType());
    }

    public function testGetKey(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'security.enableFeature', true);

        $this->assertEquals('security.enableFeature', $operation->getKey());
    }

    public function testGetValueString(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'test.key', 'test value');

        $this->assertEquals('test value', $operation->getValue());
    }

    public function testGetValueBoolean(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'test.key', true);

        $this->assertTrue($operation->getValue());
    }

    public function testGetValueInteger(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'test.key', 42);

        $this->assertEquals(42, $operation->getValue());
    }

    public function testGetDescriptionWithString(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'main.language', 'en');

        $this->assertEquals("Add configuration: main.language = 'en'", $operation->getDescription());
    }

    public function testGetDescriptionWithBooleanTrue(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'feature.enabled', true);

        $this->assertEquals('Add configuration: feature.enabled = true', $operation->getDescription());
    }

    public function testGetDescriptionWithBooleanFalse(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'feature.enabled', false);

        $this->assertEquals('Add configuration: feature.enabled = false', $operation->getDescription());
    }

    public function testGetDescriptionWithLongString(): void
    {
        $longValue = str_repeat('a', 60);
        $operation = new ConfigAddOperation($this->configuration, 'test.key', $longValue);

        $description = $operation->getDescription();
        $this->assertStringContainsString('...', $description);
    }

    public function testGetDescriptionWithNull(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'test.key', null);

        $this->assertEquals('Add configuration: test.key = null', $operation->getDescription());
    }

    public function testExecute(): void
    {
        $this->configuration
            ->expects($this->once())
            ->method('add')
            ->with('test.key', 'value');

        $operation = new ConfigAddOperation($this->configuration, 'test.key', 'value');
        $result = $operation->execute();

        $this->assertTrue($result);
    }

    public function testToArray(): void
    {
        $operation = new ConfigAddOperation($this->configuration, 'main.language', 'en');

        $expected = [
            'type' => 'config_add',
            'description' => "Add configuration: main.language = 'en'",
            'key' => 'main.language',
            'value' => 'en',
        ];

        $this->assertEquals($expected, $operation->toArray());
    }
}
