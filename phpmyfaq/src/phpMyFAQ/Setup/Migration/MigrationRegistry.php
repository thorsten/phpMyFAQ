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

    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->registerDefaultMigrations();
    }

    /**
     * Registers a migration class.
     *
     * @param class-string<MigrationInterface> $className
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
     * Returns all registered migrations, sorted by version.
     *
     * @return array<string, MigrationInterface>
     */
    public function getMigrations(): array
    {
        if ($this->migrations !== null) {
            return $this->migrations;
        }

        $this->migrations = [];

        foreach ($this->migrationClasses as $version => $className) {
            if (!class_exists($className)) {
                continue;
            }

            $this->migrations[$version] = new $className($this->configuration);
        }

        // Sort by version
        uksort($this->migrations, 'version_compare');

        return $this->migrations;
    }

    /**
     * Returns a specific migration by version.
     */
    public function getMigration(string $version): ?MigrationInterface
    {
        $migrations = $this->getMigrations();
        return $migrations[$version] ?? null;
    }

    /**
     * Returns all versions in order.
     *
     * @return string[]
     */
    public function getVersions(): array
    {
        return array_keys($this->getMigrations());
    }

    /**
     * Returns migrations that need to be applied to get from $currentVersion to the latest.
     *
     * @return MigrationInterface[]
     */
    public function getPendingMigrations(string $currentVersion): array
    {
        $pending = [];

        foreach ($this->getMigrations() as $version => $migration) {
            if (!version_compare($currentVersion, $version, '<')) {
                continue;
            }

            $pending[$version] = $migration;
        }

        return $pending;
    }

    /**
     * Returns migrations that need to be applied based on what's already tracked.
     *
     * @param string[] $appliedVersions
     * @return MigrationInterface[]
     */
    public function getUnappliedMigrations(array $appliedVersions): array
    {
        $unapplied = [];

        foreach ($this->getMigrations() as $version => $migration) {
            if (in_array($version, $appliedVersions, true)) {
                continue;
            }

            $unapplied[$version] = $migration;
        }

        return $unapplied;
    }

    /**
     * Returns the latest migration version.
     */
    public function getLatestVersion(): ?string
    {
        $versions = $this->getVersions();
        return !empty($versions) ? end($versions) : null;
    }

    /**
     * Checks if a migration version exists.
     */
    public function hasMigration(string $version): bool
    {
        return isset($this->migrationClasses[$version]);
    }
}
