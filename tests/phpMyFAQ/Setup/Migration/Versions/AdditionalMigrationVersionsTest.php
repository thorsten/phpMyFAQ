<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class AdditionalMigrationVersionsTest extends TestCase
{
    #[DataProvider('metadataProvider')]
    public function testMigrationMetadata(
        string $className,
        string $version,
        array $dependencies,
        string $descriptionFragment,
    ): void {
        $this->setDatabaseState('sqlite3');

        $migration = new $className($this->createStub(Configuration::class));

        $this->assertInstanceOf(MigrationInterface::class, $migration);
        $this->assertSame($version, $migration->getVersion());
        $this->assertSame($dependencies, $migration->getDependencies());
        $this->assertStringContainsString($descriptionFragment, $migration->getDescription());
        $this->assertFalse($migration->isReversible());
        $this->assertSame(64, strlen($migration->getChecksum()));
    }

    public static function metadataProvider(): array
    {
        return [
            '320beta' => [Migration320Beta::class, '3.2.0-beta', ['3.2.0-alpha'], 'SMTP TLS'],
            '320beta2' => [Migration320Beta2::class, '3.2.0-beta.2', ['3.2.0-beta'], 'contact'],
            '320rc' => [Migration320RC::class, '3.2.0-RC', ['3.2.0-beta.2'], 'mail address'],
            '323' => [Migration323::class, '3.2.3', ['3.2.0-RC'], 'IPv6'],
            '400alpha' => [Migration400Alpha::class, '4.0.0-alpha', ['3.2.3'], 'bookmarks'],
            '400alpha2' => [Migration400Alpha2::class, '4.0.0-alpha.2', ['4.0.0-alpha'], 'Forms table'],
            '400alpha3' => [Migration400Alpha3::class, '4.0.0-alpha.3', ['4.0.0-alpha.2'], 'SEO table'],
            '400beta2' => [Migration400Beta2::class, '4.0.0-beta.2', ['4.0.0-alpha.3'], 'WebAuthn'],
            '405' => [Migration405::class, '4.0.5', ['4.0.0-beta.2'], 'forms input_label'],
            '407' => [Migration407::class, '4.0.7', ['4.0.5'], 'language codes'],
            '409' => [Migration409::class, '4.0.9', ['4.0.7'], 'faqseo table'],
            '410alpha' => [Migration410Alpha::class, '4.1.0-alpha', ['4.0.9'], 'robots.txt'],
            '410alpha2' => [Migration410Alpha2::class, '4.1.0-alpha.2', ['4.1.0-alpha'], 'OpenSearch'],
            '410alpha3' => [Migration410Alpha3::class, '4.1.0-alpha.3', ['4.1.0-alpha.2'], 'LLMs.txt'],
        ];
    }

    public function testMigration320BetaRecordsSqliteRebuildOperations(): void
    {
        $this->setDatabaseState('sqlite3', 'pmf_');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration320Beta($this->createStub(Configuration::class)))->up($recorder);

        $counts = $recorder->getOperationCounts();

        $this->assertSame(1, $counts['config_add'] ?? 0);
        $this->assertSame(1, $counts['config_delete'] ?? 0);
        $this->assertGreaterThanOrEqual(12, $counts['sql'] ?? 0);
        $this->assertStringContainsString('faqdata_new', implode("\n", $recorder->getSqlQueries()));
        $this->assertStringContainsString('faqconfig_new', implode("\n", $recorder->getSqlQueries()));
    }

    public function testMigration323RecordsPostgresSpecificSql(): void
    {
        $this->setDatabaseState('pgsql', 'pmf_');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration323($this->createStub(Configuration::class)))->up($recorder);

        $queries = $recorder->getSqlQueries();

        $this->assertCount(1, $queries);
        $this->assertStringContainsString('ALTER COLUMN ip TYPE VARCHAR(64)', $queries[0]);
    }

    public function testMigration400AlphaRecordsConfigAndBookmarkOperations(): void
    {
        $this->setDatabaseState('sqlite3');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration400Alpha($this->createStub(Configuration::class)))->up($recorder);

        $counts = $recorder->getOperationCounts();
        $payload = $recorder->toArray();
        $serialized = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->assertGreaterThanOrEqual(6, $counts['config_add'] ?? 0);
        $this->assertGreaterThanOrEqual(6, $counts['config_delete'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $counts['sql'] ?? 0);
        $this->assertStringContainsString('faqbookmarks', $serialized);
        $this->assertStringContainsString('records.orderStickyFaqsCustom', $serialized);
    }

    public function testMigration400Alpha3RecordsPostgresSeoAndConfigOperations(): void
    {
        $this->setDatabaseState('pgsql');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration400Alpha3($this->createStub(Configuration::class)))->up($recorder);

        $counts = $recorder->getOperationCounts();
        $payload = json_encode($recorder->toArray(), JSON_THROW_ON_ERROR);

        $this->assertSame(1, $counts['sql'] ?? 0);
        $this->assertSame(1, $counts['config_update'] ?? 0);
        $this->assertSame(7, $counts['config_add'] ?? 0);
        $this->assertSame(4, $counts['config_delete'] ?? 0);
        $this->assertSame(3, $counts['config_rename'] ?? 0);
        $this->assertStringContainsString('faqseo', $payload);
        $this->assertStringContainsString('layout.templateSet', $payload);
    }

    public function testMigration405RecordsSqliteTableRebuild(): void
    {
        $this->setDatabaseState('sqlite3', 'pmf_');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration405($this->createStub(Configuration::class)))->up($recorder);

        $queries = implode("\n", $recorder->getSqlQueries());

        $this->assertStringContainsString("DELETE FROM pmf_faqright WHERE name = 'view_sections'", $queries);
        $this->assertStringContainsString('ALTER TABLE pmf_faqforms RENAME TO pmf_faqforms_old', $queries);
        $this->assertStringContainsString('CREATE TABLE pmf_faqforms', $queries);
    }

    public function testMigration407AddsLanguageNormalizationQueries(): void
    {
        $this->setDatabaseState('sqlite3', 'pmf_');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration407($this->createStub(Configuration::class)))->up($recorder);

        $queries = $recorder->getSqlQueries();

        $this->assertCount(28, $queries);
        $this->assertStringContainsString("SET lang='fr_ca' WHERE lang='fr-ca'", $queries[2]);
        $this->assertStringContainsString("language_pt_br.php", $queries[27]);
    }

    public function testMigration409OnlyAddsPostgresSequenceOperations(): void
    {
        $this->setDatabaseState('pgsql', 'pmf_');

        $postgresRecorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration409($this->createStub(Configuration::class)))->up($postgresRecorder);
        $this->assertCount(4, $postgresRecorder->getSqlQueries());

        $this->setDatabaseState('sqlite3', 'pmf_');

        $sqliteRecorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration409($this->createStub(Configuration::class)))->up($sqliteRecorder);
        $this->assertCount(0, $sqliteRecorder->getSqlQueries());
    }

    public function testMigration410Alpha3AddsConfigsAndIndexes(): void
    {
        $this->setDatabaseState('sqlite3');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        (new Migration410Alpha3($this->createStub(Configuration::class)))->up($recorder);

        $counts = $recorder->getOperationCounts();
        $payload = json_encode($recorder->toArray(), JSON_THROW_ON_ERROR);

        $this->assertSame(6, $counts['config_add'] ?? 0);
        $this->assertSame(3, $counts['sql'] ?? 0);
        $this->assertStringContainsString('seo.contentLlmsText', $payload);
        $this->assertStringContainsString('idx_faqsearches_date_term_lang', $payload);
    }

    private function setDatabaseState(string $type, string $tablePrefix = ''): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, $type);
        Database::setTablePrefix($tablePrefix);
    }
}
