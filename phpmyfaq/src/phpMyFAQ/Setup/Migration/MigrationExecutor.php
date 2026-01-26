<?php

/**
 * Executes migrations with tracking and dry-run support.
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
use phpMyFAQ\Filesystem\Filesystem;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use Throwable;

class MigrationExecutor
{
    private bool $dryRun = false;

    /** @var MigrationResult[] */
    private array $results = [];

    public function __construct(
        private readonly Configuration $configuration,
        private readonly MigrationTracker $tracker,
        private readonly ?Filesystem $filesystem = null,
    ) {
    }

    /**
     * Sets dry-run mode.
     */
    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    /**
     * Returns whether dry-run mode is enabled.
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Executes a single migration.
     */
    public function executeMigration(MigrationInterface $migration): MigrationResult
    {
        $result = new MigrationResult($migration->getVersion(), $migration->getDescription());
        $result->setDryRun($this->dryRun);

        $startTime = microtime(true);

        try {
            // Record all operations
            $recorder = new OperationRecorder($this->configuration, $this->filesystem);
            $migration->up($recorder);

            // Execute or simulate operations
            foreach ($recorder->getOperations() as $operation) {
                if ($this->dryRun) {
                    $result->addOperationResult($operation, true);
                } else {
                    $success = $operation->execute();
                    $error = $success ? null : 'Operation failed';
                    $result->addOperationResult($operation, $success, $error);

                    if (!$success) {
                        $result->setErrorMessage("Failed at operation: {$operation->getDescription()}");
                        break;
                    }
                }
            }

            // Track the migration if successful and not in dry-run mode
            if ($result->isSuccess() && !$this->dryRun) {
                $executionTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                $this->tracker->recordMigration(
                    $migration->getVersion(),
                    $executionTimeMs,
                    $migration instanceof AbstractMigration ? $migration->getChecksum() : null,
                    $migration->getDescription(),
                );
            }
        } catch (Throwable $e) {
            $result->setSuccess(false);
            $result->setErrorMessage($e->getMessage());
        }

        $result->setExecutionTimeMs((microtime(true) - $startTime) * 1000);
        $this->results[] = $result;

        return $result;
    }

    /**
     * Executes multiple migrations in order.
     *
     * @param MigrationInterface[] $migrations
     * @return MigrationResult[]
     */
    public function executeMigrations(array $migrations): array
    {
        $results = [];

        foreach ($migrations as $migration) {
            $result = $this->executeMigration($migration);
            $results[] = $result;

            // Stop on failure unless in dry-run mode
            if (!$result->isSuccess() && !$this->dryRun) {
                break;
            }
        }

        return $results;
    }

    /**
     * Collects operations from migrations without executing them.
     *
     * @param MigrationInterface[] $migrations
     * @return array<string, array{migration: MigrationInterface, operations: array<int, array<string, mixed>>}>
     */
    public function collectOperations(array $migrations): array
    {
        $collected = [];

        foreach ($migrations as $version => $migration) {
            $recorder = new OperationRecorder($this->configuration, $this->filesystem);
            $migration->up($recorder);

            $collected[$version] = [
                'migration' => $migration,
                'operations' => $recorder->toArray(),
            ];
        }

        return $collected;
    }

    /**
     * Returns all execution results.
     *
     * @return MigrationResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Clears stored results.
     */
    public function clearResults(): self
    {
        $this->results = [];
        return $this;
    }

    /**
     * Generates a dry-run report for the given migrations.
     *
     * @param MigrationInterface[] $migrations
     * @return array<string, mixed>
     */
    public function generateDryRunReport(array $migrations): array
    {
        $report = [
            'migrations' => [],
            'summary' => [
                'migrationCount' => 0,
                'totalOperations' => 0,
                'operationsByType' => [],
            ],
        ];

        foreach ($migrations as $version => $migration) {
            $recorder = new OperationRecorder($this->configuration, $this->filesystem);
            $migration->up($recorder);

            $operations = $recorder->toArray();
            $counts = $recorder->getOperationCounts();

            $report['migrations'][$version] = [
                'description' => $migration->getDescription(),
                'operationCount' => count($operations),
                'operationsByType' => $counts,
                'operations' => $operations,
            ];

            $report['summary']['migrationCount']++;
            $report['summary']['totalOperations'] += count($operations);

            foreach ($counts as $type => $count) {
                $report['summary']['operationsByType'][$type] =
                    ($report['summary']['operationsByType'][$type] ?? 0) + $count;
            }
        }

        return $report;
    }

    /**
     * Formats the dry-run report as a human-readable string.
     *
     * @param array<string, mixed> $report
     */
    public function formatDryRunReport(array $report): string
    {
        $output = "=== Migration Dry-Run Report ===\n\n";

        foreach ($report['migrations'] as $version => $migrationData) {
            $output .= "Version: $version\n";
            $output .= "Description: {$migrationData['description']}\n\n";

            // Group operations by type
            $byType = [];
            foreach ($migrationData['operations'] as $op) {
                $byType[$op['type']][] = $op;
            }

            // SQL Operations
            if (!empty($byType['sql'])) {
                $output .= '--- SQL Operations (' . count($byType['sql']) . ") ---\n";
                foreach ($byType['sql'] as $i => $op) {
                    $output .= ($i + 1) . ". {$op['description']}\n";
                    $output .= '   ' . $this->truncateQuery($op['query']) . "\n";
                }
                $output .= "\n";
            }

            // Config Operations
            $configOps = array_merge(
                $byType['config_add'] ?? [],
                $byType['config_delete'] ?? [],
                $byType['config_rename'] ?? [],
                $byType['config_update'] ?? [],
            );
            if (!empty($configOps)) {
                $output .= '--- Configuration Changes (' . count($configOps) . ") ---\n";
                foreach ($configOps as $i => $op) {
                    $output .= ($i + 1) . ". {$op['description']}\n";
                }
                $output .= "\n";
            }

            // File Operations
            $fileOps = array_merge($byType['file_copy'] ?? [], $byType['directory_copy'] ?? []);
            if (!empty($fileOps)) {
                $output .= '--- File Operations (' . count($fileOps) . ") ---\n";
                foreach ($fileOps as $i => $op) {
                    $output .= ($i + 1) . ". {$op['description']}\n";
                }
                $output .= "\n";
            }

            // Permission Operations
            if (!empty($byType['permission_grant'])) {
                $output .= '--- Permission Changes (' . count($byType['permission_grant']) . ") ---\n";
                foreach ($byType['permission_grant'] as $i => $op) {
                    $output .= ($i + 1) . ". {$op['description']}\n";
                }
                $output .= "\n";
            }

            $output .= str_repeat('-', 50) . "\n\n";
        }

        // Summary
        $output .= "=== Summary ===\n";
        $output .= "Migrations: {$report['summary']['migrationCount']}\n";
        $output .= "Total Operations: {$report['summary']['totalOperations']}\n";

        if (!empty($report['summary']['operationsByType'])) {
            $output .= "By Type:\n";
            foreach ($report['summary']['operationsByType'] as $type => $count) {
                $output .= "  - $type: $count\n";
            }
        }

        return $output;
    }

    private function truncateQuery(string $query, int $maxLength = 100): string
    {
        $sanitized = preg_replace('/\s+/', ' ', trim($query)) ?? '';
        if (strlen($sanitized) > $maxLength) {
            return substr($sanitized, 0, $maxLength - 3) . '...';
        }
        return $sanitized;
    }
}
