<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class OperationRecorderTest extends TestCase
{
    private MockObject&Configuration $configuration;
    private MockObject&Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $database = $this->createMock(DatabaseDriver::class);
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getDb')->willReturn($database);

        $this->filesystem = $this->createMock(Filesystem::class);
    }

    public function testAddSql(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addSql('SELECT 1');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(SqlOperation::class, $operations[0]);
    }

    public function testAddSqlWithDescription(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addSql('SELECT 1', 'Test query');

        $operations = $recorder->getOperations();

        $this->assertEquals('Test query', $operations[0]->getDescription());
    }

    public function testAddSqlForDbTypeRecordsOnlyForMatchingDatabaseType(): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $previousType = $dbTypeProperty->getValue();
        $dbTypeProperty->setValue(null, 'sqlite3');

        try {
            $recorder = new OperationRecorder($this->configuration);
            $result = $recorder
                ->addSqlForDbType('SELECT 1', 'sqlite3', 'sqlite only')
                ->addSqlForDbType('SELECT 2', ['mysqli', 'pgsql'])
                ->addSqlForDbType('SELECT 3', ['sqlsrv', 'sqlite3']);
        } finally {
            $dbTypeProperty->setValue(null, $previousType);
        }

        $this->assertSame($recorder, $result);
        $this->assertSame(['SELECT 1', 'SELECT 3'], $recorder->getSqlQueries());
        $this->assertEquals('sqlite only', $recorder->getOperations()[0]->getDescription());
    }

    public function testAddConfig(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addConfig('test.key', 'value');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(ConfigAddOperation::class, $operations[0]);
    }

    public function testDeleteConfig(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->deleteConfig('test.key');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(ConfigDeleteOperation::class, $operations[0]);
    }

    public function testRenameConfig(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->renameConfig('old.key', 'new.key');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(ConfigRenameOperation::class, $operations[0]);
    }

    public function testUpdateConfig(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->updateConfig('test.key', 'new value');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(ConfigUpdateOperation::class, $operations[0]);
    }

    public function testCopyFile(): void
    {
        $recorder = new OperationRecorder($this->configuration, $this->filesystem);
        $recorder->copyFile('/source/file.php', '/dest/file.php');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(FileCopyOperation::class, $operations[0]);
    }

    public function testCopyDirectory(): void
    {
        $recorder = new OperationRecorder($this->configuration, $this->filesystem);
        $recorder->copyDirectory('/source/dir', '/dest/dir');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(DirectoryCopyOperation::class, $operations[0]);
    }

    public function testGrantPermission(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->grantPermission('test_permission', 'Test permission description');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(PermissionGrantOperation::class, $operations[0]);
    }

    public function testRenamePermission(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->renamePermission('addfaq', 'add_faq');

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertInstanceOf(PermissionRenameOperation::class, $operations[0]);
        $this->assertSame('addfaq', $operations[0]->getOldName());
        $this->assertSame('add_faq', $operations[0]->getNewName());
    }

    public function testCreateUser(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $result = $recorder->createUser('migrator', 'secret', 'Migration User', 'migrator@example.com', 42, true);

        $operations = $recorder->getOperations();

        $this->assertSame($recorder, $result);
        $this->assertCount(1, $operations);
        $this->assertInstanceOf(UserCreateOperation::class, $operations[0]);
        $this->assertSame('Create user "migrator" (ID: 42)', $operations[0]->getDescription());
    }

    public function testInsertFormInput(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $result = $recorder->insertFormInput(['form_id' => 1, 'input_id' => 9, 'input_label' => 'label']);

        $operations = $recorder->getOperations();

        $this->assertSame($recorder, $result);
        $this->assertCount(1, $operations);
        $this->assertInstanceOf(FormInputInsertOperation::class, $operations[0]);
        $this->assertSame('form_input_insert', $operations[0]->getType());
    }

    public function testGetOperationsByType(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addSql('SELECT 1');
        $recorder->addSql('SELECT 2');
        $recorder->addConfig('test.key', 'value');

        $sqlOperations = $recorder->getOperationsByType('sql');

        $this->assertCount(2, $sqlOperations);
    }

    public function testGetSqlQueries(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addSql('SELECT 1');
        $recorder->addSql('SELECT 2');
        $recorder->addConfig('test.key', 'value');

        $queries = $recorder->getSqlQueries();

        $this->assertCount(2, $queries);
        $this->assertEquals('SELECT 1', $queries[0]);
        $this->assertEquals('SELECT 2', $queries[1]);
    }

    public function testGetOperationCounts(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addSql('SELECT 1');
        $recorder->addSql('SELECT 2');
        $recorder->addConfig('test.key', 'value');
        $recorder->deleteConfig('old.key');

        $counts = $recorder->getOperationCounts();

        $this->assertEquals(2, $counts['sql']);
        $this->assertEquals(1, $counts['config_add']);
        $this->assertEquals(1, $counts['config_delete']);
    }

    public function testToArray(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addSql('SELECT 1', 'Test query');
        $recorder->addConfig('test.key', 'value');

        $array = $recorder->toArray();

        $this->assertCount(2, $array);
        $this->assertEquals('sql', $array[0]['type']);
        $this->assertEquals('config_add', $array[1]['type']);
    }

    public function testClear(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addSql('SELECT 1');
        $recorder->addConfig('test.key', 'value');

        $recorder->clear();

        $this->assertCount(0, $recorder->getOperations());
    }

    public function testCount(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $recorder->addSql('SELECT 1');
        $recorder->addSql('SELECT 2');
        $recorder->addConfig('test.key', 'value');

        $this->assertEquals(3, $recorder->count());
    }

    public function testFluentInterface(): void
    {
        $recorder = new OperationRecorder($this->configuration);

        $result = $recorder->addSql('SELECT 1')->addConfig('test.key', 'value')->deleteConfig('old.key');

        $this->assertSame($recorder, $result);
        $this->assertEquals(3, $recorder->count());
    }

    public function testAddCustomOperation(): void
    {
        $customOp = $this->createMock(OperationInterface::class);
        $customOp->method('getType')->willReturn('custom');

        $recorder = new OperationRecorder($this->configuration);
        $recorder->addOperation($customOp);

        $operations = $recorder->getOperations();

        $this->assertCount(1, $operations);
        $this->assertSame($customOp, $operations[0]);
    }
}
