<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class AbstractSetupTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;
    private string $configFilePath;
    private bool $createdConfigFile = false;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-setup-abstract-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);

        $configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $configuration);
        $configuration->set('main.maintenanceMode', 'true');
        $configuration->getAll();

        $this->configFilePath = PMF_ROOT_DIR . '/content/core/config/database.php';
        if (!is_file($this->configFilePath)) {
            file_put_contents($this->configFilePath, "<?php\nreturn [];\n");
            $this->createdConfigFile = true;
        }
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if ($this->createdConfigFile && is_file($this->configFilePath)) {
            unlink($this->configFilePath);
        }

        $this->dbHandle->close();
        if (is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testCheckMinimumPhpVersionReturnsTrue(): void
    {
        $setup = new class(new System()) extends AbstractSetup {};

        $this->assertTrue($setup->checkMinimumPhpVersion());
    }

    public function testCheckMinimumUpdateVersionRejectsTooOldVersion(): void
    {
        $setup = new class($this->createStub(System::class)) extends AbstractSetup {};

        $this->assertFalse($setup->checkMinimumUpdateVersion('3.1.0'));
        $this->assertTrue($setup->checkMinimumUpdateVersion('3.2.0'));
    }

    public function testCheckMaintenanceModeReadsConfigurationSingleton(): void
    {
        $setup = new class($this->createStub(System::class)) extends AbstractSetup {};

        $this->assertTrue($setup->checkMaintenanceMode());
    }

    public function testCheckPreUpgradeRejectsUnsupportedDatabase(): void
    {
        $system = $this->createStub(System::class);
        $system
            ->method('getSupportedDatabases')
            ->willReturn([
                'mysqli' => 'MySQL',
                'sqlite3' => 'SQLite',
            ]);

        $setup = new class($system) extends AbstractSetup {};

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sorry, but the database Unsupported is not supported!');

        $setup->checkPreUpgrade('unsupported');
    }

    public function testCheckPreUpgradeAcceptsSupportedDatabase(): void
    {
        $system = $this->createStub(System::class);
        $system
            ->method('getSupportedDatabases')
            ->willReturn([
                'mysqli' => 'MySQL',
                'sqlite3' => 'SQLite',
            ]);

        $setup = new class($system) extends AbstractSetup {};

        $setup->checkPreUpgrade('sqlite3');

        $this->addToAssertionCount(1);
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }
}
