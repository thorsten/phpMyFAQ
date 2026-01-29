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

    public function __construct(
        private readonly Configuration $configuration,
        private readonly ?Filesystem $filesystem = null,
    ) {
    }

    /**
     * Records a SQL query operation.
     */
    public function addSql(string $query, string $description = ''): self
    {
        $this->operations[] = new SqlOperation($this->configuration, $query, $description);
        return $this;
    }

    /**
     * Records a SQL query operation with database type condition.
     *
     * @param string|string[] $dbTypes One or more database types (mysqli, pgsql, sqlite3, sqlsrv)
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
     * Records a configuration add operation.
     */
    public function addConfig(string $key, mixed $value): self
    {
        $this->operations[] = new ConfigAddOperation($this->configuration, $key, $value);
        return $this;
    }

    /**
     * Records a configuration delete operation.
     */
    public function deleteConfig(string $key): self
    {
        $this->operations[] = new ConfigDeleteOperation($this->configuration, $key);
        return $this;
    }

    /**
     * Records a configuration rename operation.
     */
    public function renameConfig(string $oldKey, string $newKey): self
    {
        $this->operations[] = new ConfigRenameOperation($this->configuration, $oldKey, $newKey);
        return $this;
    }

    /**
     * Records a configuration update operation.
     */
    public function updateConfig(string $key, mixed $value): self
    {
        $this->operations[] = new ConfigUpdateOperation($this->configuration, $key, $value);
        return $this;
    }

    /**
     * Records a file copy operation.
     */
    public function copyFile(string $source, string $destination, bool $onlyIfExists = true): self
    {
        $filesystem = $this->filesystem ?? new Filesystem(PMF_ROOT_DIR);
        $this->operations[] = new FileCopyOperation($filesystem, $source, $destination, $onlyIfExists);
        return $this;
    }

    /**
     * Records a directory copy operation.
     */
    public function copyDirectory(string $source, string $destination, bool $onlyIfExists = true): self
    {
        $filesystem = $this->filesystem ?? new Filesystem(PMF_ROOT_DIR);
        $this->operations[] = new DirectoryCopyOperation($filesystem, $source, $destination, $onlyIfExists);
        return $this;
    }

    /**
     * Records a permission grant operation.
     */
    public function grantPermission(string $name, string $description, int $userId = 1): self
    {
        $this->operations[] = new PermissionGrantOperation($this->configuration, $name, $description, $userId);
        return $this;
    }

    /**
     * Records a custom operation.
     */
    public function addOperation(OperationInterface $operation): self
    {
        $this->operations[] = $operation;
        return $this;
    }

    /**
     * Returns all recorded operations.
     *
     * @return OperationInterface[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Returns operations filtered by type.
     *
     * @return OperationInterface[]
     */
    public function getOperationsByType(string $type): array
    {
        return array_filter($this->operations, static fn(OperationInterface $op) => $op->getType() === $type);
    }

    /**
     * Returns all SQL queries.
     *
     * @return string[]
     */
    public function getSqlQueries(): array
    {
        return array_map(static fn(OperationInterface $op) => $op instanceof SqlOperation
            ? $op->getQuery()
            : '', $this->getOperationsByType('sql'));
    }

    /**
     * Returns the count of operations by type.
     *
     * @return array<string, int>
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
     * Returns all operations as an array for dry-run output.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn(OperationInterface $op) => $op->toArray(), $this->operations);
    }

    /**
     * Clears all recorded operations.
     */
    public function clear(): self
    {
        $this->operations = [];
        return $this;
    }

    /**
     * Returns the total count of operations.
     */
    public function count(): int
    {
        return count($this->operations);
    }
}
