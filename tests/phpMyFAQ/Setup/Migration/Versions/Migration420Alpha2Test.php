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
        $this->configuration->getDb()->close();

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

    public function testIntroduceEditorialWorkflowStatusAddsColumnBackfillsAndDropsWhenActivePresent(): void
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
        $this->assertStringContainsString('ALTER TABLE faqdata DROP COLUMN active', $joined);
        $this->assertStringContainsString('ALTER TABLE faqdata_revisions DROP COLUMN active', $joined);
    }

    /**
     * Covers a re-run that died between adding "status" and dropping "active": the ADD COLUMN
     * step is skipped because "status" already exists, but the backfill and the DROP COLUMN must
     * still run because "active" is still there.
     */
    public function testIntroduceEditorialWorkflowStatusBackfillsAndDropsWhenBothColumnsExist(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faqdata (id INTEGER, lang VARCHAR(5), active CHAR(3), status VARCHAR(12))');
        $db->query(
            'CREATE TABLE faqdata_revisions (id INTEGER, lang VARCHAR(5), active CHAR(3), status VARCHAR(12))',
        );

        $recorder = new OperationRecorder($this->configuration);
        $method = new ReflectionMethod($this->migration, 'introduceEditorialWorkflowStatus');
        $method->invoke($this->migration, $recorder);

        $queries = $recorder->getSqlQueries();
        $joined = implode("\n", $queries);

        $this->assertStringNotContainsString('ADD COLUMN status', $joined);
        $this->assertStringContainsString(
            "UPDATE faqdata SET status = CASE WHEN active = 'yes' THEN 'published' ELSE 'draft' END",
            $joined,
        );
        $this->assertStringContainsString(
            "UPDATE faqdata_revisions SET status = CASE WHEN active = 'yes' THEN 'published' ELSE 'draft' END",
            $joined,
        );
        $this->assertStringContainsString('ALTER TABLE faqdata DROP COLUMN active', $joined);
        $this->assertStringContainsString('ALTER TABLE faqdata_revisions DROP COLUMN active', $joined);
    }

    /**
     * The truly finished state: "status" exists and "active" is already gone, so neither the
     * backfill nor the drop may run again.
     */
    public function testIntroduceEditorialWorkflowStatusIsNoOpWhenActiveAlreadyDropped(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faqdata (id INTEGER, lang VARCHAR(5), status VARCHAR(12))');
        $db->query('CREATE TABLE faqdata_revisions (id INTEGER, lang VARCHAR(5), status VARCHAR(12))');

        $recorder = new OperationRecorder($this->configuration);
        $method = new ReflectionMethod($this->migration, 'introduceEditorialWorkflowStatus');
        $method->invoke($this->migration, $recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }

    /**
     * Exercises up() itself (not the private method via reflection) against a schema still
     * missing "status", so this fails if the `$this->introduceEditorialWorkflowStatus($recorder);`
     * call is ever removed or reordered out of up(). Calling up() is safe here: every branch only
     * records operations on the recorder, it never executes SQL against the database.
     */
    public function testUpRecordsEditorialWorkflowStatusSqlWhenMissing(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faqdata (id INTEGER, lang VARCHAR(5), active CHAR(3))');
        $db->query('CREATE TABLE faqdata_revisions (id INTEGER, lang VARCHAR(5), active CHAR(3))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $joined = implode("\n", $recorder->getSqlQueries());

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
        $this->assertStringContainsString('ALTER TABLE faqdata DROP COLUMN active', $joined);
        $this->assertStringContainsString('ALTER TABLE faqdata_revisions DROP COLUMN active', $joined);
    }

    /**
     * Companion re-run-safety check driven through up() rather than the private method directly.
     * up() also records unrelated SQL (the faquser_right_language/faqgroup_right_language/
     * faqquestion_history table creation, plus the approverec retirement DELETEs), so this
     * asserts the specific absence of the editorial status SQL rather than asserting the whole
     * recorder is empty.
     */
    public function testUpDoesNotRecordEditorialWorkflowStatusSqlWhenAlreadyPresent(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faqdata (id INTEGER, lang VARCHAR(5), status VARCHAR(12))');
        $db->query('CREATE TABLE faqdata_revisions (id INTEGER, lang VARCHAR(5), status VARCHAR(12))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $queries = $recorder->getSqlQueries();
        $joined = implode("\n", $queries);

        $this->assertNotEmpty($queries, 'Expected up() to still record its other, unrelated operations.');
        $this->assertStringNotContainsString('ADD COLUMN status', $joined);
        $this->assertStringNotContainsString('SET status = CASE', $joined);
        $this->assertStringNotContainsString('DROP COLUMN active', $joined);
    }

    /**
     * approverec's grants must be removed from every right-mapping table (including the
     * category-scoped one) and from faqright itself, via idempotent DELETEs keyed off a
     * subselect so a re-run against an already-retired right is a no-op.
     */
    public function testUpRecordsApproveRightRetirement(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $joined = implode("\n", $recorder->getSqlQueries());
        $rightIdSelect = "SELECT right_id FROM faqright WHERE name = 'approverec'";

        foreach (
            [
                'faquser_right',
                'faqgroup_right',
                'faquser_right_language',
                'faqgroup_right_language',
                'faqgroup_right_category',
            ] as $mappingTable
        ) {
            $this->assertStringContainsString(
                sprintf('DELETE FROM %s WHERE right_id IN (%s)', $mappingTable, $rightIdSelect),
                $joined,
            );
        }

        $this->assertStringContainsString("DELETE FROM faqright WHERE name = 'approverec'", $joined);
    }

    public function testGetDescriptionMentionsActiveFlagReplacementAndApproveRightRetirement(): void
    {
        $description = $this->migration->getDescription();

        $this->assertStringContainsString('replace the active flag with it', $description);
        $this->assertStringContainsString('retire the unused approverec right', $description);
    }

    /**
     * The lossy VARCHAR/TEXT columns must become NVARCHAR on SQL Server (#1896). Columns bound
     * to a default constraint (seo_robots, the faquser token columns) or used in an index
     * (faqcustompages.slug) cannot change their type directly, so those constraints have to be
     * dropped first and restored afterwards.
     */
    public function testConvertSqlServerColumnsToUnicodeRecordsAltersOnSqlServer(): void
    {
        Database::factory('pdo_sqlsrv');

        try {
            $migration = new Migration420Alpha2($this->configuration);
            $recorder = new OperationRecorder($this->configuration);
            $method = new ReflectionMethod($migration, 'convertSqlServerColumnsToUnicode');
            $method->invoke($migration, $recorder);

            $joined = implode("\n", $recorder->getSqlQueries());

            $this->assertStringContainsString(
                'ALTER TABLE faqcustompages ALTER COLUMN page_title NVARCHAR(255) NOT NULL',
                $joined,
            );
            $this->assertStringContainsString('DROP INDEX idx_custompages_slug ON faqcustompages', $joined);
            $this->assertStringContainsString(
                'ALTER TABLE faqcustompages ALTER COLUMN slug NVARCHAR(255) NOT NULL',
                $joined,
            );
            $this->assertStringContainsString(
                'CREATE INDEX idx_custompages_slug ON faqcustompages (slug, lang)',
                $joined,
            );
            $this->assertStringContainsString(
                'ALTER TABLE faqcustompages ALTER COLUMN seo_robots NVARCHAR(50) NOT NULL',
                $joined,
            );
            $this->assertStringContainsString("ADD DEFAULT 'index,follow' FOR seo_robots", $joined);
            $this->assertStringContainsString('ALTER TABLE faqseo ALTER COLUMN title NVARCHAR(MAX) NULL', $joined);
            $this->assertStringContainsString('ALTER TABLE faqseo ALTER COLUMN slug NVARCHAR(MAX) NULL', $joined);
            $this->assertStringContainsString(
                'ALTER TABLE faquser ALTER COLUMN refresh_token NVARCHAR(MAX) NULL',
                $joined,
            );
            $this->assertStringContainsString(
                'ALTER TABLE faquser ALTER COLUMN webauthnkeys NVARCHAR(MAX) NULL',
                $joined,
            );
            $this->assertStringContainsString(
                'ALTER TABLE faquser ALTER COLUMN code_verifier NVARCHAR(255) NULL',
                $joined,
            );
            $this->assertStringContainsString("c.name = 'refresh_token'", $joined);
        } finally {
            Database::factory('pdo_sqlite');
        }
    }

    public function testConvertSqlServerColumnsToUnicodeRecordsNothingForOtherDatabases(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $method = new ReflectionMethod($this->migration, 'convertSqlServerColumnsToUnicode');
        $method->invoke($this->migration, $recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }

    public function testUpRecordsUnicodeColumnConversionsOnSqlServer(): void
    {
        Database::factory('pdo_sqlsrv');

        try {
            $migration = new Migration420Alpha2($this->configuration);
            $recorder = new OperationRecorder($this->configuration);
            $migration->up($recorder);

            $this->assertStringContainsString(
                'ALTER TABLE faqseo ALTER COLUMN title NVARCHAR(MAX) NULL',
                implode("\n", $recorder->getSqlQueries()),
            );
        } finally {
            Database::factory('pdo_sqlite');
        }
    }
}
