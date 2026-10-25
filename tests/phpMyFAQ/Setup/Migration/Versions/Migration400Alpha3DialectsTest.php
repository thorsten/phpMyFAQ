<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\Operations\ConfigRenameOperation;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * The SEO table is created per dialect; the configuration changes after it must be recorded for every dialect.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Migration400Alpha3::class)]
#[UsesNamespace('phpMyFAQ')]
final class Migration400Alpha3DialectsTest extends TestCase
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

    #[DataProvider('dialectProvider')]
    public function testUpRecordsSeoTableAndConfigurationChanges(string $dbType): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);
        Database::setTablePrefix('pmf_');

        $configuration = $this->createStub(Configuration::class);
        $recorder = new OperationRecorder($configuration);
        new Migration400Alpha3($configuration)->up($recorder);

        $renamed = [];
        foreach ($recorder->getOperations() as $operation) {
            if ($operation instanceof ConfigRenameOperation) {
                $renamed[$operation->getOldKey()] = $operation->getNewKey();
            }
        }

        $this->assertStringContainsString('pmf_faqseo', implode("\n", $recorder->getSqlQueries()));
        $this->assertSame('layout.templateSet', $renamed['main.templateSet'] ?? null);
        $this->assertSame('layout.contactInformationHTML', $renamed['main.contactInformationHTML'] ?? null);
    }
}
