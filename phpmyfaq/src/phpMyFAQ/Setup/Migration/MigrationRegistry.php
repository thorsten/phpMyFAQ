<?php

/**
 * Discovers and orders migrations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration;

use phpMyFAQ\Configuration;

class MigrationRegistry
{
    /** @var array<string, class-string<MigrationInterface>> */
    private array $migrationClasses = [];

    /** @var array<string, MigrationInterface>|null */
    private ?array $migrations = null;

    /**
     * Create a MigrationRegistry and register the default migration mappings.
     *
     * Stores the provided Configuration for use when instantiating migration classes
     * and populates the registry with the built-in migrations.
     *
     * @param Configuration $configuration Configuration used to instantiate migration classes.
     */
    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->registerDefaultMigrations();
    }

    /**
         * Register a migration class for the given version.
         *
         * Also clears the cached instantiated migrations so the new registration is picked up.
         *
         * @param string $version The version identifier for the migration.
         * @param class-string<MigrationInterface> $className Fully-qualified migration class name.
         * @return $this The current MigrationRegistry instance for fluent chaining.
         */
    public function register(string $version, string $className): self
    {
        $this->migrationClasses[$version] = $className;
        $this->migrations = null; // Reset cache
        return $this;
    }

    /**
     * Registers all migrations in the Versions directory.
     */
    private function registerDefaultMigrations(): void
    {
        $this->migrationClasses = [
            '3.2.0-alpha' => Versions\Migration320Alpha::class,
            '3.2.0-beta' => Versions\Migration320Beta::class,
            '3.2.0-beta.2' => Versions\Migration320Beta2::class,
            '3.2.0-RC' => Versions\Migration320RC::class,
            '3.2.3' => Versions\Migration323::class,
            '4.0.0-alpha' => Versions\Migration400Alpha::class,
            '4.0.0-alpha.2' => Versions\Migration400Alpha2::class,
            '4.0.0-alpha.3' => Versions\Migration400Alpha3::class,
            '4.0.0-beta.2' => Versions\Migration400Beta2::class,
            '4.0.5' => Versions\Migration405::class,
            '4.0.7' => Versions\Migration407::class,
            '4.0.9' => Versions\Migration409::class,
            '4.1.0-alpha' => Versions\Migration410Alpha::class,
            '4.1.0-alpha.2' => Versions\Migration410Alpha2::class,
            '4.1.0-alpha.3' => Versions\Migration410Alpha3::class,
            '4.2.0-alpha' => Versions\Migration420Alpha::class,
        ];
    }

    /**
     * Get all registered migrations ordered by version.
     *
     * Builds and caches MigrationInterface instances (constructed with the registry's Configuration)
     * for each registered migration class that exists, then returns them keyed by version.
     *
     * @return array<string, MigrationInterface> Associative array mapping version strings to MigrationInterface instances, ordered by version.
     */
    public function getMigrations(): array
    {
        if ($this->migrations !== null) {
            return $this->migrations;
        }

        $this->migrations = [];

        foreach ($this->migrationClasses as $version => $className) {
            if (class_exists($className)) {
                $this->migrations[$version] = new $className($this->configuration);
            }
        }

        // Sort by version
        uksort($this->migrations, 'version_compare');

        return $this->migrations;
    }

    /**
     * Retrieve the migration instance for a given version.
     *
     * @param string $version The migration version identifier.
     * @return MigrationInterface|null The migration instance for the version, or `null` if no migration is registered for it.
     */
    public function getMigration(string $version): ?MigrationInterface
    {
        $migrations = $this->getMigrations();
        return $migrations[$version] ?? null;
    }

    /**
     * List registered migration version strings in ascending version order.
     *
     * @return string[] Array of migration version strings ordered from lowest to highest.
     */
    public function getVersions(): array
    {
        return array_keys($this->getMigrations());
    }

    /**
         * Get migrations with versions newer than the provided current version.
         *
         * @param string $currentVersion The current installed version to compare from.
         * @return MigrationInterface[] Migrations whose version is greater than `$currentVersion`, keyed by version string.
         */
    public function getPendingMigrations(string $currentVersion): array
    {
        $pending = [];

        foreach ($this->getMigrations() as $version => $migration) {
            if (version_compare($currentVersion, $version, '<')) {
                $pending[$version] = $migration;
            }
        }

        return $pending;
    }

    /**
         * Determine migrations that are not present in the provided applied versions.
         *
         * @param string[] $appliedVersions List of migration version strings that have already been applied.
         * @return MigrationInterface[] Migration instances keyed by version for migrations not listed in `$appliedVersions`.
         */
    public function getUnappliedMigrations(array $appliedVersions): array
    {
        $unapplied = [];

        foreach ($this->getMigrations() as $version => $migration) {
            if (!in_array($version, $appliedVersions, true)) {
                $unapplied[$version] = $migration;
            }
        }

        return $unapplied;
    }

    /**
     * Get the most recent registered migration version.
     *
     * @return string|null The latest migration version string, or `null` if no migrations are registered.
     */
    public function getLatestVersion(): ?string
    {
        $versions = $this->getVersions();
        return !empty($versions) ? end($versions) : null;
    }

    /**
     * Determine whether a migration is registered for the given version.
     *
     * @param string $version The migration version string to check.
     * @return bool `true` if a migration for the version is registered, `false` otherwise.
     */
    public function hasMigration(string $version): bool
    {
        return isset($this->migrationClasses[$version]);
    }
}