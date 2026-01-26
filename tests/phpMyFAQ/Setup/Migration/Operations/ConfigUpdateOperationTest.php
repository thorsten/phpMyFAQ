<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConfigUpdateOperationTest extends TestCase
{
    private MockObject&Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetType(): void
    {
        $operation = new ConfigUpdateOperation($this->configuration, 'test.key', 'value');

        $this->assertEquals('config_update', $operation->getType());
    }

    public function testGetKey(): void
    {
        $operation = new ConfigUpdateOperation($this->configuration, 'main.botIgnoreList', 'bot,crawler');

        $this->assertEquals('main.botIgnoreList', $operation->getKey());
    }

    public function testGetValue(): void
    {
        $operation = new ConfigUpdateOperation($this->configuration, 'test.key', 'new value');

        $this->assertEquals('new value', $operation->getValue());
    }

    public function testGetDescriptionWithString(): void
    {
        $operation = new ConfigUpdateOperation($this->configuration, 'main.language', 'de');

        $this->assertEquals("Update configuration: main.language = 'de'", $operation->getDescription());
    }

    public function testGetDescriptionWithBoolean(): void
    {
        $operation = new ConfigUpdateOperation($this->configuration, 'feature.enabled', true);

        $this->assertEquals('Update configuration: feature.enabled = true', $operation->getDescription());
    }

    public function testGetDescriptionWithLongString(): void
    {
        $longValue = str_repeat('a', 60);
        $operation = new ConfigUpdateOperation($this->configuration, 'test.key', $longValue);

        $description = $operation->getDescription();
        $this->assertStringContainsString('...', $description);
    }

    public function testExecute(): void
    {
        $this->configuration
            ->expects($this->once())
            ->method('update')
            ->with(['test.key' => 'value']);

        $operation = new ConfigUpdateOperation($this->configuration, 'test.key', 'value');
        $result = $operation->execute();

        $this->assertTrue($result);
    }

    public function testToArray(): void
    {
        $operation = new ConfigUpdateOperation($this->configuration, 'main.version', '4.2.0');

        $expected = [
            'type' => 'config_update',
            'description' => "Update configuration: main.version = '4.2.0'",
            'key' => 'main.version',
            'value' => '4.2.0',
        ];

        $this->assertEquals($expected, $operation->toArray());
    }
}
