<?php

namespace phpMyFAQ\Instance;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\TenantIsolationMode;
use phpMyFAQ\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Class ClientTest
 */
#[AllowMockObjectsWithoutExpectations]
class ClientTest extends TestCase
{
    private Client $client;
    private Filesystem $filesystem;
    private Configuration $configuration;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->createMock(Filesystem::class);
        $this->configuration = $this->createStub(Configuration::class);
        $this->client = new Client($this->configuration);
        $this->client->setFileSystem($this->filesystem);
    }

    protected function tearDown(): void
    {
        Database::setTablePrefix('');

        parent::tearDown();
    }

    public function testCreateClientFolder(): void
    {
        $hostname = 'example.com';
        $this->filesystem->method('createDirectory')->willReturn(true);

        $result = $this->client->createClientFolder($hostname);

        $this->assertTrue($result);
    }

    public function testCreateClientTables(): void
    {
        $prefix = 'test_';
        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->expects($this->exactly(5))->method('query');

        $this->client->setClientUrl('https://example.com');
        $this->client->createClientTables($prefix);
    }

    public function testCopyConstantsFile(): void
    {
        $destination = '/path/to/destination/constants.php';
        $this->filesystem->method('copy')->willReturn(true);

        $result = $this->client->copyConstantsFile($destination);

        $this->assertTrue($result);
    }

    public function testCopyTemplateFolder(): void
    {
        $destination = '/path/to/destination';
        $templateDir = 'default';

        $this->filesystem->expects($this->once())->method('recursiveCopy');

        $this->client->copyTemplateFolder($destination, $templateDir);
    }

    public function testMoveClientFolder(): void
    {
        $sourceUrl = 'https://source.com';
        $destinationUrl = 'https://destination.com';
        $this->filesystem->method('moveDirectory')->willReturn(true);

        $result = $this->client->moveClientFolder($sourceUrl, $destinationUrl);

        $this->assertTrue($result);
    }

    public function testDeleteClientFolder(): void
    {
        $sourceUrl = 'https://source.com';
        $this->filesystem->method('deleteDirectory')->willReturn(true);

        $result = $this->client->deleteClientFolder($sourceUrl);

        $this->assertTrue($result);
    }

    public function testCreateClientDatabaseWithPrefixMode(): void
    {
        $prefix = 'tenant_';
        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->expects($this->atLeastOnce())->method('query');

        $this->client->setClientUrl('https://tenant.example.com');
        $this->client->createClientDatabase($prefix, TenantIsolationMode::PREFIX);
    }

    public function testCreateClientDatabaseWithSchemaMode(): void
    {
        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $queries = [];
        $dbMock
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$queries): bool {
                $queries[] = $query;
                return true;
            });
        $dbMock->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $this->client->setClientUrl('https://tenant.example.com');
        $this->client->createClientDatabase('tenant_schema', TenantIsolationMode::SCHEMA);

        $this->assertNotEmpty($queries);
        $this->assertTrue($this->queryContains($queries, 'SET search_path TO "tenant_schema"'));
        $this->assertContains('INSERT INTO "tenant_schema".faqconfig SELECT * FROM faqconfig', $queries);
        $this->assertContains('INSERT INTO "tenant_schema".faqright SELECT * FROM faqright', $queries);
        $this->assertContains(
            'INSERT INTO "tenant_schema".faquser_right SELECT * FROM faquser_right WHERE user_id = 1',
            $queries,
        );
        $this->assertContains(
            "UPDATE \"tenant_schema\".faqconfig SET config_value = 'https://tenant.example.com' WHERE config_name = 'main.referenceURL'",
            $queries,
        );
    }

    public function testCreateClientDatabaseWithDatabaseMode(): void
    {
        // Guard: the DATABASE code path requires a database.php fixture so
        // getDatabaseCredentials() returns non-null credentials.
        $this->assertFileExists(
            PMF_CONFIG_DIR . '/database.php',
            'Test fixture tests/content/core/config/database.php is required for the DATABASE isolation code path.',
        );

        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $connectCalls = [];
        $dbMock
            ->expects($this->exactly(2))
            ->method('connect')
            ->willReturnCallback(static function (
                string $server,
                string $user,
                string $password,
                string $database,
                ?int $port = null,
            ) use (&$connectCalls): bool {
                $connectCalls[] = $database;
                return true;
            });

        $queries = [];
        $dbMock
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$queries): mixed {
                $queries[] = $query;
                if (str_starts_with($query, 'SELECT * FROM faq')) {
                    return new \stdClass();
                }
                if (str_starts_with($query, 'SELECT 1 FROM pg_database')) {
                    return new \stdClass();
                }
                return true;
            });
        $dbMock->method('fetchAll')->willReturn([]);
        $dbMock->method('numRows')->willReturn(0);
        $dbMock->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $this->client->setClientUrl('https://tenant.example.com');
        $this->client->createClientDatabase('tenant_db', TenantIsolationMode::DATABASE);

        // Verify connect was called first with the tenant DB, then restored to the source DB
        $this->assertCount(2, $connectCalls);
        $this->assertSame('tenant_db', $connectCalls[0]);

        $this->assertNotEmpty($queries);
        $this->assertContains('SELECT * FROM faqconfig', $queries);
        $this->assertContains('SELECT * FROM faqright', $queries);
        $this->assertContains('SELECT * FROM faquser_right WHERE user_id = 1', $queries);
        $this->assertContains("SELECT 1 FROM pg_database WHERE datname = 'tenant_db'", $queries);
        $this->assertContains('CREATE DATABASE "tenant_db"', $queries);
        $this->assertContains(
            "UPDATE faqconfig SET config_value = 'https://tenant.example.com' WHERE config_name = 'main.referenceURL'",
            $queries,
        );
    }

    /**
     * @param string[] $queries
     */
    private function queryContains(array $queries, string $needle): bool
    {
        foreach ($queries as $query) {
            if (str_contains($query, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function testCreateClientDatabaseDefaultsToPrefix(): void
    {
        putenv('PMF_TENANT_ISOLATION_MODE=prefix');

        $prefix = 'default_';
        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->expects($this->atLeastOnce())->method('query');

        $this->client->setClientUrl('https://default.example.com');
        $this->client->createClientDatabase($prefix);

        putenv('PMF_TENANT_ISOLATION_MODE');
    }

    public function testInsertRowsThrowsOnQueryFailure(): void
    {
        $dbMock = $this->createMock(DatabaseDriver::class);
        $loggerMock = $this->createMock(Logger::class);

        $this->configuration->method('getDb')->willReturn($dbMock);
        $this->configuration->method('getLogger')->willReturn($loggerMock);

        $dbMock->method('query')->willReturn(false);
        $dbMock->method('error')->willReturn('Duplicate entry for key PRIMARY');
        $dbMock->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Failed to insert row into tenant table.', $this->callback(static function (array $context): bool {
                return (
                    $context['table'] === 'test_faqconfig'
                    && str_contains($context['query'], 'INSERT INTO test_faqconfig')
                    && $context['error'] === 'Duplicate entry for key PRIMARY'
                );
            }));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to insert row into test_faqconfig: Duplicate entry for key PRIMARY');

        $method = new ReflectionMethod(Client::class, 'insertRows');

        $rows = [(object) ['config_name' => 'test.key', 'config_value' => 'test_value']];
        $method->invoke($this->client, 'test_faqconfig', $rows);
    }

    public function testInsertRowsSucceedsWhenQueryReturnsTrue(): void
    {
        $dbMock = $this->createMock(DatabaseDriver::class);
        $loggerMock = $this->createMock(Logger::class);

        $this->configuration->method('getDb')->willReturn($dbMock);
        $this->configuration->method('getLogger')->willReturn($loggerMock);

        $dbMock->method('query')->willReturn(true);
        $dbMock->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $loggerMock->expects($this->never())->method('error');

        $method = new ReflectionMethod(Client::class, 'insertRows');

        $rows = [
            (object) ['config_name' => 'key1', 'config_value' => 'value1'],
            (object) ['config_name' => 'key2', 'config_value' => 'value2'],
        ];
        $method->invoke($this->client, 'test_faqconfig', $rows);

        $this->addToAssertionCount(1);
    }
}
