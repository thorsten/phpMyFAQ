<?php

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\TenantIsolationMode;
use phpMyFAQ\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

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
        $dbMock->method('query')->willReturnCallback(
            static function (string $query) use (&$queries): bool {
                $queries[] = $query;
                return true;
            }
        );
        $dbMock->method('escape')->willReturnCallback(static fn (string $value): string => $value);

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
        Database::factory('pdo_pgsql');
        Database::setTablePrefix('');

        $dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($dbMock);

        $dbMock->expects($this->exactly(2))
            ->method('connect')
            ->willReturnCallback(static function (
                string $server,
                string $user,
                string $password,
                string $database,
                ?int $port = null,
            ): bool {
                return $database === 'tenant_db' || $database === '';
            });

        $queries = [];
        $dbMock->method('query')->willReturnCallback(
            static function (string $query) use (&$queries): mixed {
                $queries[] = $query;
                if (str_starts_with($query, 'SELECT * FROM faq')) {
                    return new \stdClass();
                }
                if (str_starts_with($query, 'SELECT 1 FROM pg_database')) {
                    return new \stdClass();
                }
                return true;
            }
        );
        $dbMock->method('fetchAll')->willReturn([]);
        $dbMock->method('numRows')->willReturn(0);
        $dbMock->method('escape')->willReturnCallback(static fn (string $value): string => $value);

        $this->client->setClientUrl('https://tenant.example.com');
        $this->client->createClientDatabase('tenant_db', TenantIsolationMode::DATABASE);

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
}
