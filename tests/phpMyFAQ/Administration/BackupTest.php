<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Administration\Backup\BackupExportResult;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use SodiumException;
use stdClass;

/**
 * Class BackupTest
 *
 * @package phpMyFAQ
 */
#[AllowMockObjectsWithoutExpectations]
class BackupTest extends TestCase
{
    /** @var Configuration */
    protected Configuration $configuration;

    /** @var DatabaseHelper */
    protected DatabaseHelper $databaseHelper;

    /** @var Backup */
    protected Backup $backup;

    private Configuration $mockConfiguration;
    private DatabaseHelper $mockDatabaseHelper;
    private DatabaseDriver $mockDb;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock Configuration class
        $this->mockConfiguration = $this->createStub(Configuration::class);

        // Mock DatabaseHelper class
        $this->mockDatabaseHelper = $this->createMock(DatabaseHelper::class);

        // Mock Database class
        $this->mockDb = $this->createStub(DatabaseDriver::class);

        // Setup Configuration to return mocked database
        $this->mockConfiguration->method('getDb')->willReturn($this->mockDb);
        $this->mockConfiguration->method('getVersion')->willReturn('4.0.0');

        // Create Backup instance
        $this->backup = new Backup($this->mockConfiguration, $this->mockDatabaseHelper);
    }

    /**
     * @throws SodiumException
     */
    public function testCreateBackup(): void
    {
        $tableNames = 'faqconfig faqinstances';
        $backupQueries = $this->backup->generateBackupQueries($tableNames);
        $dataBackup = $this->backup->createBackup(BackupType::BACKUP_TYPE_DATA->value, $backupQueries);
        $expected = 'phpmyfaq-data.' . date(format: 'Y-m-d-H-i-s') . '.sql';

        $this->assertEquals($expected, $dataBackup);

        $tableNames = 'faqadminlog faqsessions';
        $backupQueries = $this->backup->generateBackupQueries($tableNames);
        $logsBackup = $this->backup->createBackup(BackupType::BACKUP_TYPE_LOGS->value, $backupQueries);
        $expected = 'phpmyfaq-logs.' . date(format: 'Y-m-d-H-i-s') . '.sql';

        $this->assertEquals($expected, $logsBackup);
    }

    /**
     * @throws SodiumException
     */
    public function testVerifyBackup(): void
    {
        // Use the mocked backup instance instead of the original one
        $tableNames = 'faqconfig faqinstances';

        // Mock the generateBackupQueries method
        $this->mockDatabaseHelper
            ->method('buildInsertQueries')
            ->willReturn(['INSERT INTO faqconfig VALUES (1, "test");']);
        $backupQueries = $this->backup->generateBackupQueries($tableNames);

        // Mock createBackup method
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $dataBackup = $this->backup->createBackup(BackupType::BACKUP_TYPE_DATA->value, $backupQueries);

        // Create a mock database result for verification
        $mockResult = new stdClass();
        $mockResult->id = 1;
        $mockResult->filename = $dataBackup;
        $mockResult->authkey = sodium_bin2hex(sodium_crypto_auth_keygen());

        // Generate valid auth code for the backup content
        $authKey = sodium_hex2bin($mockResult->authkey);
        $mockResult->authcode = sodium_bin2hex(sodium_crypto_auth($backupQueries, $authKey));

        // Mock the database calls for verification
        $this->mockDb->method('numRows')->willReturn(1);
        $this->mockDb->method('fetchObject')->willReturn($mockResult);

        $result = $this->backup->verifyBackup($backupQueries, $dataBackup);

        $this->assertTrue($result);
    }

    public function testGenerateBackupQueries(): void
    {
        $tableNames = 'faqconfig faqinstances';
        $queries = $this->backup->generateBackupQueries($tableNames);

        $this->assertStringContainsString('DO NOT REMOVE THE FIRST LINE!', $queries);
    }

    /**
     * @throws SodiumException
     */ public function testCreateBackupWithTablePrefix(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->backup->createBackup('data', 'test backup content');

        $this->assertStringStartsWith('phpmyfaq-data.', $result);
        $this->assertStringEndsWith('.sql', $result);
    }

    /**
     * @throws SodiumException
     */
    public function testCreateBackupWithoutTablePrefix(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->backup->createBackup('logs', 'test backup content');

        $this->assertStringStartsWith('phpmyfaq-logs.', $result);
        $this->assertStringEndsWith('.sql', $result);
    }

    /**
     * @throws SodiumException
     */ public function testCreateBackupEscapesInput(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb
            ->method('escape')
            ->willReturnCallback(function ($input) {
                return str_replace("'", "\\'", $input);
            });
        $this->mockDb->method('query')->willReturn(true);

        $maliciousContent = "'; DROP TABLE users; --";
        $result = $this->backup->createBackup('data', $maliciousContent);

        $this->assertIsString($result);
    }

    /**
     * @throws SodiumException
     */ public function testVerifyBackupWithNonExistentFile(): void
    {
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $result = $this->backup->verifyBackup('content', 'nonexistent.sql');

        $this->assertFalse($result);
    }

    /**
     * @throws SodiumException
     */ public function testVerifyBackupWithValidData(): void
    {
        // Create mock database result
        $mockResult = new stdClass();
        $mockResult->id = 1;
        $mockResult->filename = 'test.sql';
        $mockResult->authkey = sodium_bin2hex(sodium_crypto_auth_keygen());

        // Generate valid auth code
        $content = 'test backup content';
        $authKey = sodium_hex2bin($mockResult->authkey);
        $mockResult->authcode = sodium_bin2hex(sodium_crypto_auth($content, $authKey));

        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(1);
        $this->mockDb->method('fetchObject')->willReturn($mockResult);

        $result = $this->backup->verifyBackup($content, 'test.sql');

        $this->assertTrue($result);
    }

    public function testGenerateBackupQueriesWithEmptyTableNames(): void
    {
        $this->mockDatabaseHelper->method('buildInsertQueries')->willReturn([]);

        $result = $this->backup->generateBackupQueries('');

        $this->assertStringContainsString('DO NOT REMOVE THE FIRST LINE!', $result);
        $this->assertStringContainsString('pmftableprefix:', $result);
    }

    public function testGenerateBackupQueriesWithMultipleTables(): void
    {
        $this->mockDatabaseHelper
            ->method('buildInsertQueries')
            ->willReturn(['INSERT INTO table1 VALUES (1);', 'INSERT INTO table2 VALUES (2);']);

        $result = $this->backup->generateBackupQueries('table1 table2');

        $this->assertStringContainsString('INSERT INTO table1 VALUES (1);', $result);
        $this->assertStringContainsString('INSERT INTO table2 VALUES (2);', $result);
    }

    public function testGenerateBackupQueriesWithSingleTable(): void
    {
        $this->mockDatabaseHelper
            ->method('buildInsertQueries')
            ->willReturn(['INSERT INTO faqconfig VALUES (1, "test");']);

        $result = $this->backup->generateBackupQueries('faqconfig');

        $this->assertStringContainsString('INSERT INTO faqconfig VALUES (1, "test");', $result);
        $this->assertStringContainsString('4.0', $result); // Version should be in header
    }

    /**
     * @throws \Exception
     */ public function testGetBackupTableNamesForDataBackup(): void
    {
        $mockTables = [
            'faqconfig',
            'faqdata',
            'faqadminlog', // Should be excluded for DATA backup
            'faqsessions', // Should be excluded for DATA backup
            'faqcategories',
        ];

        $this->mockDb->method('getTableNames')->willReturn($mockTables);

        $result = $this->backup->getBackupTableNames(BackupType::BACKUP_TYPE_DATA);

        $this->assertStringContainsString('faqconfig', $result);
        $this->assertStringContainsString('faqdata', $result);
        $this->assertStringContainsString('faqcategories', $result);
        $this->assertStringNotContainsString('faqadminlog', $result);
        $this->assertStringNotContainsString('faqsessions', $result);
    }

    /**
     * @throws \Exception
     */ public function testGetBackupTableNamesForLogsBackup(): void
    {
        $mockTables = [
            'faqconfig',
            'faqdata',
            'faqadminlog', // Should be included for LOGS backup
            'faqsessions', // Should be included for LOGS backup
            'faqcategories',
        ];

        $this->mockDb->method('getTableNames')->willReturn($mockTables);

        $result = $this->backup->getBackupTableNames(BackupType::BACKUP_TYPE_LOGS);

        $this->assertStringContainsString('faqadminlog', $result);
        $this->assertStringContainsString('faqsessions', $result);
        $this->assertStringNotContainsString('faqconfig', $result);
        $this->assertStringNotContainsString('faqdata', $result);
        $this->assertStringNotContainsString('faqcategories', $result);
    }

    /**
     * @throws \Exception
     */ public function testGetBackupTableNamesWithEmptyTableList(): void
    {
        $this->mockDb->method('getTableNames')->willReturn([]);

        $result = $this->backup->getBackupTableNames(BackupType::BACKUP_TYPE_DATA);

        $this->assertEquals('', $result);
    }

    /**
     * @throws \Exception
     */ public function testGetBackupTableNamesWithWhitespaceInTableNames(): void
    {
        $mockTables = [
            '  faqconfig  ', // With whitespace
            'faqdata',
            '  faqcategories  ',
        ];

        $this->mockDb->method('getTableNames')->willReturn($mockTables);

        $result = $this->backup->getBackupTableNames(BackupType::BACKUP_TYPE_DATA);

        // Should still include tables despite whitespace
        $this->assertStringContainsString('faqconfig', $result);
        $this->assertStringContainsString('faqdata', $result);
        $this->assertStringContainsString('faqcategories', $result);
    }

    public function testBackupHeaderGeneration(): void
    {
        $this->mockDatabaseHelper->method('buildInsertQueries')->willReturn([]);

        $result = $this->backup->generateBackupQueries('test_table');

        $expectedHeaderElements = [
            'pmf4.0: test_table',
            'DO NOT REMOVE THE FIRST LINE!',
            'pmftableprefix:',
            'DO NOT REMOVE THE LINES ABOVE!',
            'Otherwise this backup will be broken.',
        ];

        foreach ($expectedHeaderElements as $element) {
            $this->assertStringContainsString($element, $result);
        }
    }

    /**
     * @throws SodiumException
     */ public function testCreateBackupDateFormat(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->backup->createBackup('data', 'test content');

        // Check date format: YYYY-MM-DD-HH-MM-SS
        $this->assertMatchesRegularExpression('/phpmyfaq-data\.\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.sql/', $result);
    }

    public function testGenerateBackupQueriesIntegration(): void
    {
        // Test full integration with DatabaseHelper
        $mockQueries = [
            'INSERT INTO faqconfig (meta_key, meta_value) VALUES ("main.language", "en");',
            'INSERT INTO faqconfig (meta_key, meta_value) VALUES ("main.currentVersion", "4.0.0");',
        ];

        $this->mockDatabaseHelper
            ->expects($this->exactly(2))
            ->method('buildInsertQueries')
            ->willReturn($mockQueries);

        $result = $this->backup->generateBackupQueries('table1 table2');

        foreach ($mockQueries as $query) {
            $this->assertStringContainsString($query, $result);
        }
    }

    public function testGetLastBackupInfoWithNoBackups(): void
    {
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);
        $this->mockDb->method('fetchAll')->willReturn([]);

        $result = $this->backup->getLastBackupInfo();

        $this->assertNull($result['lastBackupDate']);
        $this->assertTrue($result['isBackupOlderThan30Days']);
    }

    public function testGetLastBackupInfoWithRecentBackup(): void
    {
        $recentDate = date('Y-m-d H:i:s');
        $mockBackup = (object) [
            'id' => 1,
            'filename' => 'backup.sql',
            'created' => $recentDate,
        ];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(1);
        $this->mockDb->method('fetchAll')->willReturn([$mockBackup]);

        $result = $this->backup->getLastBackupInfo();

        $this->assertEquals($recentDate, $result['lastBackupDate']);
        $this->assertFalse($result['isBackupOlderThan30Days']);
    }

    public function testGetLastBackupInfoWithOldBackup(): void
    {
        $oldDate = date('Y-m-d H:i:s', strtotime('-35 days'));
        $mockBackup = (object) [
            'id' => 1,
            'filename' => 'backup.sql',
            'created' => $oldDate,
        ];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(1);
        $this->mockDb->method('fetchAll')->willReturn([$mockBackup]);

        $result = $this->backup->getLastBackupInfo();

        $this->assertEquals($oldDate, $result['lastBackupDate']);
        $this->assertTrue($result['isBackupOlderThan30Days']);
    }

    public function testGetLastBackupInfoWithInvalidDate(): void
    {
        $mockBackup = (object) [
            'id' => 1,
            'filename' => 'backup.sql',
            'created' => 'invalid-date',
        ];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(1);
        $this->mockDb->method('fetchAll')->willReturn([$mockBackup]);

        $result = $this->backup->getLastBackupInfo();

        $this->assertNull($result['lastBackupDate']);
        $this->assertTrue($result['isBackupOlderThan30Days']);
    }

    public function testParseBackupFile(): void
    {
        $backupFile = PMF_TEST_DIR . '/test-backup.sql';
        $content =
            "-- pmf4.0: faqconfig faqdata\n"
            . "-- DO NOT REMOVE THE FIRST LINE!\n"
            . "-- pmftableprefix: pmf_\n"
            . "-- DO NOT REMOVE THE LINES ABOVE!\n"
            . "INSERT INTO faqconfig VALUES (1, 'test');\n"
            . "INSERT INTO faqdata VALUES (2, 'data');";

        file_put_contents($backupFile, $content);

        $result = $this->backup->parseBackupFile($backupFile, '4.0.0');

        $this->assertTrue($result->versionMatches);
        $this->assertEquals('-- pmf4.0', $result->versionFound);
        $this->assertEquals('-- pmf4.0', $result->versionExpected);
        $this->assertEquals('pmf_', $result->tablePrefix);
        $this->assertGreaterThan(0, count($result->queries));

        unlink($backupFile);
    }

    public function testParseBackupFileWithMultiLineStatement(): void
    {
        $backupFile = PMF_TEST_DIR . '/test-backup-multiline.sql';
        $content =
            "-- pmf4.0: faqconfig\n"
            . "-- DO NOT REMOVE THE FIRST LINE!\n"
            . "-- pmftableprefix: pmf_\n"
            . "INSERT INTO faqconfig VALUES (1, 'line1\n"
            . "line2\n"
            . "line3');";

        file_put_contents($backupFile, $content);

        $result = $this->backup->parseBackupFile($backupFile, '4.0.0');

        // Should have 2 queries: DELETE FROM faqconfig and the INSERT
        $this->assertCount(2, $result->queries);
        $this->assertEquals('DELETE FROM faqconfig', $result->queries[0]);
        $this->assertStringContainsString("line1\nline2\nline3", $result->queries[1]);

        unlink($backupFile);
    }

    public function testParseBackupFileWithHashInData(): void
    {
        $backupFile = PMF_TEST_DIR . '/test-backup-hash.sql';
        $content =
            "-- pmf4.0: faqconfig\n"
            . "-- DO NOT REMOVE THE FIRST LINE!\n"
            . "-- pmftableprefix: pmf_\n"
            . "# This is a MySQL comment\n"
            . "INSERT INTO faqconfig VALUES (1, 'Text with # hash symbol');\n"
            . "INSERT INTO faqconfig VALUES (2, 'C# programming');";

        file_put_contents($backupFile, $content);

        $result = $this->backup->parseBackupFile($backupFile, '4.0.0');

        // Should have 3 queries: DELETE + 2 INSERTs (comment line skipped)
        $this->assertCount(3, $result->queries);
        $this->assertStringContainsString('# hash symbol', $result->queries[1]);
        $this->assertStringContainsString('C#', $result->queries[2]);

        unlink($backupFile);
    }

    public function testParseBackupFileWithEscapedQuotes(): void
    {
        $backupFile = PMF_TEST_DIR . '/test-backup-quotes.sql';
        $content =
            "-- pmf4.0: faqconfig\n"
            . "-- DO NOT REMOVE THE FIRST LINE!\n"
            . "-- pmftableprefix: pmf_\n"
            . "INSERT INTO faqconfig VALUES (1, 'It''s a test');\n"
            . "INSERT INTO faqconfig VALUES (2, 'He said \"hello\"');";

        file_put_contents($backupFile, $content);

        $result = $this->backup->parseBackupFile($backupFile, '4.0.0');

        // Should have 3 queries: DELETE + 2 INSERTs
        $this->assertCount(3, $result->queries);
        $this->assertStringContainsString("It''s a test", $result->queries[1]);

        unlink($backupFile);
    }

    public function testParseBackupFileWithSemicolonInData(): void
    {
        $backupFile = PMF_TEST_DIR . '/test-backup-semicolon.sql';
        $content =
            "-- pmf4.0: faqconfig\n"
            . "-- DO NOT REMOVE THE FIRST LINE!\n"
            . "-- pmftableprefix: pmf_\n"
            . "INSERT INTO faqconfig VALUES (1, 'Text with; semicolon');\n"
            . "INSERT INTO faqconfig VALUES (2, 'normal');";

        file_put_contents($backupFile, $content);

        $result = $this->backup->parseBackupFile($backupFile, '4.0.0');

        // Should have 3 queries: DELETE + 2 INSERTs
        $this->assertCount(3, $result->queries);
        $this->assertStringContainsString('with; semicolon', $result->queries[1]);

        unlink($backupFile);
    }

    public function testParseBackupFileWithVersionMismatch(): void
    {
        $backupFile = PMF_TEST_DIR . '/test-backup-old.sql';
        $content =
            "-- pmf3.2: faqconfig\n"
            . "-- DO NOT REMOVE THE FIRST LINE!\n"
            . "-- pmftableprefix: \n"
            . "INSERT INTO faqconfig VALUES (1, 'test');";

        file_put_contents($backupFile, $content);

        $result = $this->backup->parseBackupFile($backupFile, '4.0.0');

        $this->assertFalse($result->versionMatches);
        $this->assertEquals('-- pmf3.2', $result->versionFound);
        $this->assertEquals('-- pmf4.0', $result->versionExpected);

        unlink($backupFile);
    }

    public function testParseBackupFileWithNonExistentFile(): void
    {
        $this->expectException(\phpMyFAQ\Core\Exception::class);
        $this->expectExceptionMessage('Cannot open backup file');

        // Suppress expected fopen warning
        @$this->backup->parseBackupFile('/nonexistent/file.sql', '4.0.0');
    }

    public function testParseBackupFileWithEmptyFile(): void
    {
        $backupFile = PMF_TEST_DIR . '/empty-backup.sql';
        file_put_contents($backupFile, '');

        $this->expectException(\phpMyFAQ\Core\Exception::class);
        $this->expectExceptionMessage('Empty backup file');

        try {
            $this->backup->parseBackupFile($backupFile, '4.0.0');
        } finally {
            unlink($backupFile);
        }
    }

    public function testExecuteBackupQueries(): void
    {
        $queries = [];

        $result = $this->backup->executeBackupQueries($queries, 'pmf_');

        $this->assertEquals(0, $result->queriesOk);
        $this->assertEquals(0, $result->queriesFailed);
    }

    public function testExecuteBackupQueriesWithFailures(): void
    {
        // Mock the database to simulate query failures
        $mockDb = $this->createMock(DatabaseDriver::class);
        $mockDb->method('query')->willReturn(false);
        $mockDb->method('error')->willReturn('Mocked SQL error');

        $mockConfig = $this->createStub(Configuration::class);
        $mockConfig->method('getDb')->willReturn($mockDb);

        $databaseHelper = new DatabaseHelper($mockConfig);
        $backup = new Backup($mockConfig, $databaseHelper);

        $queries = ['SELECT 1', 'SELECT 2'];
        $result = $backup->executeBackupQueries($queries, 'pmf_');

        // All queries should fail with mocked error
        $this->assertEquals(0, $result->queriesOk);
        $this->assertEquals(2, $result->queriesFailed);
        $this->assertNotNull($result->lastErrorQuery);
        $this->assertEquals('Mocked SQL error', $result->lastErrorReason);
    }

    public function testExecuteBackupQueriesWithEmptyArray(): void
    {
        $result = $this->backup->executeBackupQueries([], 'pmf_');

        $this->assertEquals(0, $result->queriesOk);
        $this->assertEquals(0, $result->queriesFailed);
    }

    public function testExecuteBackupQueriesWithDifferentTablePrefix(): void
    {
        // Mock database to return success for queries
        $mockDb = $this->createMock(DatabaseDriver::class);
        $mockDb->method('query')->willReturn(true);

        $mockConfig = $this->createStub(Configuration::class);
        $mockConfig->method('getDb')->willReturn($mockDb);

        $databaseHelper = new DatabaseHelper($mockConfig);
        $backup = new Backup($mockConfig, $databaseHelper);

        $queries = ['SELECT 1'];
        $result = $backup->executeBackupQueries($queries, 'old_prefix_');

        // Query should execute successfully with mocked database
        $this->assertEquals(1, $result->queriesOk);
        $this->assertEquals(0, $result->queriesFailed);
    }

    /**
     * @throws SodiumException
     */ public function testExportForDataBackup(): void
    {
        $mockTables = ['faqconfig', 'faqdata'];
        $mockQueries = ['INSERT INTO faqconfig VALUES (1);'];

        $this->mockDb->method('getTableNames')->willReturn($mockTables);
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $this->mockDatabaseHelper->method('buildInsertQueries')->willReturn($mockQueries);

        $result = $this->backup->export(BackupType::BACKUP_TYPE_DATA);

        $this->assertInstanceOf(BackupExportResult::class, $result);
        $this->assertStringContainsString('phpmyfaq-data', $result->fileName);
        $this->assertStringContainsString('pmf4.0', $result->content);
        $this->assertStringContainsString('DO NOT REMOVE', $result->content);
    }

    /**
     * @throws SodiumException
     */ public function testExportForLogsBackup(): void
    {
        $mockTables = ['faqadminlog', 'faqsessions'];
        $mockQueries = ['INSERT INTO faqadminlog VALUES (1);'];

        $this->mockDb->method('getTableNames')->willReturn($mockTables);
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $this->mockDatabaseHelper->method('buildInsertQueries')->willReturn($mockQueries);

        $result = $this->backup->export(BackupType::BACKUP_TYPE_LOGS);

        $this->assertInstanceOf(BackupExportResult::class, $result);
        $this->assertStringContainsString('phpmyfaq-logs', $result->fileName);
    }

    /**
     * @throws SodiumException
     */ public function testExportForContentBackupThrowsException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('To be implemented');

        $this->mockDb->method('getTableNames')->willReturn([]);

        $this->backup->export(BackupType::BACKUP_TYPE_CONTENT);
    }
}
