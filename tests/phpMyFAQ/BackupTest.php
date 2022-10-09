<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use SodiumException;

/**
 * Class BackupTest
 *
 * @testdox Backup should
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

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->configuration->config['main.currentVersion'] = System::getVersion();

        $this->databaseHelper = new DatabaseHelper($this->configuration);

        $this->backup = new Backup($this->configuration, $this->databaseHelper);
    }

    /**
     * @testdox create a complete backup file
     * @throws SodiumException
     */
    public function testCreateBackup(): void
    {
        $tableNames = 'faqconfig faqinstances';
        $backupQueries = $this->backup->generateBackupQueries($tableNames);
        $dataBackup = $this->backup->createBackup(Backup::BACKUP_TYPE_DATA, $backupQueries);
        $expected = 'phpmyfaq-data.' . date('Y-m-d-H-i-s') . '.sql';

        $this->assertEquals($expected, $dataBackup);

        $tableNames = 'faqadminlog faqsessions';
        $backupQueries = $this->backup->generateBackupQueries($tableNames);
        $logsBackup = $this->backup->createBackup(Backup::BACKUP_TYPE_LOGS, $backupQueries);
        $expected = 'phpmyfaq-logs.' . date('Y-m-d-H-i-s') . '.sql';

        $this->assertEquals($expected, $logsBackup);
    }

    /**
     * @throws SodiumException
     */
    public function testVerifyBackup(): void
    {
        $tableNames = 'faqconfig faqinstances';
        $backupQueries = $this->backup->generateBackupQueries($tableNames);
        $dataBackup = $this->backup->createBackup(Backup::BACKUP_TYPE_DATA, $backupQueries);

        $result = $this->backup->verifyBackup($backupQueries, $dataBackup);

        $this->assertTrue($result);
    }

    /**
     * @testdox generates correct INSERT queries for the backup
     */
    public function testGenerateBackupQueries(): void
    {
        $tableNames = 'faqconfig faqinstances';
        $queries = $this->backup->generateBackupQueries($tableNames);

        $this->assertStringContainsString('DO NOT REMOVE THE FIRST LINE!', $queries);
    }
}
