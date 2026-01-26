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

class MigrationExecutor
{
    private bool $dryRun = false;

    /** @var MigrationResult[] */
    private array $results = [];

    /**
     * Create a MigrationExecutor with its required dependencies.
     *
     * The provided Configuration and MigrationTracker are used for migration execution
     * and persistent tracking. The Filesystem is optional and used for file-related
     * operations during migrations when present.
     *
     * @param ?Filesystem $filesystem Optional filesystem used for file and directory operations.
     */
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
     * Indicates whether migrations will be simulated without applying changes.
     *
     * @return bool `true` if dry-run mode is enabled, `false` otherwise.
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Execute a single migration and collect per-operation results.
     *
     * Runs the migration's up() to gather operations, then simulates them when in dry-run mode
     * or executes them otherwise. On success (and when not in dry-run), the migration is recorded
     * with the tracker. The produced MigrationResult is stored internally and returned.
     *
     * @param MigrationInterface $migration The migration instance to execute.
     * @return MigrationResult The result containing per-operation outcomes, total execution time (ms),
     *                         dry-run flag, and any error message if execution failed.
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
        } catch (\Exception $e) {
            $result->setSuccess(false);
            $result->setErrorMessage($e->getMessage());
        }

        $result->setExecutionTimeMs((microtime(true) - $startTime) * 1000);
        $this->results[] = $result;

        return $result;
    }

    /**
     * Executes each migration in the given order, collecting and returning their results.
     *
     * Execution stops after the first failed migration unless dry-run mode is enabled.
     *
     * @param MigrationInterface[] $migrations Migrations to execute, in desired order.
     * @return MigrationResult[] Array of results for the migrations that were processed.
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
     * Collects operations produced by each migration's up() method without executing them.
     *
     * @param MigrationInterface[] $migrations Array of migrations keyed by version.
     * @return array<string, array{migration: MigrationInterface, operations: array<int, array<string, mixed>>}> Array keyed by migration version. Each entry contains:
     *     - `migration`: the MigrationInterface instance
     *     - `operations`: an indexed array of operation representations produced by the migration
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
     * Get accumulated migration execution results.
     *
     * @return MigrationResult[] The collected MigrationResult objects in execution order.
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Clear all accumulated migration results.
     *
     * @return $this The current executor instance for method chaining.
     */
    public function clearResults(): self
    {
        $this->results = [];
        return $this;
    }

    /**
     * Generate a dry-run report summarizing operations produced by each migration.
     *
     * The report contains two top-level keys:
     * - `migrations` (array): keyed by migration version; each entry includes:
     *   - `description` (string): migration description
     *   - `operationCount` (int): total operations for the migration
     *   - `operationsByType` (array): counts per operation type
     *   - `operations` (array): the recorded operations
     * - `summary` (array): aggregated totals with keys:
     *   - `migrationCount` (int)
     *   - `totalOperations` (int)
     *   - `operationsByType` (array) aggregated across all migrations
     *
     * @param MigrationInterface[] $migrations Migrations keyed by version to include in the report.
     * @return array<string,mixed> The aggregated dry-run report with `migrations` and `summary` keys.
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
     * Produce a human-readable text report for a dry-run migration report.
     *
     * The output contains a per-migration section (version, description and grouped
     * operations: SQL, configuration changes, file operations, permission changes)
     * followed by a summary with migration and operation counts and a breakdown by
     * operation type.
     *
     * @param array<string,mixed> $report Dry-run report produced by generateDryRunReport(), expected to contain:
     *                                   - 'migrations': array keyed by version with ['description' => string, 'operations' => array]
     *                                   - 'summary': array with 'migrationCount', 'totalOperations' and optional 'operationsByType'
     * @return string Formatted multi-line human-readable report.
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

    /**
     * Normalize whitespace in a SQL query and shorten it to a maximum length.
     *
     * The query's consecutive whitespace characters are collapsed to single spaces and surrounding
     * whitespace is trimmed. If the result exceeds `$maxLength`, it is truncated and an
     * ellipsis (`...`) is appended so the returned string length does not exceed `$maxLength`.
     *
     * @param string $query The SQL query to normalize and truncate.
     * @param int $maxLength The maximum allowed length of the returned string (including the ellipsis). Defaults to 100.
     * @return string The normalized, possibly truncated query.
     */
    private function truncateQuery(string $query, int $maxLength = 100): string
    {
        $query = preg_replace('/\s+/', ' ', trim($query));
        if (strlen($query) > $maxLength) {
            return substr($query, 0, $maxLength - 3) . '...';
        }
        return $query;
    }
}