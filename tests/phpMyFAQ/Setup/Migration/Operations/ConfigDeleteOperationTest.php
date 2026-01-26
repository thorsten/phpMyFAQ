<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ConfigDeleteOperationTest extends TestCase
{
    private MockObject&Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetType(): void
    {
        $operation = new ConfigDeleteOperation($this->configuration, 'test.key');

        $this->assertEquals('config_delete', $operation->getType());
    }

    public function testGetKey(): void
    {
        $operation = new ConfigDeleteOperation($this->configuration, 'main.deprecatedFeature');

        $this->assertEquals('main.deprecatedFeature', $operation->getKey());
    }

    public function testGetDescription(): void
    {
        $operation = new ConfigDeleteOperation($this->configuration, 'socialnetworks.twitterKey');

        $this->assertEquals('Delete configuration: socialnetworks.twitterKey', $operation->getDescription());
    }

    public function testExecute(): void
    {
        $this->configuration
            ->expects($this->once())
            ->method('delete')
            ->with('test.key');

        $operation = new ConfigDeleteOperation($this->configuration, 'test.key');
        $result = $operation->execute();

        $this->assertTrue($result);
    }

    public function testToArray(): void
    {
        $operation = new ConfigDeleteOperation($this->configuration, 'main.oldSetting');

        $expected = [
            'type' => 'config_delete',
            'description' => 'Delete configuration: main.oldSetting',
            'key' => 'main.oldSetting',
        ];

        $this->assertEquals($expected, $operation->toArray());
    }
}
