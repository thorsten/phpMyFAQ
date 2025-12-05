<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\System;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use SodiumException;
use stdClass;

/**
 * Class BackupTest
 *
 * @package phpMyFAQ
 */
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

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->getAll();

        $this->databaseHelper = new DatabaseHelper($this->configuration);

        $this->backup = new Backup($this->configuration, $this->databaseHelper);

        // Mock Configuration class
        $this->mockConfiguration = $this->createStub(Configuration::class);

        // Mock DatabaseHelper class
        $this->mockDatabaseHelper = $this->createMock(DatabaseHelper::class);

        // Mock Database class
        $this->mockDb = $this->createMock(DatabaseDriver::class);

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
        $this->mockDatabaseHelper->method('buildInsertQueries')->willReturn(['INSERT INTO faqconfig VALUES (1, "test");']);
        $backupQueries = $this->backup->generateBackupQueries($tableNames);

        // Mock createBackup method
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $dataBackup = $this->backup->createBackup(BackupType::BACKUP_TYPE_DATA->value, $backupQueries);

        // Create mock database result for verification
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

    public function testCreateBackupWithTablePrefix(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->backup->createBackup('data', 'test backup content');

        $this->assertStringStartsWith('phpmyfaq-data.', $result);
        $this->assertStringEndsWith('.sql', $result);
    }

    public function testCreateBackupWithoutTablePrefix(): void
    {
        // Mock Database static methods
        \phpMyFAQ\Database::setTablePrefix('');

        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->backup->createBackup('logs', 'test backup content');

        $this->assertStringStartsWith('phpmyfaq-logs.', $result);
        $this->assertStringEndsWith('.sql', $result);
    }

    public function testCreateBackupEscapesInput(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')
            ->willReturnCallback(function ($input) {
                return str_replace("'", "\\'", $input);
            });
        $this->mockDb->method('query')->willReturn(true);

        $maliciousContent = "'; DROP TABLE users; --";
        $result = $this->backup->createBackup('data', $maliciousContent);

        $this->assertIsString($result);
    }

    public function testVerifyBackupWithNonExistentFile(): void
    {
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('numRows')->willReturn(0);

        $result = $this->backup->verifyBackup('content', 'nonexistent.sql');

        $this->assertFalse($result);
    }

    public function testVerifyBackupWithValidData(): void
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
        $this->mockDatabaseHelper->method('buildInsertQueries')
            ->willReturn(['INSERT INTO table1 VALUES (1);', 'INSERT INTO table2 VALUES (2);']);

        $result = $this->backup->generateBackupQueries('table1 table2');

        $this->assertStringContainsString('INSERT INTO table1 VALUES (1);', $result);
        $this->assertStringContainsString('INSERT INTO table2 VALUES (2);', $result);
    }

    public function testGenerateBackupQueriesWithSingleTable(): void
    {
        $this->mockDatabaseHelper->method('buildInsertQueries')
            ->willReturn(['INSERT INTO faqconfig VALUES (1, "test");']);

        $result = $this->backup->generateBackupQueries('faqconfig');

        $this->assertStringContainsString('INSERT INTO faqconfig VALUES (1, "test");', $result);
        $this->assertStringContainsString('4.0', $result); // Version should be in header
    }

    public function testGetBackupTableNamesForDataBackup(): void
    {
        $mockTables = [
            'faqconfig',
            'faqdata',
            'faqadminlog', // Should be excluded for DATA backup
            'faqsessions', // Should be excluded for DATA backup
            'faqcategories'
        ];

        $this->mockDb->method('getTableNames')->willReturn($mockTables);

        $result = $this->backup->getBackupTableNames(BackupType::BACKUP_TYPE_DATA);

        $this->assertStringContainsString('faqconfig', $result);
        $this->assertStringContainsString('faqdata', $result);
        $this->assertStringContainsString('faqcategories', $result);
        $this->assertStringNotContainsString('faqadminlog', $result);
        $this->assertStringNotContainsString('faqsessions', $result);
    }

    public function testGetBackupTableNamesForLogsBackup(): void
    {
        $mockTables = [
            'faqconfig',
            'faqdata',
            'faqadminlog', // Should be included for LOGS backup
            'faqsessions', // Should be included for LOGS backup
            'faqcategories'
        ];

        $this->mockDb->method('getTableNames')->willReturn($mockTables);

        $result = $this->backup->getBackupTableNames(BackupType::BACKUP_TYPE_LOGS);

        $this->assertStringContainsString('faqadminlog', $result);
        $this->assertStringContainsString('faqsessions', $result);
        $this->assertStringNotContainsString('faqconfig', $result);
        $this->assertStringNotContainsString('faqdata', $result);
        $this->assertStringNotContainsString('faqcategories', $result);
    }

    public function testGetBackupTableNamesWithEmptyTableList(): void
    {
        $this->mockDb->method('getTableNames')->willReturn([]);

        $result = $this->backup->getBackupTableNames(BackupType::BACKUP_TYPE_DATA);

        $this->assertEquals('', $result);
    }

    public function testGetBackupTableNamesWithWhitespaceInTableNames(): void
    {
        $mockTables = [
            '  faqconfig  ', // With whitespace
            'faqdata',
            '  faqcategories  '
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
            'Otherwise this backup will be broken.'
        ];

        foreach ($expectedHeaderElements as $element) {
            $this->assertStringContainsString($element, $result);
        }
    }

    public function testCreateBackupDateFormat(): void
    {
        $this->mockDb->method('nextId')->willReturn(1);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->backup->createBackup('data', 'test content');

        // Check date format: YYYY-MM-DD-HH-MM-SS
        $this->assertMatchesRegularExpression(
            '/phpmyfaq-data\.\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.sql/',
            $result
        );
    }

    public function testGenerateBackupQueriesIntegration(): void
    {
        // Test full integration with DatabaseHelper
        $mockQueries = [
            'INSERT INTO faqconfig (meta_key, meta_value) VALUES ("main.language", "en");',
            'INSERT INTO faqconfig (meta_key, meta_value) VALUES ("main.currentVersion", "4.0.0");'
        ];

        $this->mockDatabaseHelper->expects($this->exactly(2))
            ->method('buildInsertQueries')
            ->willReturn($mockQueries);

        $result = $this->backup->generateBackupQueries('table1 table2');

        foreach ($mockQueries as $query) {
            $this->assertStringContainsString($query, $result);
        }
    }
}
