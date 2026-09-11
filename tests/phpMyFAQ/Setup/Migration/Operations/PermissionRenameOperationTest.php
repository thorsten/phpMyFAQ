<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PermissionRenameOperationTest extends TestCase
{
    private Sqlite3 $dbHandle;

    private Configuration $configuration;

    private string $databasePath;

    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-perm-rename-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseReflection->getProperty('databaseDriver')->setValue(null, $this->dbHandle);
        $databaseReflection->getProperty('dbType')->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $this->configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $this->configuration);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationReflection->getProperty('configuration')->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseReflection->getProperty('databaseDriver')->setValue(null, null);
        $databaseReflection->getProperty('dbType')->setValue(null, '');

        if (is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testGetTypeAndDescriptionDescribeTheRename(): void
    {
        $operation = new PermissionRenameOperation($this->configuration, 'addfaq', 'add_faq');

        self::assertSame('permission_rename', $operation->getType());
        self::assertSame('addfaq', $operation->getOldName());
        self::assertSame('add_faq', $operation->getNewName());
        self::assertSame('Rename permission: addfaq to add_faq', $operation->getDescription());
    }

    public function testToArrayCarriesBothNames(): void
    {
        $operation = new PermissionRenameOperation($this->configuration, 'addfaq', 'add_faq');

        self::assertSame(
            [
                'type' => 'permission_rename',
                'description' => 'Rename permission: addfaq to add_faq',
                'oldName' => 'addfaq',
                'newName' => 'add_faq',
            ],
            $operation->toArray(),
        );
    }

    /**
     * PermissionType::FAQ_ADD was renamed from 'addfaq' to 'add_faq' in v4.0.15 without a
     * database migration, so upgraded installations kept the legacy row name and silently
     * lost the "Add new FAQ" entry in the admin menu.
     */
    public function testRenamesLegacyRight(): void
    {
        $rightId = $this->rightId(PermissionType::FAQ_ADD->value);
        self::assertGreaterThan(0, $rightId);

        // Simulate a database that was installed before v4.0.15
        $this->dbHandle->query("UPDATE faqright SET name = 'addfaq' WHERE name = 'add_faq'");
        self::assertSame(0, $this->rightId(PermissionType::FAQ_ADD->value));

        $operation = new PermissionRenameOperation($this->configuration, 'addfaq', PermissionType::FAQ_ADD->value);

        self::assertTrue($operation->execute());
        self::assertSame(0, $this->rightId('addfaq'));
        self::assertSame($rightId, $this->rightId(PermissionType::FAQ_ADD->value));
    }

    public function testKeepsAlreadyMigratedRightUntouched(): void
    {
        $rightId = $this->rightId(PermissionType::FAQ_ADD->value);
        self::assertGreaterThan(0, $rightId);

        $operation = new PermissionRenameOperation($this->configuration, 'addfaq', PermissionType::FAQ_ADD->value);

        self::assertTrue($operation->execute());
        self::assertSame($rightId, $this->rightId(PermissionType::FAQ_ADD->value));
        self::assertSame(0, $this->rightId('addfaq'));
    }

    public function testSucceedsWhenNeitherNameExists(): void
    {
        $operation = new PermissionRenameOperation($this->configuration, 'does_not_exist', 'still_does_not_exist');

        self::assertTrue($operation->execute());
        self::assertSame(0, $this->rightId('does_not_exist'));
        self::assertSame(0, $this->rightId('still_does_not_exist'));
    }

    private function rightId(string $name): int
    {
        $result = $this->dbHandle->query(sprintf("SELECT right_id FROM faqright WHERE name = '%s'", $name));
        $row = $this->dbHandle->fetchArray($result);

        return is_array($row) ? (int) $row['right_id'] : 0;
    }
}
