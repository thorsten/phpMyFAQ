<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConfigRenameOperationTest extends TestCase
{
    private MockObject&Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetType(): void
    {
        $operation = new ConfigRenameOperation($this->configuration, 'old.key', 'new.key');

        $this->assertEquals('config_rename', $operation->getType());
    }

    public function testGetOldKey(): void
    {
        $operation = new ConfigRenameOperation($this->configuration, 'main.templateSet', 'layout.templateSet');

        $this->assertEquals('main.templateSet', $operation->getOldKey());
    }

    public function testGetNewKey(): void
    {
        $operation = new ConfigRenameOperation($this->configuration, 'main.templateSet', 'layout.templateSet');

        $this->assertEquals('layout.templateSet', $operation->getNewKey());
    }

    public function testGetDescription(): void
    {
        $operation = new ConfigRenameOperation($this->configuration, 'main.old', 'layout.new');

        $this->assertEquals('Rename configuration: main.old -> layout.new', $operation->getDescription());
    }

    public function testExecute(): void
    {
        $this->configuration
            ->expects($this->once())
            ->method('rename')
            ->with('old.key', 'new.key');

        $operation = new ConfigRenameOperation($this->configuration, 'old.key', 'new.key');
        $result = $operation->execute();

        $this->assertTrue($result);
    }

    public function testToArray(): void
    {
        $operation = new ConfigRenameOperation($this->configuration, 'main.old', 'layout.new');

        $expected = [
            'type' => 'config_rename',
            'description' => 'Rename configuration: main.old -> layout.new',
            'oldKey' => 'main.old',
            'newKey' => 'layout.new',
        ];

        $this->assertEquals($expected, $operation->toArray());
    }
}
