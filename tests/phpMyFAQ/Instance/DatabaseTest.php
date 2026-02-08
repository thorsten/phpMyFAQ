<?php

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

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
        $driver = Database::factory($this->configuration, 'mysqli');
        $this->assertInstanceOf(\phpMyFAQ\Instance\Database\DriverInterface::class, $driver);
    }

    public function testFactoryWithInvalidType(): void
    {
        $this->expectException(Exception::class);
        Database::factory($this->configuration, 'InvalidType');
    }

    public function testGetInstance(): void
    {
        // After factory() has been called, getInstance() returns the last created driver
        Database::factory($this->configuration, 'mysqli');
        $instance = Database::getInstance();
        $this->assertInstanceOf(Database\DriverInterface::class, $instance);
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

    public function testCreateTenantDatabaseThrowsForInvalidDatabaseName(): void
    {
        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);
        $dbMock->expects($this->never())->method('query');

        $this->expectException(\InvalidArgumentException::class);

        Database::createTenantDatabase($this->configuration, 'pgsql', 'tenant-db');
    }

    public function testCreateTenantDatabaseCreatesPgsqlDatabaseWhenMissing(): void
    {
        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->method('escape')->willReturnArgument(0);
        $queryCall = 0;
        $dbMock
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$queryCall): mixed {
                if ($queryCall === 0) {
                    $this->assertStringContainsString('SELECT 1 FROM pg_database', $query);
                    $queryCall++;
                    return new \stdClass();
                }

                $this->assertStringContainsString('CREATE DATABASE "tenantdb"', $query);
                return true;
            });
        $dbMock->expects($this->once())->method('numRows')->willReturn(0);

        $result = Database::createTenantDatabase($this->configuration, 'pgsql', 'tenantdb');

        $this->assertTrue($result);
    }

    public function testCreateTenantDatabaseCreatesSqlServerDatabaseWhenMissing(): void
    {
        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->method('escape')->willReturnArgument(0);
        $dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("IF DB_ID('tenantdb') IS NULL CREATE DATABASE [tenantdb]"))
            ->willReturn(true);

        $result = Database::createTenantDatabase($this->configuration, 'sqlsrv', 'tenantdb');

        $this->assertTrue($result);
    }
}
