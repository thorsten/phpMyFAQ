<?php

/**
 * Collects all operations from a migration for review or execution.
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

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Filesystem\Filesystem;

class OperationRecorder
{
    /** @var OperationInterface[] */
    private array $operations = [];

    /**
     * Create a new OperationRecorder with the given configuration and optional filesystem.
     *
     * @param Configuration $configuration Application configuration used by recorded operations.
     * @param Filesystem|null $filesystem Optional filesystem used for file and directory operations; if null, a default filesystem rooted at PMF_ROOT_DIR will be used.
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly ?Filesystem $filesystem = null,
    ) {
    }

    /**
     * Record a SQL query operation for later review or execution.
     *
     * @param string $query The SQL statement to record.
     * @param string $description Optional human-readable description of the query.
     * @return self The recorder instance for method chaining.
     */
    public function addSql(string $query, string $description = ''): self
    {
        $this->operations[] = new SqlOperation($this->configuration, $query, $description);
        return $this;
    }

    /**
     * Records a SQL operation only when the current database type matches one of the provided types.
     *
     * @param string $query The SQL query to record.
     * @param string|string[] $dbTypes One or more database types to match (e.g., 'mysqli', 'pgsql', 'sqlite3', 'sqlsrv').
     * @param string $description Optional description for the recorded operation.
     * @return self The recorder instance for chaining.
     */
    public function addSqlForDbType(string $query, string|array $dbTypes, string $description = ''): self
    {
        $dbTypes = (array) $dbTypes;
        if (in_array(Database::getType(), $dbTypes, true)) {
            $this->operations[] = new SqlOperation($this->configuration, $query, $description);
        }
        return $this;
    }

    /**
     * Record a configuration addition operation for the given key and value.
     *
     * @param string $key The configuration key to add.
     * @param mixed  $value The value to set for the configuration key.
     * @return self The current OperationRecorder instance for chaining.
     */
    public function addConfig(string $key, mixed $value): self
    {
        $this->operations[] = new ConfigAddOperation($this->configuration, $key, $value);
        return $this;
    }

    /**
     * Record a configuration delete operation.
     *
     * @param string $key The configuration key to delete.
     * @return self The recorder instance for method chaining.
     */
    public function deleteConfig(string $key): self
    {
        $this->operations[] = new ConfigDeleteOperation($this->configuration, $key);
        return $this;
    }

    /**
     * Record a configuration key rename operation.
     *
     * @param string $oldKey The existing configuration key to rename.
     * @param string $newKey The new configuration key name.
     * @return self The recorder instance for method chaining.
     */
    public function renameConfig(string $oldKey, string $newKey): self
    {
        $this->operations[] = new ConfigRenameOperation($this->configuration, $oldKey, $newKey);
        return $this;
    }

    /**
     * Record an operation that updates a configuration entry.
     *
     * Appends an operation to update the configuration identified by `$key` to the provided `$value`.
     *
     * @param string $key The configuration key to update.
     * @param mixed $value The new value for the configuration key.
     * @return self The current OperationRecorder instance for method chaining.
     */
    public function updateConfig(string $key, mixed $value): self
    {
        $this->operations[] = new ConfigUpdateOperation($this->configuration, $key, $value);
        return $this;
    }

    /**
         * Record a file copy operation for later execution or review.
         *
         * @param string $source The source file path.
         * @param string $destination The destination file path.
         * @param bool $onlyIfExists If `true`, the operation should be skipped when the source file does not exist.
         * @return self The current OperationRecorder instance for method chaining.
         */
    public function copyFile(string $source, string $destination, bool $onlyIfExists = true): self
    {
        $filesystem = $this->filesystem ?? new Filesystem(PMF_ROOT_DIR);
        $this->operations[] = new FileCopyOperation($filesystem, $source, $destination, $onlyIfExists);
        return $this;
    }

    /**
         * Record a directory copy operation for later execution or review.
         *
         * @param string $source Path to the source directory.
         * @param string $destination Path to the destination directory.
         * @param bool $onlyIfExists If `true`, the operation should be applied only when the source directory exists.
         * @return self The current OperationRecorder instance for chaining.
         */
    public function copyDirectory(string $source, string $destination, bool $onlyIfExists = true): self
    {
        $filesystem = $this->filesystem ?? new Filesystem(PMF_ROOT_DIR);
        $this->operations[] = new DirectoryCopyOperation($filesystem, $source, $destination, $onlyIfExists);
        return $this;
    }

    /**
     * Record a permission grant operation to be executed or reviewed later.
     *
     * @param string $name The internal name of the permission.
     * @param string $description A human-readable description of the permission.
     * @param int $userId The ID of the user who will receive the permission (defaults to 1).
     * @return self The current OperationRecorder instance for method chaining.
     */
    public function grantPermission(string $name, string $description, int $userId = 1): self
    {
        $this->operations[] = new PermissionGrantOperation($this->configuration, $name, $description, $userId);
        return $this;
    }

    /**
     * Record a custom migration operation.
     *
     * @param OperationInterface $operation The operation to record.
     * @return self The current OperationRecorder instance.
     */
    public function addOperation(OperationInterface $operation): self
    {
        $this->operations[] = $operation;
        return $this;
    }

    /**
     * Get all recorded operations.
     *
     * @return OperationInterface[] An array of recorded operations.
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Get recorded operations that match the given type.
     *
     * @param string $type The operation type to filter by.
     * @return OperationInterface[] The operations whose type equals the provided type.
     */
    public function getOperationsByType(string $type): array
    {
        return array_filter($this->operations, static fn(OperationInterface $op) => $op->getType() === $type);
    }

    / **
     * Collects SQL query strings from recorded SQL operations.
     *
     * @return string[] Array of SQL query strings; non-SQL entries contribute an empty string.
     */
    public function getSqlQueries(): array
    {
        return array_map(static fn(OperationInterface $op) => $op instanceof SqlOperation
            ? $op->getQuery()
            : '', $this->getOperationsByType('sql'));
    }

    /**
     * Compute counts of recorded operations grouped by their type.
     *
     * @return array<string,int> Map of operation type to the number of recorded operations.
     */
    public function getOperationCounts(): array
    {
        $counts = [];
        foreach ($this->operations as $operation) {
            $type = $operation->getType();
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        return $counts;
    }

    /**
     * Provide an array representation of all recorded operations suitable for dry-run output.
     *
     * @return array<int, array<string, mixed>> An indexed array where each element is an associative array representation of a recorded operation.
     */
    public function toArray(): array
    {
        return array_map(static fn(OperationInterface $op) => $op->toArray(), $this->operations);
    }

    /**
     * Remove all recorded operations from the recorder.
     *
     * @return self The same recorder instance for method chaining.
     */
    public function clear(): self
    {
        $this->operations = [];
        return $this;
    }

    /**
     * Get the total number of recorded operations.
     *
     * @return int The number of recorded operations.
     */
    public function count(): int
    {
        return count($this->operations);
    }
}