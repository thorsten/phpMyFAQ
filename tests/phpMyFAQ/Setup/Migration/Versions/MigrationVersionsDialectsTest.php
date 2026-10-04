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
 * Records the schema migrations that branch on the database dialect without touching the
 * database, for every supported dialect, and pins the statement each dialect receives.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Migration320Alpha::class)]
#[CoversClass(Migration323::class)]
#[CoversClass(Migration400Alpha2::class)]
#[CoversClass(Migration400Alpha3::class)]
#[CoversClass(Migration400Beta2::class)]
#[CoversClass(Migration405::class)]
#[UsesNamespace('phpMyFAQ')]
final class MigrationVersionsDialectsTest extends TestCase
{
    private const array DIALECTS = ['mysqli', 'pdo_mysql', 'pgsql', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite', 'sqlsrv', 'pdo_sqlsrv'];

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
     * Expected fragments per migration and dialect family.
     *
     * @return iterable<string, array{class-string, string, list<string>}>
     */
    public static function dialectStatementProvider(): iterable
    {
        $expectations = [
            Migration320Alpha::class => [
                'mysql' => ['ADD refresh_token TEXT NULL DEFAULT NULL', 'CREATE TABLE IF NOT EXISTS pmf_faqbackup'],
                'pgsql' => ['ADD COLUMN IF NOT EXISTS twofactor_enabled', 'CREATE TABLE IF NOT EXISTS pmf_faqbackup'],
                'sqlite' => ['CREATE TABLE IF NOT EXISTS pmf_faqbackup'],
                'sqlsrv' => ['ADD refresh_token TEXT', 'CREATE TABLE pmf_faqbackup'],
            ],
            Migration323::class => [
                'mysql' => ['ALTER TABLE pmf_faquser CHANGE ip ip VARCHAR(64)'],
                'pgsql' => ['ALTER TABLE pmf_faquser ALTER COLUMN ip TYPE VARCHAR(64)'],
                'sqlite' => ['CREATE TABLE pmf_faquser_new', 'INSERT INTO pmf_faquser_new SELECT * FROM pmf_faquser', 'ALTER TABLE pmf_faquser_new RENAME TO pmf_faquser'],
                'sqlsrv' => ['ALTER TABLE pmf_faquser ALTER COLUMN ip VARCHAR(64)'],
            ],
            Migration400Alpha2::class => [
                'mysql' => ['CREATE TABLE IF NOT EXISTS pmf_faqforms', 'form_id INT(1) NOT NULL'],
                'pgsql' => ['pmf_faqforms'],
                'sqlite' => ['pmf_faqforms'],
                'sqlsrv' => ['CREATE TABLE pmf_faqforms', 'input_type NVARCHAR(1000) NOT NULL'],
            ],
            Migration400Alpha3::class => [
                'mysql' => ['CREATE TABLE IF NOT EXISTS pmf_faqseo', 'ENGINE = InnoDB'],
                'pgsql' => ['pmf_faqseo'],
                'sqlite' => ['CREATE TABLE IF NOT EXISTS pmf_faqseo'],
                'sqlsrv' => ['CREATE TABLE pmf_faqseo', 'DEFAULT GETDATE()'],
            ],
            Migration400Beta2::class => [
                'mysql' => ['ALTER TABLE pmf_faquser ADD webauthnkeys TEXT NULL DEFAULT NULL'],
                'pgsql' => ['ALTER TABLE pmf_faquser ADD COLUMN IF NOT EXISTS webauthnkeys TEXT NULL DEFAULT NULL'],
                'sqlite' => ['ALTER TABLE pmf_faquser ADD COLUMN webauthnkeys TEXT NULL DEFAULT NULL'],
                'sqlsrv' => ['ALTER TABLE pmf_faquser ADD webauthnkeys TEXT NULL DEFAULT NULL'],
            ],
            Migration405::class => [
                'mysql' => ['ALTER TABLE pmf_faqforms CHANGE input_label input_label VARCHAR(500) NOT NULL'],
                'pgsql' => ['ALTER TABLE pmf_faqforms ALTER COLUMN input_label TYPE VARCHAR(500)', 'ALTER COLUMN input_label SET NOT NULL'],
                'sqlite' => ['ALTER TABLE pmf_faqforms RENAME TO pmf_faqforms_old', 'DROP TABLE pmf_faqforms_old'],
                'sqlsrv' => ['ALTER TABLE pmf_faqforms ALTER COLUMN input_label NVARCHAR(500) NOT NULL'],
            ],
        ];

        foreach ($expectations as $migration => $byFamily) {
            foreach (self::DIALECTS as $dialect) {
                $family = match (true) {
                    str_contains($dialect, 'mysql') => 'mysql',
                    str_contains($dialect, 'pgsql') => 'pgsql',
                    str_contains($dialect, 'sqlite') => 'sqlite',
                    default => 'sqlsrv',
                };

                yield substr($migration, strrpos($migration, '\\') + 1) . ' on ' . $dialect => [
                    $migration,
                    $dialect,
                    $byFamily[$family],
                ];
            }
        }
    }

    /**
     * @param class-string $migrationClass
     * @param list<string> $expectedFragments
     */
    #[DataProvider('dialectStatementProvider')]
    public function testEveryDialectReceivesItsOwnStatements(
        string $migrationClass,
        string $dialect,
        array $expectedFragments,
    ): void {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dialect);
        Database::setTablePrefix('pmf_');

        $configuration = $this->createStub(Configuration::class);
        $recorder = new OperationRecorder($configuration);
        new $migrationClass($configuration)->up($recorder);

        $queries = $recorder->getSqlQueries();
        $this->assertNotEmpty($queries);
        $joined = implode("\n", $queries);

        foreach ($expectedFragments as $fragment) {
            $this->assertStringContainsString($fragment, $joined, $migrationClass . ' on ' . $dialect);
        }

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression(
                '/(?<![\w.])faq[a-z_]+/',
                $query,
                'Every table is addressed with the prefix: ' . $query,
            );
        }
    }
}
