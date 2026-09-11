<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class Migration419Test extends TestCase
{
    protected function tearDown(): void
    {
        $this->setDatabaseState('sqlite3');

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function dialectProvider(): array
    {
        return [
            'mysqli' => ['mysqli', 'ALTER TABLE pmf_faqvisits MODIFY visits INT(11) NOT NULL'],
            'pdo_mysql' => ['pdo_mysql', 'ALTER TABLE pmf_faqvisits MODIFY visits INT(11) NOT NULL'],
            'pgsql' => ['pgsql', 'ALTER TABLE pmf_faqvisits ALTER COLUMN visits TYPE INTEGER'],
            'pdo_pgsql' => ['pdo_pgsql', 'ALTER TABLE pmf_faqvisits ALTER COLUMN visits TYPE INTEGER'],
            'sqlsrv' => ['sqlsrv', 'ALTER TABLE pmf_faqvisits ALTER COLUMN visits INTEGER NOT NULL'],
            'pdo_sqlsrv' => ['pdo_sqlsrv', 'ALTER TABLE pmf_faqvisits ALTER COLUMN visits INTEGER NOT NULL'],
        ];
    }

    #[DataProvider('dialectProvider')]
    public function testWidensVisitsColumn(string $dbType, string $expectedSql): void
    {
        $this->setDatabaseState($dbType, 'pmf_');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        new Migration419($this->createStub(Configuration::class))->up($recorder);

        $this->assertSame([$expectedSql], $recorder->getSqlQueries());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sqliteProvider(): array
    {
        return [
            'sqlite3' => ['sqlite3'],
            'pdo_sqlite' => ['pdo_sqlite'],
        ];
    }

    #[DataProvider('sqliteProvider')]
    public function testSkipsSqliteBecauseSmallintHasNoRangeLimit(string $dbType): void
    {
        $this->setDatabaseState($dbType, 'pmf_');

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        new Migration419($this->createStub(Configuration::class))->up($recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }

    private function setDatabaseState(string $dbType, string $prefix = ''): void
    {
        $reflection = new ReflectionClass(Database::class);
        $reflection->getProperty('dbType')->setValue(null, $dbType);
        Database::setTablePrefix($prefix);
    }
}
