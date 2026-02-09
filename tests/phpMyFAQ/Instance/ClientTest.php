<?php

namespace phpMyFAQ\Instance;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
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

    public function testCreateClientDatabaseWithSchemaModeThrowsOnQueryFailure(): void
    {
        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $dbMock = $this->createMock(DatabaseDriver::class);
        $loggerMock = $this->createMock(Logger::class);

        $this->configuration->method('getDb')->willReturn($dbMock);
        $this->configuration->method('getLogger')->willReturn($loggerMock);

        // SET search_path succeeds, INSERT INTO faqconfig fails
        $dbMock->method('query')->willReturnCallback(static function (string $query): mixed {
            if (str_contains($query, 'SET search_path')) {
                return true;
            }
            if (str_contains($query, 'CREATE')) {
                return true;
            }
            // First INSERT fails
            return false;
        });
        $dbMock->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $dbMock->method('error')->willReturn('relation "faqconfig" does not exist');

        $loggerMock->expects($this->once())->method('error');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to INSERT faqconfig');

        $this->client->setClientUrl('https://tenant.example.com');
        $this->client->createClientDatabase('fail_schema', TenantIsolationMode::SCHEMA);
    }

    public function testCreateClientDatabaseWithDatabaseMode(): void
    {
        if (!file_exists(PMF_CONFIG_DIR . '/database.php')) {
            $this->markTestSkipped(
                'Test fixture tests/content/core/config/database.php is missing; '
                . 'the DATABASE isolation code path requires valid database credentials.',
            );
        }

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

        try {
            $prefix = 'default_';
            $dbMock = $this->createMock(DatabaseDriver::class);
            $this->configuration->method('getDb')->willReturn($dbMock);

            $dbMock->expects($this->atLeastOnce())->method('query');

            $this->client->setClientUrl('https://default.example.com');
            $this->client->createClientDatabase($prefix);
        } finally {
            putenv('PMF_TENANT_ISOLATION_MODE');
        }
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

    public function testCreateClientTablesWithDatabaseThrowsOnMissingCredentials(): void
    {
        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database credentials not found for tenant database "tenant_db".');

        $method = new ReflectionMethod(Client::class, 'createClientTablesWithDatabase');

        // Use a non-existent config dir to ensure getDatabaseCredentials returns null
        $origConfigDir = PMF_CONFIG_DIR;
        // We can't change the constant, but getDatabaseCredentials reads from PMF_CONFIG_DIR/database.php.
        // Instead, test by invoking on a client whose config dir won't have the file.
        // Since PMF_CONFIG_DIR is set to tests/content/core/config, if database.php doesn't exist there
        // the test would pass. But it does exist. So we test via a different approach:
        // We'll just verify the exception is thrown by temporarily renaming the file.
        $dbFile = PMF_CONFIG_DIR . '/database.php';
        $tempFile = PMF_CONFIG_DIR . '/database.php.bak';

        if (!file_exists($dbFile)) {
            // If no database.php exists, the method should throw directly
            $method->invoke($this->client, 'tenant_db');
            return;
        }

        rename($dbFile, $tempFile);
        try {
            $method->invoke($this->client, 'tenant_db');
        } finally {
            rename($tempFile, $dbFile);
        }
    }

    public function testCreateClientTablesWithDatabaseThrowsOnCreateTenantDatabaseFailure(): void
    {
        $this->assertFileExists(
            PMF_CONFIG_DIR . '/database.php',
            'Test fixture tests/content/core/config/database.php is required.',
        );

        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $dbMock = $this->createMock(DatabaseDriver::class);
        $loggerMock = $this->createMock(Logger::class);

        $this->configuration->method('getDb')->willReturn($dbMock);
        $this->configuration->method('getLogger')->willReturn($loggerMock);

        $dbMock->method('query')->willReturnCallback(static function (string $query): mixed {
            // collectSeedRows SELECT queries return a result
            if (str_starts_with($query, 'SELECT * FROM')) {
                return new \stdClass();
            }
            // createTenantDatabase: SELECT 1 FROM pg_database returns a result
            if (str_starts_with($query, 'SELECT 1 FROM pg_database')) {
                return new \stdClass();
            }
            // CREATE DATABASE fails
            if (str_starts_with($query, 'CREATE DATABASE')) {
                return false;
            }
            return true;
        });
        $dbMock->method('fetchAll')->willReturn([]);
        $dbMock->method('numRows')->willReturn(0); // database does not exist yet
        $dbMock->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $loggerMock->expects($this->once())->method('error');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to create tenant database "fail_db".');

        $this->client->setClientUrl('https://tenant.example.com');
        $this->client->createClientDatabase('fail_db', TenantIsolationMode::DATABASE);
    }

    public function testCreateClientTablesWithDatabaseThrowsOnConnectFailure(): void
    {
        $this->assertFileExists(
            PMF_CONFIG_DIR . '/database.php',
            'Test fixture tests/content/core/config/database.php is required.',
        );

        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $dbMock = $this->createMock(DatabaseDriver::class);
        $loggerMock = $this->createMock(Logger::class);

        $this->configuration->method('getDb')->willReturn($dbMock);
        $this->configuration->method('getLogger')->willReturn($loggerMock);

        $dbMock->method('query')->willReturnCallback(static function (string $query): mixed {
            if (str_starts_with($query, 'SELECT * FROM')) {
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
        $dbMock->method('connect')->willReturn(false);
        $dbMock->method('error')->willReturn('Connection refused');

        $loggerMock->expects($this->once())->method('error');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to connect to tenant database "connect_fail_db"');

        $this->client->setClientUrl('https://tenant.example.com');
        $this->client->createClientDatabase('connect_fail_db', TenantIsolationMode::DATABASE);
    }

    public function testCreateClientTablesWithDatabaseThrowsOnCreateTablesFailure(): void
    {
        $this->assertFileExists(
            PMF_CONFIG_DIR . '/database.php',
            'Test fixture tests/content/core/config/database.php is required.',
        );

        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $dbMock = $this->createMock(DatabaseDriver::class);
        $loggerMock = $this->createMock(Logger::class);

        $this->configuration->method('getDb')->willReturn($dbMock);
        $this->configuration->method('getLogger')->willReturn($loggerMock);

        $dbMock->method('query')->willReturnCallback(static function (string $query): mixed {
            if (str_starts_with($query, 'SELECT * FROM')) {
                return new \stdClass();
            }
            if (str_starts_with($query, 'SELECT 1 FROM pg_database')) {
                return new \stdClass();
            }
            // Let createTenantDatabase's CREATE DATABASE succeed
            if (str_starts_with($query, 'CREATE DATABASE')) {
                return true;
            }
            // Fail on the first CREATE TABLE (from createTables/SchemaInstaller)
            if (str_starts_with($query, 'CREATE TABLE')) {
                return false;
            }
            return true;
        });
        $dbMock->method('fetchAll')->willReturn([]);
        $dbMock->method('numRows')->willReturn(0);
        $dbMock->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $dbMock->method('connect')->willReturn(true);

        $loggerMock->expects($this->once())->method('error');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to create tables in tenant database "tables_fail_db"');

        $this->client->setClientUrl('https://tenant.example.com');
        $this->client->createClientDatabase('tables_fail_db', TenantIsolationMode::DATABASE);
    }

    public function testReconnectFailureIsLoggedWithoutSwallowingOriginalException(): void
    {
        $this->assertFileExists(
            PMF_CONFIG_DIR . '/database.php',
            'Test fixture tests/content/core/config/database.php is required.',
        );

        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $dbMock = $this->createMock(DatabaseDriver::class);
        $loggerMock = $this->createMock(Logger::class);

        $this->configuration->method('getDb')->willReturn($dbMock);
        $this->configuration->method('getLogger')->willReturn($loggerMock);

        $dbMock->method('query')->willReturnCallback(static function (string $query): mixed {
            if (str_starts_with($query, 'SELECT * FROM')) {
                return new \stdClass();
            }
            if (str_starts_with($query, 'SELECT 1 FROM pg_database')) {
                return new \stdClass();
            }
            // CREATE DATABASE fails to trigger the original exception
            if (str_starts_with($query, 'CREATE DATABASE')) {
                return false;
            }
            return true;
        });
        $dbMock->method('fetchAll')->willReturn([]);
        $dbMock->method('numRows')->willReturn(0);
        $dbMock->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        // Reconnect throws an exception
        $dbMock->method('connect')->willThrowException(new \RuntimeException('Connection lost'));

        // Expect two logger->error calls: one for the original failure, one for the reconnect failure
        $logMessages = [];
        $loggerMock
            ->expects($this->exactly(2))
            ->method('error')
            ->willReturnCallback(static function (string $message, array $context) use (&$logMessages): void {
                $logMessages[] = $message;
            });

        try {
            $this->client->setClientUrl('https://tenant.example.com');
            $this->client->createClientDatabase('reconnect_fail_db', TenantIsolationMode::DATABASE);
            $this->fail('Expected Exception was not thrown.');
        } catch (Exception $exception) {
            // The original exception is preserved, not the reconnect one
            $this->assertStringContainsString('Failed to create tenant database "reconnect_fail_db"', $exception->getMessage());
        }

        $this->assertContains('Failed to create tenant database tables.', $logMessages);
        $this->assertContains('Failed to reconnect to source database.', $logMessages);
    }
}
