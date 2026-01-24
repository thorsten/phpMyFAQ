<?php

namespace phpMyFAQ\Administration\Backup;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class BackupExecuteResultTest
 *
 * @package phpMyFAQ\Administration\Backup
 */
#[AllowMockObjectsWithoutExpectations]
class BackupExecuteResultTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $result = new BackupExecuteResult(
            queriesOk: 10,
            queriesFailed: 2,
            lastErrorQuery: 'SELECT * FROM invalid_table',
            lastErrorReason: 'Table does not exist',
        );

        $this->assertEquals(10, $result->queriesOk);
        $this->assertEquals(2, $result->queriesFailed);
        $this->assertEquals('SELECT * FROM invalid_table', $result->lastErrorQuery);
        $this->assertEquals('Table does not exist', $result->lastErrorReason);
    }

    public function testConstructorWithOnlyRequiredParameters(): void
    {
        $result = new BackupExecuteResult(queriesOk: 15, queriesFailed: 0);

        $this->assertEquals(15, $result->queriesOk);
        $this->assertEquals(0, $result->queriesFailed);
        $this->assertNull($result->lastErrorQuery);
        $this->assertNull($result->lastErrorReason);
    }

    public function testConstructorWithZeroQueries(): void
    {
        $result = new BackupExecuteResult(queriesOk: 0, queriesFailed: 0);

        $this->assertEquals(0, $result->queriesOk);
        $this->assertEquals(0, $result->queriesFailed);
    }

    public function testConstructorWithOnlyFailedQueries(): void
    {
        $result = new BackupExecuteResult(
            queriesOk: 0,
            queriesFailed: 5,
            lastErrorQuery: 'INSERT INTO locked_table VALUES (1)',
            lastErrorReason: 'Table is locked',
        );

        $this->assertEquals(0, $result->queriesOk);
        $this->assertEquals(5, $result->queriesFailed);
        $this->assertEquals('INSERT INTO locked_table VALUES (1)', $result->lastErrorQuery);
        $this->assertEquals('Table is locked', $result->lastErrorReason);
    }

    public function testConstructorWithOnlySuccessfulQueries(): void
    {
        $result = new BackupExecuteResult(
            queriesOk: 100,
            queriesFailed: 0,
            lastErrorQuery: null,
            lastErrorReason: null,
        );

        $this->assertEquals(100, $result->queriesOk);
        $this->assertEquals(0, $result->queriesFailed);
        $this->assertNull($result->lastErrorQuery);
        $this->assertNull($result->lastErrorReason);
    }

    public function testReadonlyProperties(): void
    {
        $result = new BackupExecuteResult(queriesOk: 5, queriesFailed: 1);

        // Verify properties are accessible
        $this->assertIsInt($result->queriesOk);
        $this->assertIsInt($result->queriesFailed);
    }

    public function testConstructorWithLargeNumbers(): void
    {
        $result = new BackupExecuteResult(queriesOk: 99999, queriesFailed: 1);

        $this->assertEquals(99999, $result->queriesOk);
        $this->assertEquals(1, $result->queriesFailed);
    }

    public function testConstructorWithErrorDetailsButNoErrors(): void
    {
        $result = new BackupExecuteResult(queriesOk: 10, queriesFailed: 0, lastErrorQuery: null, lastErrorReason: null);

        $this->assertEquals(10, $result->queriesOk);
        $this->assertEquals(0, $result->queriesFailed);
        $this->assertNull($result->lastErrorQuery);
        $this->assertNull($result->lastErrorReason);
    }

    public function testConstructorWithComplexErrorMessage(): void
    {
        $complexError = 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'db.table\' doesn\'t exist';

        $result = new BackupExecuteResult(
            queriesOk: 5,
            queriesFailed: 1,
            lastErrorQuery: 'SELECT * FROM missing_table',
            lastErrorReason: $complexError,
        );

        $this->assertEquals($complexError, $result->lastErrorReason);
    }
}
