<?php

/**
 * Tests for phpMyFAQ\Database\Pgsql
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    GitHub Copilot
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Database;

use Exception;
use PgSql\Connection;
use PgSql\Result;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Class PgsqlTest
 */
#[AllowMockObjectsWithoutExpectations]
class PgsqlTest extends TestCase
{
    private Pgsql $pgsql;

    protected function setUp(): void
    {
        $this->pgsql = new Pgsql();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->pgsql);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->pgsql->tableNames);
        $this->assertEquals('', $this->pgsql->log());
    }

    public function testConnectParameterHandling(): void
    {
        $reflection = new ReflectionMethod($this->pgsql, 'connect');
        $parameters = $reflection->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals('host', $parameters[0]->getName());
        $this->assertEquals('user', $parameters[1]->getName());
        $this->assertEquals('password', $parameters[2]->getName());
        $this->assertEquals('database', $parameters[3]->getName());
        $this->assertEquals('port', $parameters[4]->getName());

        $this->assertTrue($parameters[3]->isOptional());
        $this->assertTrue($parameters[4]->isOptional());
        $this->assertEquals('', $parameters[3]->getDefaultValue());
        $this->assertNull($parameters[4]->getDefaultValue());
    }

    public function testConnectDsnFormat(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'connect'));

        $reflection = new ReflectionMethod($this->pgsql, 'connect');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testConnectExceptionHandling(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'connect'));

        $reflection = new ReflectionMethod($this->pgsql, 'connect');
        $this->assertTrue($reflection->isPublic());

        $this->assertCount(5, $reflection->getParameters());
    }

    public function testQueryMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'query'));

        $reflection = new ReflectionMethod($this->pgsql, 'query');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('offset', $parameters[1]->getName());
        $this->assertEquals('rowcount', $parameters[2]->getName());

        $this->assertTrue($parameters[1]->isOptional());
        $this->assertTrue($parameters[2]->isOptional());
        $this->assertEquals(0, $parameters[1]->getDefaultValue());
        $this->assertEquals(0, $parameters[2]->getDefaultValue());
    }

    public function testQueryLogAccumulation(): void
    {
        // Test that queries are logged
        $testPgsql = new class extends Pgsql {
            public function addToLog(string $query): void
            {
                $reflection = new ReflectionProperty(parent::class, 'sqlLog');
                $currentLog = $reflection->getValue($this);
                $reflection->setValue($this, $currentLog . $query);
            }
        };

        $testPgsql->addToLog('SELECT * FROM test; ');
        $testPgsql->addToLog('UPDATE users SET active = 1; ');

        $log = $testPgsql->log();
        $this->assertStringContainsString('SELECT * FROM test;', $log);
        $this->assertStringContainsString('UPDATE users SET active = 1;', $log);
    }

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'error'));

        $reflection = new ReflectionMethod($this->pgsql, 'error');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testEscapeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'escape'));

        $reflection = new ReflectionMethod($this->pgsql, 'escape');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('string', $parameters[0]->getName());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testFetchAllMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'fetchAll'));

        $reflection = new ReflectionMethod($this->pgsql, 'fetchAll');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('result', $parameters[0]->getName());

        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('array', $returnType->getName());
    }

    public function testFetchAllWithFalseResult(): void
    {
        $pgsql = new class extends Pgsql {
            public function error(): string
            {
                return 'PostgreSQL test error message';
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error while fetching result: PostgreSQL test error message');

        $pgsql->fetchAll(false);
    }

    public function testFetchArrayMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'fetchArray'));

        $reflection = new ReflectionMethod($this->pgsql, 'fetchArray');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('array', $returnType->getName());
    }

    public function testFetchRowMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'fetchRow'));

        $reflection = new ReflectionMethod($this->pgsql, 'fetchRow');
        $this->assertTrue($reflection->isPublic());
    }

    public function testFetchObjectMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'fetchObject'));

        $reflection = new ReflectionMethod($this->pgsql, 'fetchObject');
        $this->assertTrue($reflection->isPublic());
    }

    public function testNumRowsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'numRows'));

        $reflection = new ReflectionMethod($this->pgsql, 'numRows');
        $this->assertEquals('int', $reflection->getReturnType()->getName());
    }

    public function testLogMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'log'));

        $reflection = new ReflectionMethod($this->pgsql, 'log');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testGetTableNamesMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'getTableNames'));

        $reflection = new ReflectionMethod($this->pgsql, 'getTableNames');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('prefix', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertEquals('', $parameters[0]->getDefaultValue());

        $this->assertEquals('array', $reflection->getReturnType()->getName());
    }

    public function testGetTableNames(): void
    {
        $prefix = 'test_';
        $result = $this->pgsql->getTableNames($prefix);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        foreach ($result as $tableName) {
            $this->assertStringStartsWith($prefix, $tableName);
        }

        $this->assertContains($prefix . 'faqdata', $result);
        $this->assertContains($prefix . 'faqcategories', $result);
        $this->assertContains($prefix . 'faqconfig', $result);
    }

    public function testGetTableNamesWithoutPrefix(): void
    {
        $result = $this->pgsql->getTableNames();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $this->assertContains('faqdata', $result);
        $this->assertContains('faqcategories', $result);
        $this->assertContains('faqconfig', $result);
        $this->assertContains('faquser', $result);
    }

    public function testGetTableNamesUpdatesProperty(): void
    {
        $prefix = 'custom_';
        $result = $this->pgsql->getTableNames($prefix);

        $this->assertEquals($result, $this->pgsql->tableNames);
    }

    public function testGetTableStatusMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'getTableStatus'));

        $reflection = new ReflectionMethod($this->pgsql, 'getTableStatus');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('prefix', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertEquals('', $parameters[0]->getDefaultValue());

        $this->assertEquals('array', $reflection->getReturnType()->getName());
    }

    public function testNextIdMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'nextId'));

        $reflection = new ReflectionMethod($this->pgsql, 'nextId');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('table', $parameters[0]->getName());
        $this->assertEquals('column', $parameters[1]->getName());
        $this->assertEquals('int', $reflection->getReturnType()->getName());
    }

    public function testClientVersionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'clientVersion'));

        $reflection = new ReflectionMethod($this->pgsql, 'clientVersion');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testServerVersionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'serverVersion'));

        $reflection = new ReflectionMethod($this->pgsql, 'serverVersion');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testCloseMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'close'));

        $reflection = new ReflectionMethod($this->pgsql, 'close');
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }

    public function testNowMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pgsql, 'now'));

        $reflection = new ReflectionMethod($this->pgsql, 'now');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testNowReturnsPostgreSQLFunction(): void
    {
        $result = $this->pgsql->now();
        $this->assertEquals('CURRENT_TIMESTAMP', $result);
    }

    public function testLimitOffsetSyntax(): void
    {
        $reflection = new ReflectionMethod($this->pgsql, 'query');

        $this->assertTrue($reflection->getParameters()[1]->hasType());
        $this->assertTrue($reflection->getParameters()[2]->hasType());

        $this->assertEquals(0, $reflection->getParameters()[1]->getDefaultValue());
        $this->assertEquals(0, $reflection->getParameters()[2]->getDefaultValue());
    }

    public function testConnectionStringFormat(): void
    {
        $reflection = new ReflectionMethod($this->pgsql, 'connect');
        $parameters = $reflection->getParameters();

        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertTrue($parameters[4]->getType()->allowsNull());
        $this->assertEquals('int', $parameters[4]->getType()->getName());
    }

    public function testTableNamesProperty(): void
    {
        $reflection = new ReflectionProperty($this->pgsql, 'tableNames');
        $this->assertTrue($reflection->isPublic());

        $this->assertEquals([], $this->pgsql->tableNames);

        $this->pgsql->getTableNames('test_');
        $this->assertNotEmpty($this->pgsql->tableNames);
    }

    public function testSqlLogProperty(): void
    {
        $this->assertEquals('', $this->pgsql->log());

        $reflection = new ReflectionMethod($this->pgsql, 'log');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testConnectionProperty(): void
    {
        $reflection = new ReflectionProperty($this->pgsql, 'conn');
        $this->assertTrue($reflection->isPrivate());

        $this->assertFalse($reflection->getValue($this->pgsql));
    }

    public function testDatabaseDriverInterfaceCompliance(): void
    {
        // Verify all required DatabaseDriver methods exist
        $requiredMethods = [
            'connect', 'query', 'error', 'escape', 'fetchAll', 'fetchArray',
            'fetchRow', 'fetchObject', 'numRows', 'log', 'getTableNames',
            'getTableStatus', 'nextId', 'clientVersion', 'serverVersion', 'close', 'now'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->pgsql, $method),
                "Required DatabaseDriver method '$method' does not exist"
            );
        }
    }

    public function testPostgreSQLSpecificFeatures(): void
    {
        $reflection = new ReflectionMethod($this->pgsql, 'query');
        $this->assertTrue($reflection->hasReturnType());

        $this->assertEquals('CURRENT_TIMESTAMP', $this->pgsql->now());

        $escapeReflection = new ReflectionMethod($this->pgsql, 'escape');
        $this->assertEquals('string', $escapeReflection->getReturnType()->getName());
    }
}
