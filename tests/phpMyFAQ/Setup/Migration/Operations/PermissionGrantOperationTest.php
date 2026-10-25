<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class PermissionGrantOperationTest extends TestCase
{
    private Configuration $configuration;

    private ?Sqlite3 $dbHandle = null;

    private ?string $databasePath = null;

    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createMock(Configuration::class);
    }

    protected function tearDown(): void
    {
        if ($this->dbHandle instanceof Sqlite3) {
            $configurationReflection = new ReflectionClass(Configuration::class);
            $configurationReflection->getProperty('configuration')->setValue(null, $this->previousConfiguration);

            $this->dbHandle->close();

            $databaseReflection = new ReflectionClass(Database::class);
            $databaseReflection->getProperty('databaseDriver')->setValue(null, null);
            $databaseReflection->getProperty('dbType')->setValue(null, '');
        }

        if ($this->databasePath !== null && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
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

    public function testExecuteCreatesTheRightAndGrantsItToTheUser(): void
    {
        $configuration = $this->connectToDatabaseCopy();
        $this->dbHandle->query("DELETE FROM faqright WHERE name = 'faq_publish'");

        $operation = new PermissionGrantOperation($configuration, 'faq_publish', 'Right to publish FAQs');

        $this->assertTrue($operation->execute());

        $rightId = $this->rightId('faq_publish');
        $this->assertGreaterThan(0, $rightId);
        $result = $this->dbHandle->query(
            sprintf('SELECT COUNT(*) AS c FROM faquser_right WHERE user_id = 1 AND right_id = %d', $rightId),
        );
        $row = $this->dbHandle->fetchArray($result);
        $this->assertSame(1, (int) $row['c']);
    }

    public function testExecuteFailsForAnAlreadyExistingRight(): void
    {
        $configuration = $this->connectToDatabaseCopy();

        $operation = new PermissionGrantOperation($configuration, PermissionType::FAQS_VIEW->value, 'View FAQs');

        $this->assertFalse($operation->execute());
    }

    public function testExecuteFailsWhenTheDatabaseIsUnavailable(): void
    {
        $configuration = $this->connectToDatabaseCopy();
        $operation = new PermissionGrantOperation($configuration, 'faq_publish', 'Right to publish FAQs');

        $this->dbHandle->close();

        $this->assertFalse($operation->execute());
    }

    private function connectToDatabaseCopy(): Configuration
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-perm-grant-');
        $this->assertNotFalse($databasePath);
        $this->assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($databasePath, '', '');

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseReflection->getProperty('databaseDriver')->setValue(null, $this->dbHandle);
        $databaseReflection->getProperty('dbType')->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $configuration);

        return $configuration;
    }

    private function rightId(string $name): int
    {
        $result = $this->dbHandle->query(sprintf("SELECT right_id FROM faqright WHERE name = '%s'", $name));
        $row = $this->dbHandle->fetchArray($result);

        return is_array($row) ? (int) $row['right_id'] : 0;
    }
}
