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

    public function __construct(
        private readonly string $version,
        private readonly string $description,
    ) {
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getExecutionTimeMs(): float
    {
        return $this->executionTimeMs;
    }

    public function setExecutionTimeMs(float $executionTimeMs): self
    {
        $this->executionTimeMs = $executionTimeMs;
        return $this;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    /**
     * Adds an operation result.
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
     * Returns all operation results.
     *
     * @return array<int, array{operation: OperationInterface, success: bool, error: string|null}>
     */
    public function getOperationResults(): array
    {
        return $this->operationResults;
    }

    /**
     * Returns the count of successful operations.
     */
    public function getSuccessCount(): int
    {
        return count(array_filter($this->operationResults, static fn($r) => $r['success']));
    }

    /**
     * Returns the count of failed operations.
     */
    public function getFailureCount(): int
    {
        return count(array_filter($this->operationResults, static fn($r) => !$r['success']));
    }

    /**
     * Returns the total operation count.
     */
    public function getOperationCount(): int
    {
        return count($this->operationResults);
    }

    /**
     * Converts the result to an array for serialization.
     *
     * @return array<string, mixed>
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
