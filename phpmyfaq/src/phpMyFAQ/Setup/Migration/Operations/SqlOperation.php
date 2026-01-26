<?php

/**
 * SQL query operation for migrations.
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
use phpMyFAQ\Core\Exception;

readonly class SqlOperation implements OperationInterface
{
    / **
     * Create a SQL migration operation with a query and optional description.
     *
     * @param string $query The SQL statement to execute during the migration.
     * @param string $description Optional human-readable description; when empty a description will be derived from the query.
     * /
    public function __construct(
        private Configuration $configuration,
        private string $query,
        private string $description = '',
    ) {
    }

    /**
     * Provide the operation type identifier.
     *
     * @return string The operation type "sql".
     */
    public function getType(): string
    {
        return 'sql';
    }

    /**
     * Provide a human-readable description for this SQL operation.
     *
     * Returns the explicitly set description if present; otherwise derives a short
     * description from the SQL query (for example, "Create table <name>" or
     * "Insert into <name>"). If the query pattern is not recognized, returns
     * "Execute SQL query".
     *
     * @return string The operation description.
     */
    public function getDescription(): string
    {
        if ($this->description !== '') {
            return $this->description;
        }

        // Generate description from query
        $query = trim($this->query);
        if (stripos($query, 'CREATE TABLE') === 0) {
            preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\S+)/i', $query, $matches);
            return sprintf('Create table %s', $matches[1] ?? 'unknown');
        }
        if (stripos($query, 'ALTER TABLE') === 0) {
            preg_match('/ALTER TABLE\s+(\S+)/i', $query, $matches);
            return sprintf('Alter table %s', $matches[1] ?? 'unknown');
        }
        if (stripos($query, 'DROP TABLE') === 0) {
            preg_match('/DROP TABLE\s+(?:IF EXISTS\s+)?(\S+)/i', $query, $matches);
            return sprintf('Drop table %s', $matches[1] ?? 'unknown');
        }
        if (stripos($query, 'CREATE INDEX') === 0) {
            preg_match('/CREATE INDEX\s+(?:IF NOT EXISTS\s+)?(\S+)/i', $query, $matches);
            return sprintf('Create index %s', $matches[1] ?? 'unknown');
        }
        if (stripos($query, 'INSERT INTO') === 0) {
            preg_match('/INSERT INTO\s+(\S+)/i', $query, $matches);
            return sprintf('Insert into %s', $matches[1] ?? 'unknown');
        }
        if (stripos($query, 'UPDATE') === 0) {
            preg_match('/UPDATE\s+(\S+)/i', $query, $matches);
            return sprintf('Update %s', $matches[1] ?? 'unknown');
        }
        if (stripos($query, 'DELETE FROM') === 0) {
            preg_match('/DELETE FROM\s+(\S+)/i', $query, $matches);
            return sprintf('Delete from %s', $matches[1] ?? 'unknown');
        }

        return 'Execute SQL query';
    }

    /**
     * Get the stored SQL query.
     *
     * @return string The raw SQL query.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Execute the stored SQL query using the configured database connection.
     *
     * Runs the operation's SQL query and indicates whether execution succeeded.
     *
     * @return bool `true` if the query executed successfully, `false` otherwise.
     */
    public function execute(): bool
    {
        try {
            $this->configuration->getDb()->query($this->query);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Serialize the operation into an associative array representation.
     *
     * @return array{type:string,description:string,query:string} Associative array with keys:
     *     - `type`: operation type identifier (e.g., "sql"),
     *     - `description`: human-readable description of the operation,
     *     - `query`: the raw SQL query to be executed.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'query' => $this->query,
        ];
    }
}