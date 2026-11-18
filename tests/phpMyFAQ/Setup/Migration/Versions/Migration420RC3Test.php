<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\ConfigAddOperation;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\TestCase;

class Migration420RC3Test extends TestCase
{
    private Migration420RC3 $migration;

    private Configuration $configuration;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        Database::factory('pdo_sqlite');
        Database::setTablePrefix('');

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-migration-420-rc3-');
        self::assertNotFalse($this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->migration = new Migration420RC3($this->configuration);
    }

    protected function tearDown(): void
    {
        $this->configuration->getDb()->close();

        parent::tearDown();

        @unlink($this->databaseFile);
    }

    public function testImplementsMigrationInterface(): void
    {
        $this->assertInstanceOf(MigrationInterface::class, $this->migration);
    }

    public function testGetVersion(): void
    {
        $this->assertSame('4.2.0-RC.3', $this->migration->getVersion());
    }

    public function testDependsOnTheRC2Migration(): void
    {
        $this->assertSame(['4.2.0-RC.2'], $this->migration->getDependencies());
    }

    public function testGetDescriptionNamesTheDecoySecret(): void
    {
        $this->assertStringContainsString('security.webAuthnDecoySecret', $this->migration->getDescription());
    }

    public function testUpAddsARandomDecoySecret(): void
    {
        $first = $this->recordedDecoySecrets();
        $second = $this->recordedDecoySecrets();

        $this->assertCount(1, $first);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $first[0]);
        $this->assertNotSame($first[0], $second[0], 'Every installation needs a secret of its own.');
    }

    /**
     * @return list<string>
     */
    private function recordedDecoySecrets(): array
    {
        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $secrets = [];
        foreach ($recorder->getOperations() as $operation) {
            if ($operation instanceof ConfigAddOperation && $operation->getKey() === 'security.webAuthnDecoySecret') {
                $secrets[] = (string) $operation->getValue();
            }
        }

        return $secrets;
    }
}
