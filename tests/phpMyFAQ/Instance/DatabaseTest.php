<?php

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class DatabaseTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createStub(Configuration::class);
    }

    public function testFactoryWithValidType(): void
    {
        $type = 'Mysqli';
        $driverClass = '\phpMyFAQ\Instance\Database\\' . ucfirst($type);
        $this->assertTrue(class_exists($driverClass));

        $driver = Database::factory($this->configuration, $type);
        $this->assertInstanceOf($driverClass, $driver);
    }

    public function testFactoryWithInvalidType(): void
    {
        $this->expectException(Exception::class);
        Database::factory($this->configuration, 'InvalidType');
    }

    public function testGetInstance(): void
    {
        $instance = Database::getInstance();
        $this->assertInstanceOf(Database::class, $instance);
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testDropTables(): void
    {
        $dbMock = $this->createStub(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->method('query')->willReturn(true);

        $database = Database::factory($this->configuration, 'mysqli');
        $result = $database->dropTables('test_');

        $this->assertTrue($result);
    }

    public function testDropTablesWithFailure(): void
    {
        $dbMock = $this->createStub(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->method('query')->willReturn(false);

        $database = Database::factory($this->configuration, 'mysqli');
        $result = $database->dropTables('test_');

        $this->assertFalse($result);
    }
}
