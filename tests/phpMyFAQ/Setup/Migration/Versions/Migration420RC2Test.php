<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\ConfigAddOperation;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\TestCase;

class Migration420RC2Test extends TestCase
{
    private Migration420RC2 $migration;

    private Configuration $configuration;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        Database::factory('pdo_sqlite');
        Database::setTablePrefix('');

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-migration-420-rc2-');
        self::assertNotFalse($this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->migration = new Migration420RC2($this->configuration);
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
        $this->assertSame('4.2.0-RC.2', $this->migration->getVersion());
    }

    public function testDependsOnTheRC1Migration(): void
    {
        $this->assertSame(['4.2.0-RC.1'], $this->migration->getDependencies());
    }

    public function testGetDescriptionNamesBothColumns(): void
    {
        $description = $this->migration->getDescription();

        $this->assertStringContainsString('password_reset_token', $description);
        $this->assertStringContainsString('password_reset_expires', $description);
    }

    public function testGetDescriptionNamesTheHeroImageConfiguration(): void
    {
        $this->assertStringContainsString('layout.heroImage', $this->migration->getDescription());
    }

    public function testUpAddsTheMissingColumns(): void
    {
        $this->configuration->getDb()->query('CREATE TABLE faquser (user_id INTEGER)');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $statements = $recorder->getSqlQueries();

        $this->assertCount(2, $statements);
        $this->assertStringContainsString('password_reset_token', $statements[0]);
        $this->assertStringContainsString('password_reset_expires', $statements[1]);
    }

    public function testUpAddsTheHeroImageConfiguration(): void
    {
        $this->configuration->getDb()->query('CREATE TABLE faquser (user_id INTEGER)');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $added = [];
        foreach ($recorder->getOperations() as $operation) {
            if ($operation instanceof ConfigAddOperation) {
                $added[$operation->getKey()] = $operation->getValue();
            }
        }

        $this->assertSame(['layout.heroImage' => ''], $added);
    }

    public function testUpIsIdempotentWhenTheColumnsExist(): void
    {
        $this->configuration
            ->getDb()
            ->query(
                'CREATE TABLE faquser (user_id INTEGER, password_reset_token VARCHAR(64), password_reset_expires INTEGER)',
            );

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }
}
