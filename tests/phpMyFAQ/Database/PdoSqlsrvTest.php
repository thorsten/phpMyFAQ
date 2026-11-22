<?php

namespace phpMyFAQ\Database;

use PDO;
use PDOStatement;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

/**
 * Class PdoSqlsrvTest
 */
#[AllowMockObjectsWithoutExpectations]
class PdoSqlsrvTest extends TestCase
{
    private PdoSqlsrv $pdoSqlsrv;

    protected function setUp(): void
    {
        $this->pdoSqlsrv = new PdoSqlsrv();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->pdoSqlsrv);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->pdoSqlsrv->tableNames);
        $this->assertEquals('', $this->pdoSqlsrv->log());
    }

    public function testConnectExceptionHandling(): void
    {
        // Test that connect method exists and handles PDOException
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'connect'));

        $this->expectException(Exception::class);

        // Suppress PDO connection warnings
        set_error_handler(function () {}, E_WARNING);

        try {
            // This should fail and throw Exception for invalid SQL Server connection
            $this->pdoSqlsrv->connect('invalid_server', 'invalid_user', 'invalid_password', 'invalid_db', 1433);
        } finally {
            restore_error_handler();
        }
    }

    public function testConnectDsnFormat(): void
    {
        $host = 'localhost';
        $database = 'test_db';
        $port = 1433;

        $this->assertTrue(method_exists($this->pdoSqlsrv, 'connect'));
    }

    public function testConnectParameterHandling(): void
    {
        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'connect');
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

    public function testEscapeWithQuoteMethod(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'escape'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'escape');
        $this->assertTrue($reflection->isPublic());
    }

    public function testFetchArrayWithMockResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);
        $expectedData = ['id' => 1, 'name' => 'test'];

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($expectedData);

        $result = $this->pdoSqlsrv->fetchArray($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchArrayReturnsNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(null);

        $result = $this->pdoSqlsrv->fetchArray($statementMock);
        $this->assertNull($result);
    }

    public function testFetchRowWithData(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn(['test_value', 'other_value']);

        $result = $this->pdoSqlsrv->fetchRow($statementMock);
        $this->assertEquals('test_value', $result);
    }

    public function testFetchRowEmpty(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_NUM)->willReturn([]);

        $result = $this->pdoSqlsrv->fetchRow($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchRowNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_NUM)->willReturn(null);

        $result = $this->pdoSqlsrv->fetchRow($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchObject(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);
        $expectedObject = new stdClass();
        $expectedObject->id = 1;
        $expectedObject->name = 'test';

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_OBJ)->willReturn($expectedObject);

        $result = $this->pdoSqlsrv->fetchObject($statementMock);
        $this->assertEquals($expectedObject, $result);
    }

    public function testFetchObjectNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_OBJ)->willReturn(null);

        $result = $this->pdoSqlsrv->fetchObject($statementMock);
        $this->assertNull($result);
    }

    public function testFetchAllWithValidResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $object1 = new stdClass();
        $object1->id = 1;
        $object2 = new stdClass();
        $object2->id = 2;

        $expectedData = [$object1, $object2];

        $statementMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_OBJ)->willReturn($expectedData);

        $result = $this->pdoSqlsrv->fetchAll($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchAllWithFalseResult(): void
    {
        $pdoSqlsrv = new class extends PdoSqlsrv {
            public function error(): string
            {
                return 'SQL Server test error message';
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error while fetching result: SQL Server test error message');

        $pdoSqlsrv->fetchAll(false);
    }

    public function testFetchAllWithEmptyResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_OBJ)->willReturn([]);

        $result = $this->pdoSqlsrv->fetchAll($statementMock);
        $this->assertEquals([], $result);
    }

    public function testNumRows(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('rowCount')->willReturn(15);

        $result = $this->pdoSqlsrv->numRows($statementMock);
        $this->assertEquals(15, $result);
    }

    public function testNumRowsZero(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('rowCount')->willReturn(0);

        $result = $this->pdoSqlsrv->numRows($statementMock);
        $this->assertEquals(0, $result);
    }

    public function testGetTableNames(): void
    {
        $prefix = 'test_';
        $result = $this->pdoSqlsrv->getTableNames($prefix);

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
        $result = $this->pdoSqlsrv->getTableNames();

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
        $result = $this->pdoSqlsrv->getTableNames($prefix);

        $this->assertEquals($result, $this->pdoSqlsrv->tableNames);
    }

    public function testGetTableStatus(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'getTableStatus'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'getTableStatus');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('prefix', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertEquals('', $parameters[0]->getDefaultValue());

        $this->assertEquals('array', $reflection->getReturnType()->getName());
    }

    public function testNextIdMethod(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'nextId'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'nextId');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('table', $parameters[0]->getName());
        $this->assertEquals('column', $parameters[1]->getName());
    }

    public function testQueryMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'query'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'query');
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

    public function testPrepareMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'prepare'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'prepare');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('options', $parameters[1]->getName());

        $this->assertTrue($parameters[1]->isOptional());
    }

    public function testExecuteMethod(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);
        $params = ['id' => 1, 'name' => 'test'];

        $statementMock->expects($this->once())->method('execute')->with($params)->willReturn(true);

        $result = $this->pdoSqlsrv->execute($statementMock, $params);
        $this->assertTrue($result);
    }

    public function testExecuteWithoutParams(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('execute')->with([])->willReturn(true);

        $result = $this->pdoSqlsrv->execute($statementMock);
        $this->assertTrue($result);
    }

    public function testExecuteFailure(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('execute')->willReturn(false);

        $result = $this->pdoSqlsrv->execute($statementMock);
        $this->assertFalse($result);
    }

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'error'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'error');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testEscapeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'escape'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'escape');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('string', $parameters[0]->getName());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testClientVersionExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'clientVersion'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'clientVersion');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testServerVersionExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'serverVersion'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'serverVersion');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testCloseMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'close'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'close');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('void', $reflection->getReturnType()->getName());
    }

    public function testNowMethod(): void
    {
        $result = $this->pdoSqlsrv->now();
        $this->assertEquals('CURRENT_TIMESTAMP', $result);
    }

    public function testSqlLogAccumulation(): void
    {
        // Test that log starts empty
        $this->assertEquals('', $this->pdoSqlsrv->log());

        // Test that log method exists and returns string
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'log'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'log');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testOffsetFetchSyntax(): void
    {
        // Test that query method would add SQL Server specific OFFSET/FETCH syntax
        // This is tested by checking the method exists and has correct parameters
        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'query');

        // Verify the method can handle offset and rowcount parameters
        $this->assertTrue($reflection->getParameters()[1]->hasType());
        $this->assertTrue($reflection->getParameters()[2]->hasType());

        // Test that default values are 0 (no pagination)
        $this->assertEquals(0, $reflection->getParameters()[1]->getDefaultValue());
        $this->assertEquals(0, $reflection->getParameters()[2]->getDefaultValue());
    }

    public function testDsnFormatVerification(): void
    {
        // Test that connect method uses SQL Server DSN format
        // We can verify this by checking method parameter types
        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'connect');
        $parameters = $reflection->getParameters();

        // Verify host parameter (for SQL Server format)
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        // Verify port parameter is nullable int (SQL Server specific)
        $this->assertTrue($parameters[4]->getType()->allowsNull());
        $this->assertEquals('int', $parameters[4]->getType()->getName());
    }

    public function testTableNamesProperty(): void
    {
        // Test that tableNames is public array
        $reflection = new \ReflectionProperty($this->pdoSqlsrv, 'tableNames');
        $this->assertTrue($reflection->isPublic());

        // Initially empty
        $this->assertEquals([], $this->pdoSqlsrv->tableNames);

        // Gets populated by getTableNames
        $this->pdoSqlsrv->getTableNames('test_');
        $this->assertNotEmpty($this->pdoSqlsrv->tableNames);
    }

    public function testGetTableNamesPopulatesPrefixedTableList(): void
    {
        $tableNames = $this->pdoSqlsrv->getTableNames('pmf_');

        $this->assertCount(56, $tableNames);
        $this->assertSame('pmf_faqadmindashboard', $tableNames[0]);
        $this->assertSame('pmf_faqvoting', $tableNames[55]);
    }

    public function testQueryPrepareExecuteAndVersionsWorkWithInjectedPdo(): void
    {
        // SQLite stands in for SQL Server here and has no scrollable cursors
        $pdo = new class('sqlite::memory:') extends PDO {
            public function prepare(string $query, array $options = []): PDOStatement|false
            {
                return parent::prepare($query);
            }
        };
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec("INSERT INTO items (name) VALUES ('one'), ('two')");
        $this->setPdo($pdo);

        $statement = $this->pdoSqlsrv->query('SELECT id, name FROM items');
        $prepared = $this->pdoSqlsrv->prepare('INSERT INTO items (name) VALUES (?)');
        $executed = $this->pdoSqlsrv->execute($prepared, ['three']);
        $this->pdoSqlsrv->query("UPDATE items SET name = 'updated'");

        $this->assertInstanceOf(PDOStatement::class, $statement);
        $this->assertTrue($executed);
        $this->assertSame(3, $this->pdoSqlsrv->affectedRows());
        $this->assertNotSame('', $this->pdoSqlsrv->clientVersion());
        $this->assertNotSame('', $this->pdoSqlsrv->serverVersion());
        $this->assertSame(3, $this->pdoSqlsrv->lastInsertId());
    }

    public function testQueryPrefixesNonAsciiLiteralsWithNationalMarker(): void
    {
        $pdo = new class ('sqlite::memory:') extends PDO {
            public string $lastQuery = '';

            public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
            {
                $this->lastQuery = $query;

                return parent::query('SELECT 1');
            }
        };
        $this->setPdo($pdo);

        $this->pdoSqlsrv->query("INSERT INTO faqdata (thema) VALUES ('こんにちは')");

        $this->assertSame("INSERT INTO faqdata (thema) VALUES (N'こんにちは')", $pdo->lastQuery);
    }

    public function testNextIdReturnsIncrementedMaximumId(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $pdo->exec("INSERT INTO items (name) VALUES ('one'), ('two')");
        $this->setPdo($pdo);

        $this->assertSame(3, $this->pdoSqlsrv->nextId('items', 'id'));
    }

    public function testGetTableStatusUsesGetOneForEveryKnownTable(): void
    {
        $pdo = $this->createMock(PDO::class);
        $statement = $this->createMock(PDOStatement::class);
        $tableCount = count($this->pdoSqlsrv->getTableNames('pmf_'));

        $pdo->expects($this->exactly($tableCount))->method('prepare')->willReturn($statement);
        $statement->expects($this->exactly($tableCount))->method('execute')->willReturn(true);
        $statement->expects($this->exactly($tableCount))->method('fetch')->with(PDO::FETCH_NUM)->willReturn(['9']);
        $this->setPdo($pdo);

        $status = $this->pdoSqlsrv->getTableStatus('pmf_');

        $this->assertCount($tableCount, $status);
        $this->assertSame('9', $status['pmf_faqadminlog']);
        $this->assertSame('9', $status['pmf_faqvoting']);
    }

    /**
     * pdo_sqlsrv reports -1 from rowCount() for a result set read with the default
     * forward-only cursor; only a scrollable cursor makes numRows() count the rows.
     */
    public function testQueryOpensResultSetsWithAScrollableCursor(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->exactly(3))->method('execute')->willReturn(true);
        $statement->method('rowCount')->willReturn(2);

        $prepared = [];
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('query');
        $pdo
            ->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(static function (string $query, array $options = []) use (
                &$prepared,
                $statement,
            ): PDOStatement {
                $prepared[] = [$query, $options];

                return $statement;
            });
        $this->setPdo($pdo);

        $scrollable = [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL];

        $result = $this->pdoSqlsrv->query('SELECT id FROM faqvoting WHERE artikel = 1');
        $this->pdoSqlsrv->query('  (select id FROM faqdata) UNION (SELECT id FROM faqnews)');
        $this->pdoSqlsrv->query('SELECT id FROM faqdata ORDER BY id', 10, 5);

        $this->assertSame($statement, $result);
        $this->assertSame(2, $this->pdoSqlsrv->numRows($result));
        $this->assertSame(
            [
                ['SELECT id FROM faqvoting WHERE artikel = 1',                               $scrollable],
                ['  (select id FROM faqdata) UNION (SELECT id FROM faqnews)',                $scrollable],
                ['SELECT id FROM faqdata ORDER BY id OFFSET 10 ROWS FETCH NEXT 5 ROWS ONLY', $scrollable],
            ],
            $prepared,
        );
    }

    public function testQueryRunsDataModifyingStatementsWithTheDefaultCursor(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('rowCount')->willReturn(4);

        $queries = [];
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('prepare');
        $pdo
            ->expects($this->exactly(4))
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$queries, $statement): PDOStatement {
                $queries[] = $query;

                return $statement;
            });
        $this->setPdo($pdo);

        $statements = [
            'UPDATE faqvoting SET usr = usr + 1 WHERE artikel = 1',
            "insert into faqjobs (queue) VALUES ('default')",
            'DELETE FROM faqsessions WHERE time < 1',
            "IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_hash') CREATE INDEX idx_hash ON faqadminlog (hash)",
        ];
        foreach ($statements as $query) {
            $this->pdoSqlsrv->query($query);
        }

        $this->assertSame($statements, $queries);
        $this->assertSame(4, $this->pdoSqlsrv->affectedRows());
    }

    public function testQueryPreparedUsesAScrollableCursorOnlyForResultSets(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('execute')->willReturn(true);

        $options = [];
        $pdo = $this->createMock(PDO::class);
        $pdo
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(static function (string $query, array $driverOptions = []) use (
                &$options,
                $statement,
            ): PDOStatement {
                $options[] = $driverOptions;

                return $statement;
            });
        $this->setPdo($pdo);

        $this->pdoSqlsrv->queryPrepared('SELECT login FROM faquser WHERE login = ?', ['admin']);
        $this->pdoSqlsrv->queryPrepared('UPDATE faquser SET login = ? WHERE user_id = ?', ['admin', 1]);

        $this->assertSame([[PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL], []], $options);
    }

    public function testQueryReturnsFalseWhenAResultSetCannotBeExecuted(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('execute')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($statement);
        $pdo->method('errorCode')->willReturn('42S02');
        $pdo->method('errorInfo')->willReturn(['42S02', 208, 'Invalid object name']);
        $this->setPdo($pdo);

        $this->assertFalse($this->pdoSqlsrv->query('SELECT id FROM missing'));
        $this->assertStringContainsString('Invalid object name', $this->pdoSqlsrv->log());
    }

    private function setPdo(PDO $pdo): void
    {
        $reflection = new ReflectionProperty($this->pdoSqlsrv, 'pdo');
        $reflection->setValue($this->pdoSqlsrv, $pdo);
    }
}
