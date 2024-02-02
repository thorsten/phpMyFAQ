<?php

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseHelper;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\BackupType;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;
use SodiumException;

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
    }

    /**
     * @throws SodiumException
     */
    public function testCreateBackup(): void
    {
        $tableNames = 'faqconfig faqinstances';
        $backupQueries = $this->backup->generateBackupQueries($tableNames);
        $dataBackup = $this->backup->createBackup(BackupType::BACKUP_TYPE_DATA->value, $backupQueries);
        $expected = 'phpmyfaq-data.' . date('Y-m-d-H-i-s') . '.sql';

        $this->assertEquals($expected, $dataBackup);

        $tableNames = 'faqadminlog faqsessions';
        $backupQueries = $this->backup->generateBackupQueries($tableNames);
        $logsBackup = $this->backup->createBackup(BackupType::BACKUP_TYPE_LOGS->value, $backupQueries);
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
        $dataBackup = $this->backup->createBackup(BackupType::BACKUP_TYPE_DATA->value, $backupQueries);

        $result = $this->backup->verifyBackup($backupQueries, $dataBackup);

        $this->assertTrue($result);
    }

    public function testGenerateBackupQueries(): void
    {
        $tableNames = 'faqconfig faqinstances';
        $queries = $this->backup->generateBackupQueries($tableNames);

        $this->assertStringContainsString('DO NOT REMOVE THE FIRST LINE!', $queries);
    }
}
