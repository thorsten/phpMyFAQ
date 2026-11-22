<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\QueryBuilder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexName::class)]
final class IndexNameTest extends TestCase
{
    #[DataProvider('databasesThatShareIndexNamesBetweenTables')]
    public function testCarriesTheTablePrefixWhereIndexNamesAreSharedBetweenTables(string $databaseType): void
    {
        $this->assertSame('idx_hash_pmf_', IndexName::forTablePrefix('idx_hash', $databaseType, 'pmf_'));
    }

    #[DataProvider('databasesThatShareIndexNamesBetweenTables')]
    public function testKeepsTheNameOfAnInstallationWithoutTablePrefix(string $databaseType): void
    {
        $this->assertSame('idx_hash', IndexName::forTablePrefix('idx_hash', $databaseType, ''));
    }

    #[DataProvider('databasesThatScopeIndexNamesToTheTable')]
    public function testKeepsTheNameWhereTheIndexNameBelongsToItsTable(string $databaseType): void
    {
        $this->assertSame('idx_hash', IndexName::forTablePrefix('idx_hash', $databaseType, 'pmf_'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function databasesThatShareIndexNamesBetweenTables(): iterable
    {
        yield 'pgsql' => ['pgsql'];
        yield 'pdo_pgsql' => ['pdo_pgsql'];
        yield 'sqlite3' => ['sqlite3'];
        yield 'pdo_sqlite' => ['pdo_sqlite'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function databasesThatScopeIndexNamesToTheTable(): iterable
    {
        yield 'mysqli' => ['mysqli'];
        yield 'pdo_mysql' => ['pdo_mysql'];
        yield 'sqlsrv' => ['sqlsrv'];
        yield 'pdo_sqlsrv' => ['pdo_sqlsrv'];
    }
}
