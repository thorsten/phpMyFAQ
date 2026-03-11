<?php

namespace phpMyFAQ\Database;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

if (!defined(__NAMESPACE__ . '\SQLSRV_FETCH_ASSOC')) {
    define(__NAMESPACE__ . '\SQLSRV_FETCH_ASSOC', 2);
}

if (!defined(__NAMESPACE__ . '\SQLSRV_CURSOR_KEYSET')) {
    define(__NAMESPACE__ . '\SQLSRV_CURSOR_KEYSET', 1);
}

function sqlsrv_connect(string $serverName, array $connectionInfo): mixed
{
    $GLOBALS['pmfSqlsrvTestState']['connected_to'] = $serverName;
    $GLOBALS['pmfSqlsrvTestState']['connection_info'] = $connectionInfo;

    return $GLOBALS['pmfSqlsrvTestState']['connect_result'] ?? false;
}

function sqlsrv_errors(): ?array
{
    return $GLOBALS['pmfSqlsrvTestState']['errors'] ?? null;
}

function sqlsrv_fetch_array(mixed $result, int $fetchType): mixed
{
    return array_shift($GLOBALS['pmfSqlsrvTestState']['fetch_array_rows']);
}

function sqlsrv_fetch_object(mixed $result): mixed
{
    return array_shift($GLOBALS['pmfSqlsrvTestState']['fetch_object_rows']);
}

function sqlsrv_num_rows(mixed $result): int
{
    return $GLOBALS['pmfSqlsrvTestState']['num_rows'] ?? 0;
}

function sqlsrv_query(mixed $conn, string $query, array $params = [], array $options = []): mixed
{
    $GLOBALS['pmfSqlsrvTestState']['last_query'] = $query;
    $GLOBALS['pmfSqlsrvTestState']['last_query_options'] = $options;

    return $GLOBALS['pmfSqlsrvTestState']['query_result'] ?? false;
}

function sqlsrv_rows_affected(mixed $result): int|false
{
    return $GLOBALS['pmfSqlsrvTestState']['rows_affected'] ?? 0;
}

function sqlsrv_fetch(mixed $result): bool
{
    $GLOBALS['pmfSqlsrvTestState']['fetch_called'] = true;

    return true;
}

function sqlsrv_get_field(mixed $result, int $fieldIndex): mixed
{
    return $GLOBALS['pmfSqlsrvTestState']['field_value'] ?? 0;
}

function sqlsrv_client_info(mixed $conn): array
{
    return $GLOBALS['pmfSqlsrvTestState']['client_info'] ?? ['DriverODBCVer' => '18.3', 'DriverVer' => '5.12'];
}

function sqlsrv_server_info(mixed $conn): array
{
    return $GLOBALS['pmfSqlsrvTestState']['server_info'] ?? ['SQLServerVersion' => '16.0.1000.6'];
}

function sqlsrv_close(mixed $conn): bool
{
    $GLOBALS['pmfSqlsrvTestState']['closed'] = true;

    return true;
}

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
        $GLOBALS['pmfSqlsrvTestState'] = [
            'connect_result' => 'sqlsrv-connection',
            'errors' => null,
            'fetch_array_rows' => [],
            'fetch_object_rows' => [],
            'num_rows' => 0,
            'query_result' => false,
            'rows_affected' => 0,
            'field_value' => 0,
            'client_info' => ['DriverODBCVer' => '18.3', 'DriverVer' => '5.12'],
            'server_info' => ['SQLServerVersion' => '16.0.1000.6'],
            'closed' => false,
            'fetch_called' => false,
        ];
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

    public function testConnectStoresExpectedServerStringAndOptions(): void
    {
        $result = $this->sqlsrv->connect('localhost', 'alice', 'secret', 'faqdb', 1433);

        $this->assertTrue($result);
        $this->assertSame('localhost, 1433', $GLOBALS['pmfSqlsrvTestState']['connected_to']);
        $this->assertSame('alice', $GLOBALS['pmfSqlsrvTestState']['connection_info']['UID']);
        $this->assertSame('faqdb', $GLOBALS['pmfSqlsrvTestState']['connection_info']['Database']);
    }

    public function testQueryAffectedRowsAndErrorUseSqlsrvShims(): void
    {
        $this->setConnectionProperty('sqlsrv-connection');
        $GLOBALS['pmfSqlsrvTestState']['query_result'] = 'sqlsrv-result';
        $GLOBALS['pmfSqlsrvTestState']['rows_affected'] = 4;
        $GLOBALS['pmfSqlsrvTestState']['errors'] = [['SQLSTATE' => '42000', 'message' => 'broken']];

        $result = $this->sqlsrv->query('SELECT * FROM faqdata', 5, 10);

        $this->assertSame('sqlsrv-result', $result);
        $this->assertStringContainsString(
            'OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY',
            $GLOBALS['pmfSqlsrvTestState']['last_query'],
        );
        $this->assertSame(4, $this->sqlsrv->affectedRows());
        $this->assertSame('42000: broken', $this->sqlsrv->error());
    }

    public function testFetchMethodsAndNumRowsUseSqlsrvShims(): void
    {
        $GLOBALS['pmfSqlsrvTestState']['fetch_array_rows'] = [['id' => 1], false];
        $GLOBALS['pmfSqlsrvTestState']['fetch_object_rows'] = [(object) ['id' => 2], false];
        $GLOBALS['pmfSqlsrvTestState']['num_rows'] = 6;

        $this->assertSame(['id' => 1], $this->sqlsrv->fetchArray('result'));
        $this->assertSame([], $this->sqlsrv->fetchArray('result'));
        $this->assertEquals((object) ['id' => 2], $this->sqlsrv->fetchObject('result'));
        $this->assertFalse($this->sqlsrv->fetchObject('result'));
        $this->assertSame(6, $this->sqlsrv->numRows('result'));
    }

    public function testNextIdVersionsLastInsertIdAndCloseUseSqlsrvShims(): void
    {
        $this->setConnectionProperty('sqlsrv-connection');
        $GLOBALS['pmfSqlsrvTestState']['query_result'] = 'sqlsrv-result';
        $GLOBALS['pmfSqlsrvTestState']['field_value'] = 8;

        $this->assertSame(9, $this->sqlsrv->nextId('faqdata', 'id'));
        $this->assertTrue($GLOBALS['pmfSqlsrvTestState']['fetch_called']);
        $this->assertSame('18.3 5.12', $this->sqlsrv->clientVersion());
        $this->assertSame('16.0.1000.6', $this->sqlsrv->serverVersion());
        $this->assertSame(8, $this->sqlsrv->lastInsertId());

        $this->sqlsrv->close();

        $this->assertTrue($GLOBALS['pmfSqlsrvTestState']['closed']);
    }

    public function testPrivateHelpersFormatErrorsAndConnectionOptions(): void
    {
        $setConnectionOptions = new ReflectionMethod($this->sqlsrv, 'setConnectionOptions');
        $setConnectionOptions->invoke($this->sqlsrv, 'bob', 'secret', 'faqdb');

        $options = new ReflectionProperty($this->sqlsrv, 'connectionOptions');
        $connectionOptions = $options->getValue($this->sqlsrv);

        $this->assertSame('bob', $connectionOptions['UID']);
        $this->assertSame('faqdb', $connectionOptions['Database']);
        $this->assertTrue($connectionOptions['TrustServerCertificate']);

        $formatErrors = new ReflectionMethod($this->sqlsrv, 'formatErrors');
        $errorHtml = $formatErrors->invoke($this->sqlsrv, [[
            'SQLSTATE' => '42S02',
            'code' => 208,
            'message' => 'Invalid object name',
        ]]);

        $this->assertStringContainsString('42S02', $errorHtml);
        $this->assertStringContainsString('208', $errorHtml);
        $this->assertStringContainsString('Invalid object name', $errorHtml);
    }

    private function setConnectionProperty(mixed $connection): void
    {
        $reflection = new ReflectionProperty($this->sqlsrv, 'conn');
        $reflection->setValue($this->sqlsrv, $connection);
    }
}
