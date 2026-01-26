<?php

namespace phpMyFAQ\Setup\Migration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MigrationTrackerTest extends TestCase
{
    private Configuration $configuration;
    private DatabaseDriver $database;
    private MigrationTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseDriver::class);
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getDb')->willReturn($this->database);

        $this->tracker = new MigrationTracker($this->configuration);
    }

    public function testIsAppliedReturnsTrueWhenMigrationExists(): void
    {
        $resultMock = $this->createMock(\stdClass::class);
        $rowMock = new \stdClass();
        $rowMock->cnt = 1;

        $this->database->method('escape')->willReturnArgument(0);
        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('fetchObject')->willReturn($rowMock);

        $this->assertTrue($this->tracker->isApplied('4.0.0'));
    }

    public function testIsAppliedReturnsFalseWhenMigrationDoesNotExist(): void
    {
        $resultMock = $this->createMock(\stdClass::class);
        $rowMock = new \stdClass();
        $rowMock->cnt = 0;

        $this->database->method('escape')->willReturnArgument(0);
        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('fetchObject')->willReturn($rowMock);

        $this->assertFalse($this->tracker->isApplied('4.0.0'));
    }

    public function testRecordMigrationExecutesInsertQuery(): void
    {
        $this->database->method('escape')->willReturnArgument(0);

        $this->database
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(function ($query) {
                return (
                    str_contains($query, 'INSERT INTO')
                    && str_contains($query, '4.0.0')
                    && str_contains($query, 'faqmigrations')
                );
            }));

        $this->tracker->recordMigration('4.0.0', 100, 'abc123', 'Test migration');
    }

    public function testRecordMigrationWithNullChecksum(): void
    {
        $this->database->method('escape')->willReturnArgument(0);

        $this->database
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(function ($query) {
                return str_contains($query, 'NULL');
            }));

        $this->tracker->recordMigration('4.0.0', 100, null, null);
    }

    public function testRemoveMigrationExecutesDeleteQuery(): void
    {
        $this->database->method('escape')->willReturnArgument(0);

        $this->database
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(function ($query) {
                return str_contains($query, 'DELETE FROM') && str_contains($query, '4.0.0');
            }));

        $this->tracker->removeMigration('4.0.0');
    }

    public function testGetAppliedMigrationsReturnsEmptyArrayWhenNoMigrations(): void
    {
        $resultMock = $this->createMock(\stdClass::class);

        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('fetchObject')->willReturn(null);

        $migrations = $this->tracker->getAppliedMigrations();

        $this->assertIsArray($migrations);
        $this->assertEmpty($migrations);
    }

    public function testGetAppliedMigrationsReturnsMigrationData(): void
    {
        $resultMock = $this->createMock(\stdClass::class);

        $row1 = new \stdClass();
        $row1->version = '3.2.0-alpha';
        $row1->applied_at = '2024-01-01 00:00:00';
        $row1->execution_time_ms = '100';
        $row1->checksum = 'abc123';
        $row1->description = 'First migration';

        $row2 = new \stdClass();
        $row2->version = '3.2.0-beta';
        $row2->applied_at = '2024-01-02 00:00:00';
        $row2->execution_time_ms = '150';
        $row2->checksum = 'def456';
        $row2->description = 'Second migration';

        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('fetchObject')->willReturnOnConsecutiveCalls($row1, $row2, null);

        $migrations = $this->tracker->getAppliedMigrations();

        $this->assertCount(2, $migrations);
        $this->assertEquals('3.2.0-alpha', $migrations[0]['version']);
        $this->assertEquals('2024-01-01 00:00:00', $migrations[0]['applied_at']);
        $this->assertEquals(100, $migrations[0]['execution_time_ms']);
        $this->assertEquals('abc123', $migrations[0]['checksum']);
        $this->assertEquals('First migration', $migrations[0]['description']);
    }

    public function testGetAppliedVersionsReturnsVersionStrings(): void
    {
        $resultMock = $this->createMock(\stdClass::class);

        $row1 = new \stdClass();
        $row1->version = '3.2.0-alpha';
        $row1->applied_at = '2024-01-01';
        $row1->execution_time_ms = '100';
        $row1->checksum = null;
        $row1->description = null;

        $row2 = new \stdClass();
        $row2->version = '4.0.0';
        $row2->applied_at = '2024-01-02';
        $row2->execution_time_ms = '150';
        $row2->checksum = null;
        $row2->description = null;

        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('fetchObject')->willReturnOnConsecutiveCalls($row1, $row2, null);

        $versions = $this->tracker->getAppliedVersions();

        $this->assertEquals(['3.2.0-alpha', '4.0.0'], $versions);
    }

    public function testGetLastAppliedVersionReturnsNullWhenEmpty(): void
    {
        $resultMock = $this->createMock(\stdClass::class);

        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('fetchObject')->willReturn(null);

        $version = $this->tracker->getLastAppliedVersion();

        $this->assertNull($version);
    }

    public function testGetLastAppliedVersionReturnsVersion(): void
    {
        $resultMock = $this->createMock(\stdClass::class);
        $rowMock = new \stdClass();
        $rowMock->version = '4.2.0-alpha';

        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('fetchObject')->willReturn($rowMock);

        $version = $this->tracker->getLastAppliedVersion();

        $this->assertEquals('4.2.0-alpha', $version);
    }

    public function testTableExistsReturnsTrueWhenTableExists(): void
    {
        $resultMock = $this->createMock(\stdClass::class);

        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('numRows')->willReturn(1);

        $this->assertTrue($this->tracker->tableExists());
    }

    public function testTableExistsReturnsFalseWhenTableDoesNotExist(): void
    {
        $resultMock = $this->createMock(\stdClass::class);

        $this->database->method('query')->willReturn($resultMock);
        $this->database->method('numRows')->willReturn(0);

        $this->assertFalse($this->tracker->tableExists());
    }
}
