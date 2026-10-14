<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class Migration4111Test extends TestCase
{
    protected function tearDown(): void
    {
        $this->setDatabaseState('sqlite3');

        parent::tearDown();
    }

    public function testMetadata(): void
    {
        $this->setDatabaseState('sqlite3');

        $migration = new Migration4111($this->createStub(Configuration::class));

        $this->assertInstanceOf(MigrationInterface::class, $migration);
        $this->assertSame('4.1.11', $migration->getVersion());
        $this->assertSame(['4.1.9'], $migration->getDependencies());
        $this->assertStringContainsString('session', $migration->getDescription());
        $this->assertFalse($migration->isReversible());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function mysqlProvider(): array
    {
        return [
            'mysqli' => ['mysqli'],
            'pdo_mysql' => ['pdo_mysql'],
        ];
    }

    /**
     * Installations created before v3.x carry a legacy UNIQUE index "session" on
     * faquser.session_id that was never dropped by a migration (see #4764).
     */
    #[DataProvider('mysqlProvider')]
    public function testDropsLegacySessionIndexOnMysql(string $dbType): void
    {
        $this->setDatabaseState($dbType, 'faq_');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        new Migration4111($this->configurationWithIndexCount(1))->up($recorder);

        $this->assertSame(['ALTER TABLE faq_faquser DROP INDEX session'], $recorder->getSqlQueries());
    }

    #[DataProvider('mysqlProvider')]
    public function testSkipsMysqlWithoutLegacySessionIndex(string $dbType): void
    {
        $this->setDatabaseState($dbType);

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        new Migration4111($this->configurationWithIndexCount(0))->up($recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonMysqlProvider(): array
    {
        return [
            'pgsql' => ['pgsql'],
            'pdo_pgsql' => ['pdo_pgsql'],
            'sqlite3' => ['sqlite3'],
            'pdo_sqlite' => ['pdo_sqlite'],
            'sqlsrv' => ['sqlsrv'],
            'pdo_sqlsrv' => ['pdo_sqlsrv'],
        ];
    }

    #[DataProvider('nonMysqlProvider')]
    public function testSkipsNonMysqlDatabasesWithoutQueryingThem(string $dbType): void
    {
        $this->setDatabaseState($dbType);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->never())->method('getDb');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        new Migration4111($configuration)->up($recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }

    private function configurationWithIndexCount(int $count): Configuration
    {
        $database = $this->createStub(Sqlite3::class);
        $database->method('query')->willReturn(true);
        $database->method('numRows')->willReturn($count);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($database);

        return $configuration;
    }

    private function setDatabaseState(string $dbType, string $prefix = ''): void
    {
        $reflection = new ReflectionClass(Database::class);
        $reflection->getProperty('dbType')->setValue(null, $dbType);
        Database::setTablePrefix($prefix);
    }
}
