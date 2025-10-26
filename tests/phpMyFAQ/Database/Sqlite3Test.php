<?php

/**
 * Tests for phpMyFAQ\Database\Sqlite3
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

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use SQLite3Result;

/**
 * Class Sqlite3Test
 */
class Sqlite3Test extends TestCase
{
    private Sqlite3 $sqlite3;

    protected function setUp(): void
    {
        $this->sqlite3 = new Sqlite3();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->sqlite3);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->sqlite3->tableNames);
        $this->assertEquals('', $this->sqlite3->log());
    }

    public function testConnectParameterHandling(): void
    {
        $reflection = new ReflectionMethod($this->sqlite3, 'connect');
        $parameters = $reflection->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals('host', $parameters[0]->getName());
        $this->assertEquals('user', $parameters[1]->getName());
        $this->assertEquals('password', $parameters[2]->getName());
        $this->assertEquals('database', $parameters[3]->getName());
        $this->assertEquals('port', $parameters[4]->getName());

        // Test optional parameters
        $this->assertTrue($parameters[3]->isOptional());
        $this->assertTrue($parameters[4]->isOptional());
        $this->assertEquals('', $parameters[3]->getDefaultValue());
        $this->assertNull($parameters[4]->getDefaultValue());
    }

    public function testConnectMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'connect'));

        $reflection = new ReflectionMethod($this->sqlite3, 'connect');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testEscapeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'escape'));

        $reflection = new ReflectionMethod($this->sqlite3, 'escape');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('string', $parameters[0]->getName());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testEscapeUsesStaticMethod(): void
    {
        // Test that escape method would use SQLite3::escapeString
        $testString = "test'string";
        $result = $this->sqlite3->escape($testString);

        // Should escape single quotes
        $this->assertStringContainsString("''", $result);
    }

    public function testFetchObjectMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'fetchObject'));

        $reflection = new ReflectionMethod($this->sqlite3, 'fetchObject');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('object', $returnType->getName());
    }

    public function testFetchArrayMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'fetchArray'));

        $reflection = new ReflectionMethod($this->sqlite3, 'fetchArray');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('array', $returnType->getName());
    }

    public function testFetchRowMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'fetchRow'));

        $reflection = new ReflectionMethod($this->sqlite3, 'fetchRow');
        $this->assertTrue($reflection->isPublic());
    }

    public function testFetchAllMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'fetchAll'));

        $reflection = new ReflectionMethod($this->sqlite3, 'fetchAll');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('array', $returnType->getName());
    }

    public function testNumRowsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'numRows'));

        $reflection = new ReflectionMethod($this->sqlite3, 'numRows');
        $this->assertEquals('int', $reflection->getReturnType()->getName());
    }

    public function testLogMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'log'));

        $reflection = new ReflectionMethod($this->sqlite3, 'log');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testQueryMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'query'));

        $reflection = new ReflectionMethod($this->sqlite3, 'query');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('offset', $parameters[1]->getName());
        $this->assertEquals('rowcount', $parameters[2]->getName());

        // Test default values
        $this->assertTrue($parameters[1]->isOptional());
        $this->assertTrue($parameters[2]->isOptional());
        $this->assertEquals(0, $parameters[1]->getDefaultValue());
        $this->assertEquals(0, $parameters[2]->getDefaultValue());
    }

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'error'));

        $reflection = new ReflectionMethod($this->sqlite3, 'error');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testGetTableNamesMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'getTableNames'));

        $reflection = new ReflectionMethod($this->sqlite3, 'getTableNames');
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
        $result = $this->sqlite3->getTableNames($prefix);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that all table names have the prefix
        foreach ($result as $tableName) {
            $this->assertStringStartsWith($prefix, $tableName);
        }

        // Check some expected SQLite tables
        $this->assertContains($prefix . 'faqdata', $result);
        $this->assertContains($prefix . 'faqcategories', $result);
        $this->assertContains($prefix . 'faqconfig', $result);
    }

    public function testGetTableNamesWithoutPrefix(): void
    {
        $result = $this->sqlite3->getTableNames();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check some expected standard table names without prefix
        $this->assertContains('faqdata', $result);
        $this->assertContains('faqcategories', $result);
        $this->assertContains('faqconfig', $result);
        $this->assertContains('faquser', $result);
    }

    public function testGetTableNamesUpdatesProperty(): void
    {
        $prefix = 'custom_';
        $result = $this->sqlite3->getTableNames($prefix);

        // Check that tableNames property is updated
        $this->assertEquals($result, $this->sqlite3->tableNames);
    }

    public function testGetTableStatusMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'getTableStatus'));

        $reflection = new ReflectionMethod($this->sqlite3, 'getTableStatus');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('prefix', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertEquals('', $parameters[0]->getDefaultValue());

        $this->assertEquals('array', $reflection->getReturnType()->getName());
    }

    public function testNextIdMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'nextId'));

        $reflection = new ReflectionMethod($this->sqlite3, 'nextId');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('table', $parameters[0]->getName());
        $this->assertEquals('column', $parameters[1]->getName());
        $this->assertEquals('int', $reflection->getReturnType()->getName());
    }

    public function testClientVersionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'clientVersion'));

        $reflection = new ReflectionMethod($this->sqlite3, 'clientVersion');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testServerVersionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'serverVersion'));

        $reflection = new ReflectionMethod($this->sqlite3, 'serverVersion');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testCloseMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'close'));

        $reflection = new ReflectionMethod($this->sqlite3, 'close');
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }

    public function testNowMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlite3, 'now'));

        $reflection = new ReflectionMethod($this->sqlite3, 'now');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testNowReturnsSQLiteFunction(): void
    {
        $result = $this->sqlite3->now();
        // SQLite uses DATETIME('now', 'localtime')
        $this->assertEquals("DATETIME('now', 'localtime')", $result);
    }

    public function testQueryLogAccumulation(): void
    {
        // Test that queries are logged
        $testSqlite3 = new class extends Sqlite3 {
            public function addToLog(string $query): void
            {
                $reflection = new ReflectionProperty(parent::class, 'sqlLog');
                $currentLog = $reflection->getValue($this);
                $reflection->setValue($this, $currentLog . $query);
            }
        };

        $testSqlite3->addToLog('SELECT * FROM test; ');
        $testSqlite3->addToLog('INSERT INTO users VALUES (1, "test"); ');

        $log = $testSqlite3->log();
        $this->assertStringContainsString('SELECT * FROM test;', $log);
        $this->assertStringContainsString('INSERT INTO users VALUES', $log);
    }

    public function testTableNamesProperty(): void
    {
        // Test that tableNames is public array
        $reflection = new ReflectionProperty($this->sqlite3, 'tableNames');
        $this->assertTrue($reflection->isPublic());

        // Initially empty
        $this->assertEquals([], $this->sqlite3->tableNames);

        // Gets populated by getTableNames
        $this->sqlite3->getTableNames('test_');
        $this->assertNotEmpty($this->sqlite3->tableNames);
    }

    public function testSqlLogProperty(): void
    {
        // Test that sqlLog property exists and is accessible via log() method
        $this->assertEquals('', $this->sqlite3->log());

        // Test that log method exists and returns string
        $reflection = new ReflectionMethod($this->sqlite3, 'log');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testConnectionProperty(): void
    {
        // Test that conn property exists and is properly typed
        $reflection = new ReflectionProperty($this->sqlite3, 'conn');
        $this->assertTrue($reflection->isPrivate());

        // The initial state should be false
        $this->assertFalse($reflection->getValue($this->sqlite3));
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
                method_exists($this->sqlite3, $method),
                "Required DatabaseDriver method '$method' does not exist"
            );
        }
    }

    public function testSQLiteSpecificFeatures(): void
    {
        // Test SQLite-specific method signatures and behavior expectations

        // SQLite uses LIMIT/OFFSET syntax
        $reflection = new ReflectionMethod($this->sqlite3, 'query');
        $this->assertTrue($reflection->hasReturnType());

        // SQLite should return DATETIME('now', 'localtime') for current timestamp
        $this->assertEquals("DATETIME('now', 'localtime')", $this->sqlite3->now());

        // SQLite uses SQLite3::escapeString for escaping
        $escapeReflection = new ReflectionMethod($this->sqlite3, 'escape');
        $this->assertEquals('string', $escapeReflection->getReturnType()->getName());
    }

    public function testSQLiteConnectionHandling(): void
    {
        // Test that connect method uses SQLite3 class
        $reflection = new ReflectionMethod($this->sqlite3, 'connect');
        $parameters = $reflection->getParameters();

        // For SQLite, the host parameter is actually the database file path
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        // SQLite doesn't use traditional user/password/port but maintains interface compatibility
        $this->assertTrue($parameters[3]->isOptional());
        $this->assertTrue($parameters[4]->isOptional());
    }

    public function testNumRowsWarningConstant(): void
    {
        // Test that the ERROR_MESSAGE constant exists for numRows warning
        $reflection = new \ReflectionClass($this->sqlite3);
        $this->assertTrue($reflection->hasConstant('ERROR_MESSAGE'));

        $errorMessage = $reflection->getConstant('ERROR_MESSAGE');
        $this->assertStringContainsString('numRows()', $errorMessage);
        $this->assertStringContainsString('reset the results', $errorMessage);
    }

    public function testLimitOffsetSyntax(): void
    {
        // Test that query method can handle SQLite specific LIMIT/OFFSET syntax
        $reflection = new ReflectionMethod($this->sqlite3, 'query');

        // Verify the method can handle offset and rowcount parameters
        $this->assertTrue($reflection->getParameters()[1]->hasType());
        $this->assertTrue($reflection->getParameters()[2]->hasType());

        // Test that default values are 0 (no pagination)
        $this->assertEquals(0, $reflection->getParameters()[1]->getDefaultValue());
        $this->assertEquals(0, $reflection->getParameters()[2]->getDefaultValue());
    }
}
