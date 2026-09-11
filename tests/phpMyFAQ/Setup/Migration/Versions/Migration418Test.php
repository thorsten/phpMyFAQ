<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use phpMyFAQ\Setup\Migration\Operations\PermissionRenameOperation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class Migration418Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new ReflectionClass(Database::class);
        $reflection->getProperty('dbType')->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }

    public function testMetadata(): void
    {
        $migration = new Migration418($this->createStub(Configuration::class));

        $this->assertSame('4.1.8', $migration->getVersion());
        $this->assertSame(['4.1.3'], $migration->getDependencies());
        $this->assertStringContainsString('add_faq', $migration->getDescription());
    }

    /**
     * PermissionType::FAQ_ADD was renamed from 'addfaq' to 'add_faq' in v4.0.15 without a
     * matching migration; the rename operation restores the right on upgraded installations
     * and is a no-op where the new name already exists.
     */
    public function testRecordsTheLegacyRightRename(): void
    {
        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        new Migration418($this->createStub(Configuration::class))->up($recorder);

        $operations = $recorder->getOperations();
        $this->assertCount(1, $operations);
        $this->assertSame(['permission_rename' => 1], $recorder->getOperationCounts());

        $operation = $operations[0];
        $this->assertInstanceOf(PermissionRenameOperation::class, $operation);
        $this->assertSame('addfaq', $operation->getOldName());
        $this->assertSame(PermissionType::FAQ_ADD->value, $operation->getNewName());
        $this->assertSame([], $recorder->getSqlQueries());
    }
}
