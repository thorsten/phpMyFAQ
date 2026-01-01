<?php

namespace phpMyFAQ\Database;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class SqlsrvTest
 */
#[AllowMockObjectsWithoutExpectations]
class SqlsrvTest extends TestCase
{
    private Sqlsrv $sqlsrv;

    protected function setUp(): void
    {
        $this->sqlsrv = new Sqlsrv();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->sqlsrv);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->sqlsrv->tableNames);
        $this->assertEquals('', $this->sqlsrv->log());
    }

    public function testConnectParameterHandling(): void
    {
        $reflection = new ReflectionMethod($this->sqlsrv, 'connect');
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

    public function testConnectMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'connect'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'connect');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('bool', $returnType->getName());
    }

    public function testEscapeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'escape'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'escape');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('string', $parameters[0]->getName());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testEscapeUsesDoubleQuotes(): void
    {
        $testString = "test'string";
        $result = $this->sqlsrv->escape($testString);

        $this->assertEquals("test''string", $result);
    }

    public function testFetchArrayMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'fetchArray'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'fetchArray');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('array', $returnType->getName());
    }

    public function testFetchRowMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'fetchRow'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'fetchRow');
        $this->assertTrue($reflection->isPublic());
    }

    public function testFetchObjectMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'fetchObject'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'fetchObject');
        $this->assertTrue($reflection->isPublic());
    }

    public function testFetchAllMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'fetchAll'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'fetchAll');
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType->allowsNull());
        $this->assertEquals('array', $returnType->getName());
    }

    public function testNumRowsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'numRows'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'numRows');
        $this->assertEquals('int', $reflection->getReturnType()->getName());
    }

    public function testLogMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'log'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'log');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testQueryMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'query'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'query');
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

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'error'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'error');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testGetTableNamesMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'getTableNames'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'getTableNames');
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
        $result = $this->sqlsrv->getTableNames($prefix);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that all table names have the prefix
        foreach ($result as $tableName) {
            $this->assertStringStartsWith($prefix, $tableName);
        }

        // Check some expected SQL Server tables
        $this->assertContains($prefix . 'faqdata', $result);
        $this->assertContains($prefix . 'faqcategories', $result);
        $this->assertContains($prefix . 'faqconfig', $result);
    }

    public function testGetTableNamesWithoutPrefix(): void
    {
        $result = $this->sqlsrv->getTableNames();

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
        $result = $this->sqlsrv->getTableNames($prefix);

        $this->assertEquals($result, $this->sqlsrv->tableNames);
    }

    public function testGetTableStatusMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'getTableStatus'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'getTableStatus');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('prefix', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertEquals('', $parameters[0]->getDefaultValue());

        $this->assertEquals('array', $reflection->getReturnType()->getName());
    }

    public function testNextIdMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'nextId'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'nextId');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('table', $parameters[0]->getName());
        $this->assertEquals('column', $parameters[1]->getName());
        $this->assertEquals('int', $reflection->getReturnType()->getName());
    }

    public function testClientVersionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'clientVersion'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'clientVersion');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testServerVersionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'serverVersion'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'serverVersion');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testCloseMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'close'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'close');
        $this->assertEquals('void', $reflection->getReturnType()->getName());
    }

    public function testNowMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'now'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'now');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testNowReturnsSQLServerFunction(): void
    {
        $result = $this->sqlsrv->now();
        // SQL Server uses GETDATE()
        $this->assertEquals('GETDATE()', $result);
    }

    public function testQueryLogAccumulation(): void
    {
        $testSqlsrv = new class extends Sqlsrv {
            public function addToLog(string $query): void
            {
                $reflection = new ReflectionProperty(parent::class, 'sqlLog');
                $currentLog = $reflection->getValue($this);
                $reflection->setValue($this, $currentLog . $query);
            }
        };

        $testSqlsrv->addToLog('SELECT * FROM test; ');
        $testSqlsrv->addToLog('UPDATE users SET active = 1; ');

        $log = $testSqlsrv->log();
        $this->assertStringContainsString('SELECT * FROM test;', $log);
        $this->assertStringContainsString('UPDATE users SET active = 1;', $log);
    }

    public function testTableNamesProperty(): void
    {
        $reflection = new ReflectionProperty($this->sqlsrv, 'tableNames');
        $this->assertTrue($reflection->isPublic());

        $this->assertEquals([], $this->sqlsrv->tableNames);

        $this->sqlsrv->getTableNames('test_');
        $this->assertNotEmpty($this->sqlsrv->tableNames);
    }

    public function testSqlLogProperty(): void
    {
        $this->assertEquals('', $this->sqlsrv->log());

        $reflection = new ReflectionMethod($this->sqlsrv, 'log');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    /**
     * @throws \ReflectionException
     */
    public function testConnectionProperty(): void
    {
        $reflection = new ReflectionProperty($this->sqlsrv, 'conn');
        $this->assertTrue($reflection->isPrivate());

        $this->assertFalse($reflection->getValue($this->sqlsrv));
    }

    /**
     * @throws \ReflectionException
     */
    public function testConnectionOptionsProperty(): void
    {
        $reflection = new ReflectionProperty($this->sqlsrv, 'connectionOptions');
        $this->assertTrue($reflection->isPrivate());

        $this->assertEquals([], $reflection->getValue($this->sqlsrv));
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetConnectionOptionsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'setConnectionOptions'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'setConnectionOptions');
        $this->assertTrue($reflection->isPrivate());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);
        $this->assertEquals('user', $parameters[0]->getName());
        $this->assertEquals('password', $parameters[1]->getName());
        $this->assertEquals('database', $parameters[2]->getName());
    }

    public function testFormatErrorsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sqlsrv, 'formatErrors'));

        $reflection = new ReflectionMethod($this->sqlsrv, 'formatErrors');
        $this->assertTrue($reflection->isPrivate());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testDatabaseDriverInterfaceCompliance(): void
    {
        $requiredMethods = [
            'connect',
            'query',
            'error',
            'escape',
            'fetchAll',
            'fetchArray',
            'fetchRow',
            'fetchObject',
            'numRows',
            'log',
            'getTableNames',
            'getTableStatus',
            'nextId',
            'clientVersion',
            'serverVersion',
            'close',
            'now',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->sqlsrv, $method),
                "Required DatabaseDriver method '$method' does not exist",
            );
        }
    }

    public function testSQLServerSpecificFeatures(): void
    {
        $reflection = new ReflectionMethod($this->sqlsrv, 'query');
        $this->assertTrue($reflection->hasReturnType());

        $this->assertEquals('GETDATE()', $this->sqlsrv->now());

        $this->assertEquals("test''string", $this->sqlsrv->escape("test'string"));
    }

    public function testSQLServerConnectionHandling(): void
    {
        $reflection = new ReflectionMethod($this->sqlsrv, 'connect');
        $parameters = $reflection->getParameters();

        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertTrue($parameters[4]->getType()->allowsNull());
        $this->assertEquals('int', $parameters[4]->getType()->getName());
    }

    public function testOffsetFetchSyntax(): void
    {
        $reflection = new ReflectionMethod($this->sqlsrv, 'query');

        $this->assertTrue($reflection->getParameters()[1]->hasType());
        $this->assertTrue($reflection->getParameters()[2]->hasType());

        $this->assertEquals(0, $reflection->getParameters()[1]->getDefaultValue());
        $this->assertEquals(0, $reflection->getParameters()[2]->getDefaultValue());
    }
}
