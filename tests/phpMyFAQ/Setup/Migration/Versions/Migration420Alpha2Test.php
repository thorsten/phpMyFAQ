<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class Migration420Alpha2Test extends TestCase
{
    private Migration420Alpha2 $migration;

    private Configuration $configuration;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        Database::factory('pdo_sqlite');
        Database::setTablePrefix('');

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-migration-420-alpha2-');
        self::assertNotFalse($this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->migration = new Migration420Alpha2($this->configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @unlink($this->databaseFile);
    }

    public function testImplementsMigrationInterface(): void
    {
        $this->assertInstanceOf(MigrationInterface::class, $this->migration);
    }

    public function testGetVersion(): void
    {
        $this->assertEquals('4.2.0-alpha.2', $this->migration->getVersion());
    }

    public function testGetDescriptionMentionsEditorialWorkflowStatus(): void
    {
        $this->assertStringContainsString(
            'introduce the editorial workflow status on faqdata',
            $this->migration->getDescription(),
        );
    }

    public function testIntroduceEditorialWorkflowStatusAddsColumnAndBackfillsWhenMissing(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faqdata (id INTEGER, lang VARCHAR(5), active CHAR(3))');
        $db->query('CREATE TABLE faqdata_revisions (id INTEGER, lang VARCHAR(5), active CHAR(3))');

        $recorder = new OperationRecorder($this->configuration);
        $method = new ReflectionMethod($this->migration, 'introduceEditorialWorkflowStatus');
        $method->invoke($this->migration, $recorder);

        $queries = $recorder->getSqlQueries();
        $joined = implode("\n", $queries);

        $this->assertStringContainsString('ALTER TABLE faqdata ADD COLUMN status', $joined);
        $this->assertStringContainsString('ALTER TABLE faqdata_revisions ADD COLUMN status', $joined);
        $this->assertStringContainsString(
            "UPDATE faqdata SET status = CASE WHEN active = 'yes' THEN 'published' ELSE 'draft' END",
            $joined,
        );
        $this->assertStringContainsString(
            "UPDATE faqdata_revisions SET status = CASE WHEN active = 'yes' THEN 'published' ELSE 'draft' END",
            $joined,
        );
    }

    public function testIntroduceEditorialWorkflowStatusIsNoOpWhenStatusColumnAlreadyExists(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faqdata (id INTEGER, lang VARCHAR(5), active CHAR(3), status VARCHAR(12))');
        $db->query(
            'CREATE TABLE faqdata_revisions (id INTEGER, lang VARCHAR(5), active CHAR(3), status VARCHAR(12))',
        );

        $recorder = new OperationRecorder($this->configuration);
        $method = new ReflectionMethod($this->migration, 'introduceEditorialWorkflowStatus');
        $method->invoke($this->migration, $recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }
}
