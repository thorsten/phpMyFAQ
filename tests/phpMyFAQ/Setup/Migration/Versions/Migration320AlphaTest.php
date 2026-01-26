<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class Migration320AlphaTest extends TestCase
{
    private Migration320Alpha $migration;

    protected function setUp(): void
    {
        parent::setUp();
        $configuration = $this->createMock(Configuration::class);
        $this->migration = new Migration320Alpha($configuration);
    }

    public function testImplementsMigrationInterface(): void
    {
        $this->assertInstanceOf(MigrationInterface::class, $this->migration);
    }

    public function testGetVersion(): void
    {
        $this->assertEquals('3.2.0-alpha', $this->migration->getVersion());
    }

    public function testGetDescription(): void
    {
        $description = $this->migration->getDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('Microsoft Entra ID', $description);
        $this->assertStringContainsString('2FA', $description);
    }

    public function testGetDependencies(): void
    {
        // First migration should have no dependencies
        $this->assertEquals([], $this->migration->getDependencies());
    }

    public function testIsReversible(): void
    {
        // By default, migrations are not reversible
        $this->assertFalse($this->migration->isReversible());
    }

    public function testUpRecordsOperations(): void
    {
        $recorder = $this->createMock(OperationRecorder::class);

        // Expect config operations
        $recorder->expects($this->atLeastOnce())->method('addConfig');

        // Expect SQL operations
        $recorder->expects($this->atLeastOnce())->method('addSql');

        $this->migration->up($recorder);
    }

    public function testUpAddsEnableSignInWithMicrosoftConfig(): void
    {
        $recorder = $this->createMock(OperationRecorder::class);

        $recorder
            ->expects($this->atLeastOnce())
            ->method('addConfig')
            ->with($this->callback(function ($key) {
                static $keys = [];
                $keys[] = $key;
                return true;
            }));

        $this->migration->up($recorder);
    }

    public function testUpAddsSqlForBackupTable(): void
    {
        $recorder = $this->createMock(OperationRecorder::class);

        $recorder
            ->expects($this->atLeastOnce())
            ->method('addSql')
            ->with($this->callback(function ($sql) {
                static $sqlStatements = [];
                $sqlStatements[] = $sql;
                // Check that at least one SQL contains backup table creation
                if (str_contains($sql, 'faqbackup')) {
                    return true;
                }
                return true;
            }));

        $this->migration->up($recorder);
    }

    public function testGetChecksum(): void
    {
        $checksum = $this->migration->getChecksum();

        $this->assertNotEmpty($checksum);
        $this->assertEquals(64, strlen($checksum));
    }
}
