<?php

namespace phpMyFAQ\Setup\Migration;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class MigrationRegistryTest extends TestCase
{
    private Configuration $configuration;
    private MigrationRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createMock(Configuration::class);
        $this->registry = new MigrationRegistry($this->configuration);
    }

    public function testRegisterMigration(): void
    {
        // Just test that register returns self and doesn't throw
        $result = $this->registry->register('5.0.0', Versions\Migration320Alpha::class);

        $this->assertSame($this->registry, $result);
    }

    public function testGetVersionsReturnsArray(): void
    {
        $versions = $this->registry->getVersions();

        $this->assertIsArray($versions);
        $this->assertNotEmpty($versions);
    }

    public function testGetVersionsContainsExpectedVersions(): void
    {
        $versions = $this->registry->getVersions();

        $this->assertContains('3.2.0-alpha', $versions);
        $this->assertContains('4.0.0-alpha', $versions);
        $this->assertContains('4.2.0-alpha', $versions);
    }

    public function testGetVersionsAreSorted(): void
    {
        $versions = $this->registry->getVersions();

        $sortedVersions = $versions;
        usort($sortedVersions, 'version_compare');

        $this->assertEquals($sortedVersions, $versions);
    }

    public function testGetMigrationsReturnsArray(): void
    {
        $migrations = $this->registry->getMigrations();

        $this->assertIsArray($migrations);
    }

    public function testGetMigrationReturnsNullForUnknownVersion(): void
    {
        $migration = $this->registry->getMigration('99.0.0');

        $this->assertNull($migration);
    }

    public function testGetMigrationReturnsInstanceForKnownVersion(): void
    {
        $migration = $this->registry->getMigration('3.2.0-alpha');

        $this->assertInstanceOf(MigrationInterface::class, $migration);
    }

    public function testHasMigrationReturnsTrueForKnownVersion(): void
    {
        $this->assertTrue($this->registry->hasMigration('3.2.0-alpha'));
        $this->assertTrue($this->registry->hasMigration('4.0.0-alpha'));
    }

    public function testHasMigrationReturnsFalseForUnknownVersion(): void
    {
        $this->assertFalse($this->registry->hasMigration('99.0.0'));
        $this->assertFalse($this->registry->hasMigration(''));
    }

    public function testGetLatestVersionReturnsLastVersion(): void
    {
        $latestVersion = $this->registry->getLatestVersion();

        $this->assertNotNull($latestVersion);
        $this->assertEquals('4.2.0-alpha', $latestVersion);
    }

    public function testGetPendingMigrationsFromOldVersion(): void
    {
        $pending = $this->registry->getPendingMigrations('3.1.0');

        $this->assertNotEmpty($pending);
        $this->assertArrayHasKey('3.2.0-alpha', $pending);
    }

    public function testGetPendingMigrationsFromCurrentVersion(): void
    {
        $pending = $this->registry->getPendingMigrations('4.2.0-alpha.2');

        $this->assertEmpty($pending);
    }

    public function testGetPendingMigrationsFromMidVersion(): void
    {
        $pending = $this->registry->getPendingMigrations('4.0.0');

        // Should include versions after 4.0.0
        $this->assertArrayHasKey('4.0.5', $pending);
        $this->assertArrayHasKey('4.1.0-alpha', $pending);
        $this->assertArrayHasKey('4.2.0-alpha', $pending);

        // Should not include versions before or equal to 4.0.0
        $this->assertArrayNotHasKey('3.2.0-alpha', $pending);
        $this->assertArrayNotHasKey('4.0.0-alpha', $pending);
    }

    public function testGetUnappliedMigrationsWithNoneApplied(): void
    {
        $unapplied = $this->registry->getUnappliedMigrations([]);

        $this->assertEquals($this->registry->getMigrations(), $unapplied);
    }

    public function testGetUnappliedMigrationsWithSomeApplied(): void
    {
        $appliedVersions = ['3.2.0-alpha', '3.2.0-beta'];
        $unapplied = $this->registry->getUnappliedMigrations($appliedVersions);

        $this->assertArrayNotHasKey('3.2.0-alpha', $unapplied);
        $this->assertArrayNotHasKey('3.2.0-beta', $unapplied);
        $this->assertArrayHasKey('3.2.0-beta.2', $unapplied);
    }

    public function testGetUnappliedMigrationsWithAllApplied(): void
    {
        $appliedVersions = $this->registry->getVersions();
        $unapplied = $this->registry->getUnappliedMigrations($appliedVersions);

        $this->assertEmpty($unapplied);
    }

    public function testMigrationsCaching(): void
    {
        // First call should initialize migrations
        $migrations1 = $this->registry->getMigrations();

        // Second call should return cached migrations
        $migrations2 = $this->registry->getMigrations();

        $this->assertSame($migrations1, $migrations2);
    }

    public function testRegisterClearsMigrationsCache(): void
    {
        // Initialize cache
        $migrations1 = $this->registry->getMigrations();

        // Register a new migration
        $this->registry->register('5.0.0', Versions\Migration320Alpha::class);

        // Get migrations again - should rebuild
        $migrations2 = $this->registry->getMigrations();

        // The arrays should be different instances (cache was cleared)
        $this->assertNotSame($migrations1, $migrations2);
    }
}
