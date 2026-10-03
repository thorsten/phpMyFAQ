<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Records the 4.2.0-alpha migration for every supported database dialect. The migration
 * branches on the dialect in dozens of places; this pins the dialect specific statements
 * and asserts that everything that is not SQL (configuration keys, permissions) is the
 * same regardless of the database in use.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Migration420Alpha::class)]
#[UsesNamespace('phpMyFAQ')]
final class Migration420AlphaDialectsTest extends TestCase
{
    private mixed $previousDatabaseType = null;

    private string $previousTablePrefix = '';

    protected function setUp(): void
    {
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        $this->previousTablePrefix = Database::getTablePrefix();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
        Database::setTablePrefix($this->previousTablePrefix);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dialectProvider(): iterable
    {
        foreach (['mysqli', 'pdo_mysql', 'pgsql', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite', 'sqlsrv', 'pdo_sqlsrv'] as $type) {
            yield $type => [$type];
        }
    }

    private function record(string $dbType, string $prefix = 'pmf_'): OperationRecorder
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);
        Database::setTablePrefix($prefix);

        $recorder = new OperationRecorder($this->createStub(Configuration::class));
        new Migration420Alpha($this->createStub(Configuration::class))->up($recorder);

        return $recorder;
    }

    /**
     * @return array<int, string>
     */
    private function configKeys(OperationRecorder $recorder, string $type): array
    {
        $keys = array_map(
            static fn(array $operation): string => (string) ($operation['key'] ?? $operation['oldKey'] ?? ''),
            array_filter($recorder->toArray(), static fn(array $operation): bool => $operation['type'] === $type),
        );
        sort($keys);

        return array_values($keys);
    }

    #[DataProvider('dialectProvider')]
    public function testEveryDialectRecordsThePrefixedSchemaChanges(string $dbType): void
    {
        $recorder = $this->record($dbType);
        $queries = $recorder->getSqlQueries();

        $this->assertGreaterThan(10, count($queries), 'The 4.2.0-alpha migration is a large schema change.');

        foreach ($queries as $query) {
            $this->assertStringContainsString('pmf_faq', $query, 'Every statement addresses a prefixed table: ' . $query);
            $this->assertDoesNotMatchRegularExpression(
                '/(?<![\w.])faq[a-z_]+/',
                $query,
                'A table name without the prefix slipped through: ' . $query,
            );
        }

        $allSql = implode("\n", $queries);
        foreach (['pmf_faqcustompages', 'pmf_faqapi_keys', 'pmf_faqoauth_clients', 'pmf_faqadminlog'] as $table) {
            $this->assertStringContainsString($table, $allSql);
        }
    }

    #[DataProvider('dialectProvider')]
    public function testEveryDialectAddsTheHashColumnsInItsOwnSyntax(string $dbType): void
    {
        $allSql = implode("\n", $this->record($dbType)->getSqlQueries());

        match (true) {
            in_array($dbType, ['mysqli', 'pdo_mysql'], true) => $this->assertStringContainsString(
                'ALTER TABLE pmf_faqadminlog ADD COLUMN hash VARCHAR(64) AFTER text',
                $allSql,
            ),
            in_array($dbType, ['sqlsrv', 'pdo_sqlsrv'], true) => $this->assertStringContainsString(
                'ALTER TABLE pmf_faqadminlog ADD hash VARCHAR(64)',
                $allSql,
            ),
            default => $this->assertStringContainsString('ALTER TABLE pmf_faqadminlog ADD COLUMN hash VARCHAR(64)', $allSql),
        };

        if (in_array($dbType, ['mysqli', 'pdo_mysql'], true)) {
            $this->assertStringContainsString('ENGINE = InnoDB', $allSql);
            $this->assertStringNotContainsString('NVARCHAR', $allSql);
        }

        if (in_array($dbType, ['sqlsrv', 'pdo_sqlsrv'], true)) {
            $this->assertStringContainsString('NVARCHAR(255)', $allSql);
            $this->assertStringNotContainsString('ENGINE = InnoDB', $allSql);
        }
    }

    public function testNonSqlOperationsDoNotDependOnTheDialect(): void
    {
        $reference = $this->record('sqlite3');
        $referenceKeys = [
            'config_add' => $this->configKeys($reference, 'config_add'),
            'config_delete' => $this->configKeys($reference, 'config_delete'),
            'config_rename' => $this->configKeys($reference, 'config_rename'),
            'config_update' => $this->configKeys($reference, 'config_update'),
        ];
        $referencePermissions = array_map(
            static fn(array $operation): string => (string) $operation['permissionName'],
            array_values(array_filter(
                $reference->toArray(),
                static fn(array $operation): bool => $operation['type'] === 'permission_grant',
            )),
        );

        $this->assertNotSame([], $referenceKeys['config_add'], 'The release adds configuration keys.');
        $this->assertNotSame([], $referencePermissions, 'The release grants new permissions.');
        $this->assertSame(array_unique($referenceKeys['config_add']), $referenceKeys['config_add']);

        foreach (['mysqli', 'pgsql', 'sqlsrv', 'pdo_sqlite'] as $dbType) {
            $recorder = $this->record($dbType);
            foreach ($referenceKeys as $type => $keys) {
                $this->assertSame($keys, $this->configKeys($recorder, $type), $type . ' differs for ' . $dbType);
            }

            $permissions = array_map(
                static fn(array $operation): string => (string) $operation['permissionName'],
                array_values(array_filter(
                    $recorder->toArray(),
                    static fn(array $operation): bool => $operation['type'] === 'permission_grant',
                )),
            );
            $this->assertSame($referencePermissions, $permissions, 'permissions differ for ' . $dbType);
        }
    }

    public function testThePrefixIsResolvedWhenTheMigrationIsConstructed(): void
    {
        $queries = $this->record('sqlite3', 'tenant_')->getSqlQueries();

        $this->assertStringContainsString('tenant_faqadminlog', $queries[0]);
        $this->assertStringNotContainsString('pmf_', implode("\n", $queries));
    }
}
