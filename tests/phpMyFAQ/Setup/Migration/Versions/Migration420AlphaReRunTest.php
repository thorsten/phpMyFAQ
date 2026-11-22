<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * The installed version is only stored when a whole update has finished, so an update that
 * was interrupted after this migration runs it again. Its ALTER TABLE and CREATE INDEX steps
 * must then be skipped instead of failing on what already exists.
 */
#[AllowMockObjectsWithoutExpectations]
class Migration420AlphaReRunTest extends TestCase
{
    private Configuration $configuration;

    private string $databaseFile;

    private mixed $previousDatabaseType = null;

    private string $previousTablePrefix = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        $this->previousTablePrefix = Database::getTablePrefix();
        Database::factory('pdo_sqlite');
        Database::setTablePrefix('');

        $databaseFile = tempnam(sys_get_temp_dir(), 'pmf-migration-420-alpha-');
        self::assertNotFalse($databaseFile);
        $this->databaseFile = $databaseFile;

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');
        $dbHandle->query('CREATE TABLE faqadminlog (id INTEGER PRIMARY KEY, text TEXT)');
        $dbHandle->query('CREATE TABLE faquserdata (user_id INTEGER)');

        $this->configuration = new Configuration($dbHandle);
    }

    protected function tearDown(): void
    {
        $this->configuration->getDb()->close();
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
        Database::setTablePrefix($this->previousTablePrefix);

        parent::tearDown();

        @unlink($this->databaseFile);
    }

    public function testEverySqlStatementCanBeExecutedASecondTime(): void
    {
        $db = $this->configuration->getDb();

        foreach ($this->recordedQueries() as $query) {
            $this->assertNotFalse($db->query($query), $query);
        }

        foreach ($this->recordedQueries() as $query) {
            $this->assertNotFalse($db->query($query), 'Second run: ' . $query);
        }
    }

    public function testColumnAndIndexStepsAreSkippedOnceTheyAreApplied(): void
    {
        $first = implode("\n", $this->recordedQueries());
        $this->assertStringContainsString('ALTER TABLE faqadminlog ADD COLUMN hash VARCHAR(64)', $first);
        $this->assertStringContainsString('ALTER TABLE faqadminlog ADD COLUMN previous_hash VARCHAR(64)', $first);
        $this->assertStringContainsString('idx_hash', $first);
        $this->assertStringContainsString('ALTER TABLE faquserdata ADD COLUMN keycloak_sub', $first);
        $this->assertStringContainsString('idx_faquserdata_keycloak_sub', $first);

        $db = $this->configuration->getDb();
        foreach ($this->recordedQueries() as $query) {
            $db->query($query);
        }

        $second = implode("\n", $this->recordedQueries());
        $this->assertStringNotContainsString('ALTER TABLE', $second);
        $this->assertStringNotContainsString('idx_hash', $second);
        $this->assertStringNotContainsString('idx_faquserdata_keycloak_sub', $second);
    }

    /**
     * MySQL has no CREATE INDEX IF NOT EXISTS: the statement itself cannot be made re-runnable,
     * so it must not be recorded when the catalog already lists the index.
     */
    public function testMySqlIndexesAreNotCreatedAgain(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'pdo_mysql');

        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('escape')->willReturnArgument(0);
        // Every column and every index is reported as present
        $driver->method('numRows')->willReturn(1);
        $driver->method('fetchObject')->willReturn((object) ['idx_count' => 1]);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        $recorder = new OperationRecorder($configuration);
        new Migration420Alpha($configuration)->up($recorder);
        $joined = implode("\n", $recorder->getSqlQueries());

        $this->assertStringNotContainsString('CREATE INDEX idx_hash', $joined);
        $this->assertStringNotContainsString('CREATE UNIQUE INDEX idx_faquserdata_keycloak_sub', $joined);
        $this->assertStringNotContainsString('ALTER TABLE', $joined);
    }

    /**
     * @return string[]
     */
    private function recordedQueries(): array
    {
        $recorder = new OperationRecorder($this->configuration);
        new Migration420Alpha($this->configuration)->up($recorder);

        return $recorder->getSqlQueries();
    }
}
