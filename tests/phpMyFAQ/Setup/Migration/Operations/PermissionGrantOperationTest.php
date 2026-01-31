<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PermissionGrantOperationTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetType(): void
    {
        $operation = new PermissionGrantOperation($this->configuration, 'add_faq', 'Add new FAQ entries');

        $this->assertEquals('permission_grant', $operation->getType());
    }

    public function testGetDescription(): void
    {
        $operation = new PermissionGrantOperation($this->configuration, 'add_faq', 'Add new FAQ entries');

        $description = $operation->getDescription();
        $this->assertStringContainsString('Add permission:', $description);
        $this->assertStringContainsString('add_faq', $description);
        $this->assertStringContainsString('Add new FAQ entries', $description);
    }

    public function testGetPermissionName(): void
    {
        $operation = new PermissionGrantOperation($this->configuration, 'edit_faq', 'Edit FAQ entries');

        $this->assertEquals('edit_faq', $operation->getPermissionName());
    }

    public function testGetPermissionDescription(): void
    {
        $operation = new PermissionGrantOperation($this->configuration, 'delete_faq', 'Delete FAQ entries');

        $this->assertEquals('Delete FAQ entries', $operation->getPermissionDescription());
    }

    public function testGetUserIdDefault(): void
    {
        $operation = new PermissionGrantOperation($this->configuration, 'add_faq', 'Add new FAQ entries');

        $this->assertEquals(1, $operation->getUserId());
    }

    public function testGetUserIdCustom(): void
    {
        $operation = new PermissionGrantOperation($this->configuration, 'add_faq', 'Add new FAQ entries', 42);

        $this->assertEquals(42, $operation->getUserId());
    }

    public function testToArray(): void
    {
        $operation = new PermissionGrantOperation($this->configuration, 'manage_users', 'Manage user accounts', 5);

        $array = $operation->toArray();

        $this->assertEquals('permission_grant', $array['type']);
        $this->assertArrayHasKey('description', $array);
        $this->assertEquals('manage_users', $array['permissionName']);
        $this->assertEquals('Manage user accounts', $array['permissionDescription']);
        $this->assertEquals(5, $array['userId']);
    }
}
