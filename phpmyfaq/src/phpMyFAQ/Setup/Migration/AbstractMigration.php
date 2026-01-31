<?php

/**
 * Base class for all migrations with helper methods.
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
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;

abstract readonly class AbstractMigration implements MigrationInterface
{
    protected string $tablePrefix;
    protected string $dbType;

    public function __construct(
        protected Configuration $configuration,
    ) {
        $this->tablePrefix = Database::getTablePrefix();
        $this->dbType = Database::getType();
    }

    /**
     * Default implementation returns an empty array (no dependencies).
     *
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [];
    }

    /**
     * Default implementation - migrations are not reversible unless overridden.
     */
    public function isReversible(): bool
    {
        return false;
    }

    /**
     * Default implementation - does nothing. Override in subclass if migration is reversible.
     */
    public function down(OperationRecorder $recorder): void
    {
        // Override in subclass if migration is reversible
    }

    /**
     * Returns the table name with a prefix.
     */
    protected function table(string $name): string
    {
        return $this->tablePrefix . $name;
    }

    /**
     * Checks if the current database type matches any of the given types.
     *
     * @param string|string[] $types Database type(s) to check
     */
    protected function isDbType(string|array $types): bool
    {
        $types = (array) $types;
        return in_array($this->dbType, $types, true);
    }

    /**
     * Returns true if running MySQL/MariaDB.
     */
    protected function isMySql(): bool
    {
        return $this->isDbType(['mysqli', 'pdo_mysql']);
    }

    /**
     * Returns true if running PostgreSQL.
     */
    protected function isPostgreSql(): bool
    {
        return $this->isDbType(['pgsql', 'pdo_pgsql']);
    }

    /**
     * Returns true if running SQLite.
     */
    protected function isSqlite(): bool
    {
        return $this->isDbType(['sqlite3', 'pdo_sqlite']);
    }

    /**
     * Returns true if running SQL Server.
     */
    protected function isSqlServer(): bool
    {
        return $this->isDbType(['sqlsrv', 'pdo_sqlsrv']);
    }

    /**
     * Helper to add a column to a table.
     * Returns the appropriate SQL for the current database type.
     */
    protected function addColumn(string $table, string $column, string $type, ?string $default = null): string
    {
        $tableName = $this->table($table);
        $defaultClause = '';
        if ($default !== null) {
            $defaultClause = " DEFAULT $default";
        }

        if ($this->isSqlite()) {
            return sprintf('ALTER TABLE %s ADD COLUMN %s %s%s', $tableName, $column, $type, $defaultClause);
        }

        // MySQL, PostgreSQL, SQL Server
        $columnKeyword = $this->isSqlServer() ? '' : 'COLUMN ';
        return sprintf('ALTER TABLE %s ADD %s%s %s%s', $tableName, $columnKeyword, $column, $type, $defaultClause);
    }

    /**
     * Helper to drop a column from a table.
     */
    protected function dropColumn(string $table, string $column): string
    {
        $tableName = $this->table($table);
        return sprintf('ALTER TABLE %s DROP COLUMN %s', $tableName, $column);
    }

    /**
     * Helper to drop multiple columns from a table.
     * Returns one ALTER TABLE ... DROP COLUMN statement per column for cross-database compatibility.
     *
     * @param string[] $columns
     * @return string[]
     */
    protected function dropColumns(string $table, array $columns): array
    {
        $tableName = $this->table($table);
        return array_map(static fn(string $col): string => sprintf(
            'ALTER TABLE %s DROP COLUMN %s',
            $tableName,
            $col,
        ), $columns);
    }

    /**
     * Helper to create an index.
     */
    protected function createIndex(string $table, string $indexName, string|array $columns): string
    {
        $tableName = $this->table($table);
        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;

        if ($this->isSqlServer()) {
            return sprintf(
                "IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = '%s') " . 'CREATE INDEX %s ON %s (%s)',
                $indexName,
                $indexName,
                $tableName,
                $columnList,
            );
        }

        // MySQL/MariaDB don't support IF NOT EXISTS in older versions
        // Use conditional execution or manual check instead
        if ($this->isMySql()) {
            return sprintf('CREATE INDEX %s ON %s (%s)', $indexName, $tableName, $columnList);
        }

        // PostgreSQL and SQLite support IF NOT EXISTS
        return sprintf('CREATE INDEX IF NOT EXISTS %s ON %s (%s)', $indexName, $tableName, $columnList);
    }

    /**
     * Helper to check if an index exists.
     * Returns a SQL query that checks for index existence.
     */
    protected function indexExists(string $table, string $indexName): string
    {
        $db = $this->configuration->getDb();
        $tableName = $this->table($table);
        $escapedTableName = $db->escape($tableName);
        $escapedIndexName = $db->escape($indexName);

        if ($this->isMySql()) {
            return sprintf(
                'SELECT COUNT(*) as idx_count FROM information_schema.STATISTICS '
                . "WHERE table_schema = DATABASE() AND table_name = '%s' AND index_name = '%s'",
                $escapedTableName,
                $escapedIndexName,
            );
        }

        if ($this->isPostgreSql()) {
            return sprintf(
                'SELECT COUNT(*) as idx_count FROM pg_indexes '
                . "WHERE schemaname = 'public' AND tablename = '%s' AND indexname = '%s'",
                $escapedTableName,
                $escapedIndexName,
            );
        }

        if ($this->isSqlite()) {
            return sprintf(
                'SELECT COUNT(*) as idx_count FROM sqlite_master '
                . "WHERE type = 'index' AND name = '%s' AND tbl_name = '%s'",
                $escapedIndexName,
                $escapedTableName,
            );
        }

        if ($this->isSqlServer()) {
            return sprintf(
                'SELECT COUNT(*) as idx_count FROM sys.indexes ' . "WHERE name = '%s' AND object_id = OBJECT_ID('%s')",
                $escapedIndexName,
                $escapedTableName,
            );
        }

        throw new \RuntimeException("Unsupported database type for index existence check: {$this->dbType}");
    }

    /**
     * Helper to drop a table.
     */
    protected function dropTable(string $table): string
    {
        return sprintf('DROP TABLE %s', $this->table($table));
    }

    /**
     * Helper to drop a table if it exists.
     */
    protected function dropTableIfExists(string $table): string
    {
        return sprintf('DROP TABLE IF EXISTS %s', $this->table($table));
    }

    /**
     * Helper for UPDATE queries with a language code fix pattern.
     */
    protected function updateLanguageCode(string $table, string $column, string $oldCode, string $newCode): string
    {
        $db = $this->configuration->getDb();
        $escapedOldCode = $db->escape($oldCode);
        $escapedNewCode = $db->escape($newCode);

        return sprintf(
            "UPDATE %s SET %s='%s' WHERE %s='%s'",
            $this->table($table),
            $column,
            $escapedNewCode,
            $column,
            $escapedOldCode,
        );
    }

    /**
     * Returns the INTEGER type appropriate for the database.
     */
    protected function integerType(): string
    {
        return match ($this->dbType) {
            'mysqli', 'pdo_mysql' => 'INT',
            default => 'INTEGER',
        };
    }

    /**
     * Returns the TEXT type appropriate for the database.
     */
    protected function textType(): string
    {
        return match ($this->dbType) {
            'sqlsrv', 'pdo_sqlsrv' => 'NVARCHAR(MAX)',
            default => 'TEXT',
        };
    }

    /**
     * Returns the VARCHAR type appropriate for the database.
     */
    protected function varcharType(int $length): string
    {
        return match ($this->dbType) {
            'sqlsrv', 'pdo_sqlsrv' => "NVARCHAR($length)",
            default => "VARCHAR($length)",
        };
    }

    /**
     * Returns the TIMESTAMP/DATETIME type with default appropriate for the database.
     */
    protected function timestampType(bool $withDefault = true): string
    {
        $type = match ($this->dbType) {
            'mysqli', 'pdo_mysql' => 'TIMESTAMP',
            'sqlsrv', 'pdo_sqlsrv' => 'DATETIME',
            default => 'TIMESTAMP',
        };

        if (!$withDefault) {
            return $type;
        }

        return match ($this->dbType) {
            'sqlsrv', 'pdo_sqlsrv' => $type . ' NOT NULL DEFAULT GETDATE()',
            'sqlite3', 'pdo_sqlite' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            default => $type . ' NOT NULL DEFAULT CURRENT_TIMESTAMP',
        };
    }

    /**
     * Returns the BOOLEAN/TINYINT type appropriate for the database.
     */
    protected function booleanType(): string
    {
        return match ($this->dbType) {
            'mysqli', 'pdo_mysql' => 'TINYINT(1)',
            'sqlsrv', 'pdo_sqlsrv' => 'TINYINT',
            default => 'INTEGER',
        };
    }

    /**
     * Returns the auto-increment column definition appropriate for the database.
     */
    protected function autoIncrementColumn(string $columnName = 'id'): string
    {
        return match ($this->dbType) {
            'mysqli', 'pdo_mysql' => "$columnName INT NOT NULL AUTO_INCREMENT",
            'pgsql', 'pdo_pgsql' => "$columnName SERIAL NOT NULL",
            'sqlite3', 'pdo_sqlite' => "$columnName INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT",
            'sqlsrv', 'pdo_sqlsrv' => "$columnName INT IDENTITY(1,1) NOT NULL",
            default => "$columnName INTEGER NOT NULL",
        };
    }

    /**
     * Gets a configuration value safely.
     */
    protected function getConfig(string $key): mixed
    {
        return $this->configuration->get(item: $key);
    }

    /**
     * Calculates a checksum for migration integrity.
     */
    public function getChecksum(): string
    {
        $data = [
            'version' => $this->getVersion(),
            'description' => $this->getDescription(),
            'class' => static::class,
        ];
        return hash('sha256', json_encode($data));
    }
}
