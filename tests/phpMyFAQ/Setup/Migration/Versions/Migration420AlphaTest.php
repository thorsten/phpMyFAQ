<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class Migration420AlphaTest extends TestCase
{
    private Migration420Alpha $migration;

    protected function setUp(): void
    {
        parent::setUp();

        Database::factory('pdo_sqlite');
        Database::setTablePrefix('');

        $configuration = $this->createMock(Configuration::class);
        $this->migration = new Migration420Alpha($configuration);
    }

    public function testImplementsMigrationInterface(): void
    {
        $this->assertInstanceOf(MigrationInterface::class, $this->migration);
    }

    public function testGetVersion(): void
    {
        $this->assertEquals('4.2.0-alpha', $this->migration->getVersion());
    }

    public function testGetDependencies(): void
    {
        $this->assertEquals(['4.1.0-alpha.3'], $this->migration->getDependencies());
    }

    public function testUpAddsFaqapiKeysTableSql(): void
    {
        $recorder = $this->createMock(OperationRecorder::class);
        $foundApiKeysTable = false;

        $recorder->expects($this->atLeastOnce())->method('addSql')->with($this->callback(function (string $sql) use (
            &$foundApiKeysTable,
        ): bool {
            if (str_contains($sql, 'faqapi_keys')) {
                $foundApiKeysTable = true;
            }

            return true;
        }), $this->anything());

        $this->migration->up($recorder);

        $this->assertTrue($foundApiKeysTable, 'Expected at least one SQL statement containing "faqapi_keys"');
    }

    public function testUpAddsOAuthStorageTablesSql(): void
    {
        $recorder = $this->createMock(OperationRecorder::class);
        $foundOauthTable = false;

        $recorder->expects($this->atLeastOnce())->method('addSql')->with($this->callback(function (string $sql) use (
            &$foundOauthTable,
        ): bool {
            if (str_contains($sql, 'faqoauth_clients')) {
                $foundOauthTable = true;
            }

            return true;
        }), $this->anything());

        $this->migration->up($recorder);

        $this->assertTrue($foundOauthTable, 'Expected at least one SQL statement containing "faqoauth_clients"');
    }
}
