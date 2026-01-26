<?php

/**
 * Result of a single migration execution.
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

use phpMyFAQ\Setup\Migration\Operations\OperationInterface;

class MigrationResult
{
    /** @var array<int, array{operation: OperationInterface, success: bool, error: string|null}> */
    private array $operationResults = [];

    private bool $success = true;
    private ?string $errorMessage = null;
    private float $executionTimeMs = 0;
    private bool $dryRun = false;

    /**
     * Create a MigrationResult for a specific migration version and description.
     *
     * @param string $version Migration identifier (typically a version string or timestamp).
     * @param string $description Human-readable description of the migration.
     */
    public function __construct(
        private readonly string $version,
        private readonly string $description,
    ) {
    }

    /**
     * Get the migration's version identifier.
     *
     * @return string The migration version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Gets the migration description.
     *
     * @return string The migration description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Indicates whether the migration completed successfully.
     *
     * @return bool `true` if the migration and all recorded operations succeeded, `false` otherwise.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Set the overall success flag for this migration result.
     *
     * @param bool $success `true` if the migration is successful, `false` otherwise.
     * @return self The current instance for method chaining.
     */
    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    /**
     * Get the aggregate error message for the migration, if one was recorded.
     *
     * @return string|null The aggregate error message if present, `null` otherwise.
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Set the aggregate error message for this migration result.
     *
     * @param string|null $errorMessage The aggregate error message, or `null` to clear it.
     * @return self Fluent instance for method chaining.
     */
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * Get the total execution time of the migration in milliseconds.
     *
     * @return float The execution time in milliseconds.
     */
    public function getExecutionTimeMs(): float
    {
        return $this->executionTimeMs;
    }

    /**
     * Set the total execution time of the migration in milliseconds.
     *
     * @param float $executionTimeMs Total execution time in milliseconds.
     * @return self The current instance for method chaining.
     */
    public function setExecutionTimeMs(float $executionTimeMs): self
    {
        $this->executionTimeMs = $executionTimeMs;
        return $this;
    }

    /**
     * Indicates whether the migration was executed as a dry run.
     *
     * @return bool `true` if this result represents a dry run, `false` otherwise.
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Set whether this migration result represents a dry run.
     *
     * @param bool $dryRun True if the migration was a dry run, false otherwise.
     * @return self The current MigrationResult instance.
     */
    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    /**
     * Record the result of a single migration operation.
     *
     * If the operation failed ($success is false), the overall migration success flag
     * is set to false.
     *
     * @param OperationInterface $operation The operation that was executed.
     * @param bool $success `true` if the operation succeeded, `false` otherwise.
     * @param string|null $error Optional error message for a failed operation.
     * @return self The current MigrationResult instance (fluent interface).
     */
    public function addOperationResult(OperationInterface $operation, bool $success, ?string $error = null): self
    {
        $this->operationResults[] = [
            'operation' => $operation,
            'success' => $success,
            'error' => $error,
        ];
        if (!$success) {
            $this->success = false;
        }
        return $this;
    }

    /**
         * Retrieve recorded per-operation results for this migration.
         *
         * @return array<int, array{operation: OperationInterface, success: bool, error: string|null}> An indexed array where each entry contains:
         *                                                                                     - `operation`: the OperationInterface instance
         *                                                                                     - `success`: `true` if the operation succeeded, `false` otherwise
         *                                                                                     - `error`: an error message or `null` if none
         */
    public function getOperationResults(): array
    {
        return $this->operationResults;
    }

    /**
     * Count successful operations recorded for this migration.
     *
     * @return int The number of operation results with success = true.
     */
    public function getSuccessCount(): int
    {
        return count(array_filter($this->operationResults, static fn($r) => $r['success']));
    }

    /**
         * Counts failed operation results.
         *
         * @return int The number of recorded operations whose `success` flag is false.
         */
    public function getFailureCount(): int
    {
        return count(array_filter($this->operationResults, static fn($r) => !$r['success']));
    }

    /**
     * Total number of operations recorded for this migration.
     *
     * @return int The number of recorded operation results.
     */
    public function getOperationCount(): int
    {
        return count($this->operationResults);
    }

    /**
     * Produce a serializable associative array representing this migration result.
     *
     * The array contains top-level metadata (version, description), execution state
     * (success, dryRun, errorMessage, executionTimeMs), aggregate counts
     * (operationCount, successCount, failureCount), and an `operations` list with
     * per-operation entries.
     *
     * @return array<string, mixed> {
     *     @type string  $version          Migration version.
     *     @type string  $description      Migration description.
     *     @type bool    $success          Overall success flag.
     *     @type bool    $dryRun           Whether the migration was a dry run.
     *     @type string|null $errorMessage Aggregate error message or null.
     *     @type float   $executionTimeMs  Total execution time in milliseconds.
     *     @type int     $operationCount   Total number of operations recorded.
     *     @type int     $successCount     Number of successful operations.
     *     @type int     $failureCount     Number of failed operations.
     *     @type array[] $operations       List of per-operation arrays with keys:
     *                                     - `type` (string)
     *                                     - `description` (string)
     *                                     - `success` (bool)
     *                                     - `error` (string|null)
     * }
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'description' => $this->description,
            'success' => $this->success,
            'dryRun' => $this->dryRun,
            'errorMessage' => $this->errorMessage,
            'executionTimeMs' => $this->executionTimeMs,
            'operationCount' => $this->getOperationCount(),
            'successCount' => $this->getSuccessCount(),
            'failureCount' => $this->getFailureCount(),
            'operations' => array_map(static fn($r) => [
                'type' => $r['operation']->getType(),
                'description' => $r['operation']->getDescription(),
                'success' => $r['success'],
                'error' => $r['error'],
            ], $this->operationResults),
        ];
    }
}