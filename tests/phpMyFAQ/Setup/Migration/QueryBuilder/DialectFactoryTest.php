<?php

namespace phpMyFAQ\Setup\Migration\QueryBuilder;

use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\MysqlDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\PostgresDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqlServerDialect;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DialectFactoryTest extends TestCase
{
    public function testCreateForTypeMysqli(): void
    {
        $dialect = DialectFactory::createForType('mysqli');

        $this->assertInstanceOf(MysqlDialect::class, $dialect);
    }

    public function testCreateForTypePdoMysql(): void
    {
        $dialect = DialectFactory::createForType('pdo_mysql');

        $this->assertInstanceOf(MysqlDialect::class, $dialect);
    }

    public function testCreateForTypePgsql(): void
    {
        $dialect = DialectFactory::createForType('pgsql');

        $this->assertInstanceOf(PostgresDialect::class, $dialect);
    }

    public function testCreateForTypePdoPgsql(): void
    {
        $dialect = DialectFactory::createForType('pdo_pgsql');

        $this->assertInstanceOf(PostgresDialect::class, $dialect);
    }

    public function testCreateForTypeSqlite3(): void
    {
        $dialect = DialectFactory::createForType('sqlite3');

        $this->assertInstanceOf(SqliteDialect::class, $dialect);
    }

    public function testCreateForTypePdoSqlite(): void
    {
        $dialect = DialectFactory::createForType('pdo_sqlite');

        $this->assertInstanceOf(SqliteDialect::class, $dialect);
    }

    public function testCreateForTypeSqlsrv(): void
    {
        $dialect = DialectFactory::createForType('sqlsrv');

        $this->assertInstanceOf(SqlServerDialect::class, $dialect);
    }

    public function testCreateForTypePdoSqlsrv(): void
    {
        $dialect = DialectFactory::createForType('pdo_sqlsrv');

        $this->assertInstanceOf(SqlServerDialect::class, $dialect);
    }

    public function testCreateForTypeInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported database type: invalid');

        DialectFactory::createForType('invalid');
    }
}
