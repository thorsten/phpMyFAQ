<?php

declare(strict_types=1);

namespace phpMyFAQ\Database;

use phpMyFAQ\Setup\Installation\DatabaseSchema;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The drivers list the phpMyFAQ tables by hand, and backups are built from that list: a table
 * missing there is silently left out of every backup. This test fails as soon as a table of the
 * installation schema is not listed by a driver.
 *
 * The drivers are created without running their constructor and never connect, so the lists of
 * all eight are checked, whether or not the database extension is installed.
 */
#[CoversClass(Mysqli::class)]
#[CoversClass(PdoMysql::class)]
#[CoversClass(PdoPgsql::class)]
#[CoversClass(PdoSqlite::class)]
#[CoversClass(PdoSqlsrv::class)]
#[CoversClass(Pgsql::class)]
#[CoversClass(Sqlite3::class)]
#[CoversClass(Sqlsrv::class)]
#[UsesClass(\phpMyFAQ\Database::class)]
#[UsesNamespace('phpMyFAQ\\Setup')]
final class DriverTableNamesTest extends TestCase
{
    /**
     * @return array<string, array{class-string<DatabaseDriver>}>
     */
    public static function driverProvider(): array
    {
        return [
            'mysqli' => [Mysqli::class],
            'pdo_mysql' => [PdoMysql::class],
            'pdo_pgsql' => [PdoPgsql::class],
            'pdo_sqlite' => [PdoSqlite::class],
            'pdo_sqlsrv' => [PdoSqlsrv::class],
            'pgsql' => [Pgsql::class],
            'sqlite3' => [Sqlite3::class],
            'sqlsrv' => [Sqlsrv::class],
        ];
    }

    /**
     * @param class-string<DatabaseDriver> $driverClass
     */
    #[DataProvider('driverProvider')]
    public function testDriverListsEveryTableOfTheInstallationSchema(string $driverClass): void
    {
        $driver = new ReflectionClass($driverClass)->newInstanceWithoutConstructor();
        $schemaTables = new DatabaseSchema(new SqliteDialect())->getTableNames();

        $listedTables = $driver->getTableNames();

        $this->assertSame(
            [],
            array_values(array_diff($schemaTables, $listedTables)),
            'Tables of the installation schema missing from ' . $driverClass . '::getTableNames()',
        );
        $this->assertSame(
            [],
            array_values(array_diff($listedTables, $schemaTables)),
            'Tables listed by ' . $driverClass . '::getTableNames() that the installation schema does not define',
        );
        $this->assertSame(array_unique($listedTables), $listedTables, 'A table is listed twice.');
    }
}
