<?php

namespace phpMyFAQ\Database;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

if (!defined(__NAMESPACE__ . '\PGSQL_COMMAND_OK')) {
    define(__NAMESPACE__ . '\PGSQL_COMMAND_OK', 1);
}

if (!defined(__NAMESPACE__ . '\PGSQL_ASSOC')) {
    define(__NAMESPACE__ . '\PGSQL_ASSOC', 1);
}

function pg_query(mixed $connection, string $query): mixed
{
    $GLOBALS['pmfPgsqlTestState']['last_query'] = $query;

    return $GLOBALS['pmfPgsqlTestState']['query_result'] ?? false;
}

function pg_result_status(mixed $result): int
{
    return $GLOBALS['pmfPgsqlTestState']['result_status'] ?? 0;
}

function pg_last_error(mixed $connection): string
{
    return $GLOBALS['pmfPgsqlTestState']['last_error'] ?? '';
}

function pg_escape_string(mixed $connection, string $string): string
{
    return $GLOBALS['pmfPgsqlTestState']['escaped_string'] ?? addslashes($string);
}

function pg_fetch_object(mixed $result): mixed
{
    return array_shift($GLOBALS['pmfPgsqlTestState']['fetch_object_rows']);
}

function pg_fetch_row(mixed $result): mixed
{
    return array_shift($GLOBALS['pmfPgsqlTestState']['fetch_row_rows']);
}

function pg_num_rows(mixed $result): int
{
    return $GLOBALS['pmfPgsqlTestState']['num_rows'] ?? 0;
}

function pg_fetch_array(mixed $result, ?int $row = null, int $mode = PGSQL_ASSOC): mixed
{
    return array_shift($GLOBALS['pmfPgsqlTestState']['fetch_array_rows']);
}

function pg_version(mixed $connection): array
{
    return $GLOBALS['pmfPgsqlTestState']['version'] ?? ['client' => 'n/a', 'server' => 'n/a'];
}

function pg_close(mixed $connection): bool
{
    $GLOBALS['pmfPgsqlTestState']['closed'] = true;

    return $GLOBALS['pmfPgsqlTestState']['close_result'] ?? true;
}

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
        $GLOBALS['pmfPgsqlTestState'] = [
            'query_result' => false,
            'result_status' => 0,
            'last_error' => '',
            'escaped_string' => '',
            'fetch_object_rows' => [],
            'fetch_row_rows' => [],
            'fetch_array_rows' => [],
            'num_rows' => 0,
            'version' => ['client' => '15.4', 'server' => '16.2'],
            'closed' => false,
            'close_result' => true,
            'last_query' => null,
        ];
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
                method_exists($this->pgsql, $method),
                "Required DatabaseDriver method '$method' does not exist",
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

    public function testQueryReturnsTrueForCommandStatus(): void
    {
        $GLOBALS['pmfPgsqlTestState']['query_result'] = true;
        $GLOBALS['pmfPgsqlTestState']['result_status'] = PGSQL_COMMAND_OK;

        $result = $this->pgsql->query('UPDATE faqdata SET active = 1');

        $this->assertTrue($result);
        $this->assertSame('UPDATE faqdata SET active = 1', $GLOBALS['pmfPgsqlTestState']['last_query']);
        $this->assertStringContainsString('UPDATE faqdata SET active = 1', $this->pgsql->log());
    }

    public function testQueryReturnsFalseAndLogsErrorOnFailure(): void
    {
        $GLOBALS['pmfPgsqlTestState']['query_result'] = false;
        $GLOBALS['pmfPgsqlTestState']['last_error'] = 'query failed';

        $result = $this->pgsql->query('SELECT * FROM faqdata');

        $this->assertFalse($result);
        $this->assertStringContainsString('query failed', $this->pgsql->log());
    }

    public function testErrorReturnsLastErrorMessage(): void
    {
        $GLOBALS['pmfPgsqlTestState']['last_error'] = 'broken relation';

        $this->assertSame('broken relation', $this->pgsql->error());
    }

    public function testEscapeUsesPgEscapeString(): void
    {
        $GLOBALS['pmfPgsqlTestState']['escaped_string'] = "escaped''value";

        $this->assertSame("escaped''value", $this->pgsql->escape("escaped'value"));
    }

    public function testFetchObjectReturnsStubbedObject(): void
    {
        $expected = (object) ['id' => 7];
        $GLOBALS['pmfPgsqlTestState']['fetch_object_rows'] = [$expected];

        $this->assertSame($expected, $this->pgsql->fetchObject(true));
    }

    public function testFetchRowReturnsStubbedRow(): void
    {
        $GLOBALS['pmfPgsqlTestState']['fetch_row_rows'] = [['value', 'extra']];

        $this->assertSame(['value', 'extra'], $this->pgsql->fetchRow(true));
    }

    public function testNumRowsReturnsStubbedCount(): void
    {
        $GLOBALS['pmfPgsqlTestState']['num_rows'] = 12;

        $this->assertSame(12, $this->pgsql->numRows(true));
    }

    public function testFetchArrayReturnsAssocRowOrEmptyArray(): void
    {
        $GLOBALS['pmfPgsqlTestState']['fetch_array_rows'] = [
            ['relname' => 'faqdata'],
            false,
        ];

        $this->assertSame(['relname' => 'faqdata'], $this->pgsql->fetchArray(true));
        $this->assertSame([], $this->pgsql->fetchArray(true));
    }

    public function testFetchAllReturnsAllObjects(): void
    {
        $first = (object) ['id' => 1];
        $second = (object) ['id' => 2];
        $GLOBALS['pmfPgsqlTestState']['fetch_object_rows'] = [$first, $second, false];

        $result = $this->pgsql->fetchAll(true);

        $this->assertSame([$first, $second], $result);
    }

    public function testGetTableStatusBuildsRowCounts(): void
    {
        $GLOBALS['pmfPgsqlTestState']['query_result'] = true;
        $GLOBALS['pmfPgsqlTestState']['result_status'] = 0;
        $GLOBALS['pmfPgsqlTestState']['fetch_array_rows'] = [
            ['relname' => 'faqdata'],
            ['relname' => 'faqcategories'],
            false,
        ];
        $GLOBALS['pmfPgsqlTestState']['fetch_row_rows'] = [
            ['4'],
            ['9'],
        ];

        $status = $this->pgsql->getTableStatus();

        $this->assertSame(['faqdata' => '4', 'faqcategories' => '9'], $status);
    }

    public function testNextIdReturnsSequenceValuePlusZeroCasting(): void
    {
        $GLOBALS['pmfPgsqlTestState']['query_result'] = true;
        $GLOBALS['pmfPgsqlTestState']['result_status'] = 0;
        $GLOBALS['pmfPgsqlTestState']['fetch_row_rows'] = [['18']];

        $nextId = $this->pgsql->nextId('faqdata', 'id');

        $this->assertSame(18, $nextId);
        $this->assertStringContainsString(
            "SELECT nextval('faqdata_id_seq')",
            (string) $GLOBALS['pmfPgsqlTestState']['last_query'],
        );
    }

    public function testClientAndServerVersionUsePgVersion(): void
    {
        $GLOBALS['pmfPgsqlTestState']['version'] = ['client' => '17.1', 'server' => '16.9'];

        $this->assertSame('17.1', $this->pgsql->clientVersion());
        $this->assertSame('16.9', $this->pgsql->serverVersion());
    }

    public function testCloseReturnsStubbedValue(): void
    {
        $GLOBALS['pmfPgsqlTestState']['close_result'] = true;

        $this->assertTrue($this->pgsql->close());
        $this->assertTrue($GLOBALS['pmfPgsqlTestState']['closed']);
    }

    public function testLastInsertIdReturnsFetchedValueOrZero(): void
    {
        $GLOBALS['pmfPgsqlTestState']['query_result'] = true;
        $GLOBALS['pmfPgsqlTestState']['fetch_row_rows'] = [['23']];

        $this->assertSame(23, $this->pgsql->lastInsertId());

        $GLOBALS['pmfPgsqlTestState']['query_result'] = false;
        $this->assertSame(0, $this->pgsql->lastInsertId());
    }
}
