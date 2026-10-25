<?php

namespace phpMyFAQ\Setup\Installation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\MigrationRegistry;
use phpMyFAQ\Setup\Migration\Operations\ConfigAddOperation;
use phpMyFAQ\Setup\Migration\Operations\ConfigDeleteOperation;
use phpMyFAQ\Setup\Migration\Operations\ConfigRenameOperation;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Fresh installations only run the DefaultDataSeeder, never the migrations. Every configuration
 * key a migration adds for updated installations must therefore be seeded as well.
 */
#[AllowMockObjectsWithoutExpectations]
class DefaultDataSeederMigrationParityTest extends TestCase
{
    private mixed $previousDatabaseType = null;

    protected function setUp(): void
    {
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dialectProvider(): iterable
    {
        foreach (['pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'pdo_sqlsrv'] as $type) {
            yield $type => [$type];
        }
    }

    #[DataProvider('dialectProvider')]
    public function testSeederContainsEveryConfigKeyAddedByMigrations(string $dbType): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);

        $configuration = $this->createStub(Configuration::class);
        $registry = new MigrationRegistry($configuration);

        $keys = [];
        foreach ($registry->getMigrations() as $migration) {
            $recorder = new OperationRecorder($configuration);
            $migration->up($recorder);

            foreach ($recorder->getOperations() as $operation) {
                if ($operation instanceof ConfigAddOperation) {
                    $keys[$operation->getKey()] = true;
                }

                if ($operation instanceof ConfigDeleteOperation) {
                    unset($keys[$operation->getKey()]);
                }

                if ($operation instanceof ConfigRenameOperation) {
                    unset($keys[$operation->getOldKey()]);
                    $keys[$operation->getNewKey()] = true;
                }
            }
        }

        $seededKeys = array_keys(new DefaultDataSeeder()->getMainConfig());
        $missing = array_values(array_diff(array_keys($keys), $seededKeys));
        sort($missing);

        $this->assertSame([], $missing, 'Keys added by migrations but missing from DefaultDataSeeder');
    }
}
