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

    /**
     * Initialize the migration with a Configuration instance and capture environment-specific database settings.
     *
     * The constructor stores the provided Configuration for later access and sets the internal table prefix and
     * database type based on the current Database environment.
     *
     * @param Configuration $configuration The configuration service used to read setup settings.
     */
    public function __construct(
        protected Configuration $configuration,
    ) {
        $this->tablePrefix = Database::getTablePrefix();
        $this->dbType = Database::getType();
    }

    /**
     * Get migration dependencies; override to declare required migration class names.
     *
     * @return string[] List of migration class names this migration depends on. Empty array if there are no dependencies.
     */
    public function getDependencies(): array
    {
        return [];
    }

    /**
     * Indicates whether the migration is reversible.
     *
     * @return bool `true` if the migration is reversible, `false` otherwise.
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
     * Resolve a table name using the configured table prefix.
     *
     * @param string $name The base table name (without prefix).
     * @return string The fully qualified table name including the configured prefix.
     */
    protected function table(string $name): string
    {
        return $this->tablePrefix . $name;
    }

    /**
     * Determines whether the current database type matches any of the provided type names.
     *
     * @param string|string[] $types Database type name or list of names to compare against (e.g. 'mysqli', 'pdo_mysql').
     * @return bool `true` if the current database type is one of the provided types, `false` otherwise.
     */
    protected function isDbType(string|array $types): bool
    {
        $types = (array) $types;
        return in_array($this->dbType, $types, true);
    }

    /**
         * Determine whether the active database type is MySQL or MariaDB.
         *
         * @return bool `true` if the current database type is MySQL/MariaDB, `false` otherwise.
         */
    protected function isMySql(): bool
    {
        return $this->isDbType(['mysqli', 'pdo_mysql']);
    }

    /**
     * Determine whether the active database is PostgreSQL.
     *
     * @return bool `true` if the current database type is PostgreSQL (`pgsql` or `pdo_pgsql`), `false` otherwise.
     */
    protected function isPostgreSql(): bool
    {
        return $this->isDbType(['pgsql', 'pdo_pgsql']);
    }

    /**
     * Determine whether the current database type is SQLite.
     *
     * @return bool `true` if the active DB type is `sqlite3` or `pdo_sqlite`, `false` otherwise.
     */
    protected function isSqlite(): bool
    {
        return $this->isDbType(['sqlite3', 'pdo_sqlite']);
    }

    /**
         * Determine whether the current database type is Microsoft SQL Server.
         *
         * @return bool `true` if the active database type is SQL Server (`sqlsrv` or `pdo_sqlsrv`), `false` otherwise.
         */
    protected function isSqlServer(): bool
    {
        return $this->isDbType(['sqlsrv', 'pdo_sqlsrv']);
    }

    /**
         * Builds an ALTER TABLE statement to add a column adapted to the active database dialect.
         *
         * @param string $table Unprefixed table name; the configured table prefix will be applied.
         * @param string $column Column name to add.
         * @param string $type SQL type definition for the new column (e.g. `VARCHAR(255)`).
         * @param string|null $default Optional SQL expression for the DEFAULT clause (include quotes/literals as required).
         * @return string The ALTER TABLE SQL statement that adds the specified column for the current DB type.
         */
    protected function addColumn(string $table, string $column, string $type, ?string $default = null): string
    {
        $tableName = $this->table($table);
        $defaultClause = $default !== null ? " DEFAULT $default" : '';

        if ($this->isSqlite()) {
            return sprintf('ALTER TABLE %s ADD COLUMN %s %s%s', $tableName, $column, $type, $defaultClause);
        }

        // MySQL, PostgreSQL, SQL Server
        $columnKeyword = $this->isSqlServer() ? '' : 'COLUMN ';
        return sprintf('ALTER TABLE %s ADD %s%s %s%s', $tableName, $columnKeyword, $column, $type, $defaultClause);
    }

    /**
     * Builds an SQL statement to drop a column from a prefixed table.
     *
     * @param string $table  Unprefixed table name.
     * @param string $column Column name to drop.
     * @return string The SQL statement that drops the specified column from the prefixed table.
     */
    protected function dropColumn(string $table, string $column): string
    {
        $tableName = $this->table($table);
        return sprintf('ALTER TABLE %s DROP COLUMN %s', $tableName, $column);
    }

    /**
         * Build an ALTER TABLE statement that drops multiple columns using MySQL-style syntax.
         *
         * @param string $table Unprefixed table name (will be prefixed by the migration's table prefix).
         * @param string[] $columns Names of columns to drop.
         * @return string The generated ALTER TABLE ... DROP COLUMN ... SQL statement.
         */
    protected function dropColumns(string $table, array $columns): string
    {
        $tableName = $this->table($table);
        $dropParts = array_map(static fn($col) => "DROP COLUMN $col", $columns);
        return sprintf('ALTER TABLE %s %s', $tableName, implode(', ', $dropParts));
    }

    /**
     * Builds a CREATE INDEX SQL statement appropriate for the active database.
     *
     * @param string $table The logical table name (will be prefixed using the migration's table prefix).
     * @param string $indexName The name of the index to create.
     * @param string|array $columns Column name or list of column names to include in the index; an array is joined with ", ".
     * @return string The SQL statement that creates the index if it does not already exist for the current DB. 
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

        return sprintf('CREATE INDEX IF NOT EXISTS %s ON %s (%s)', $indexName, $tableName, $columnList);
    }

    /**
     * Build a DROP TABLE statement for the given table name using the configured prefix.
     *
     * @param string $table Unprefixed table name.
     * @return string SQL DROP TABLE statement targeting the prefixed table.
     */
    protected function dropTable(string $table): string
    {
        return sprintf('DROP TABLE %s', $this->table($table));
    }

    /**
         * Builds a DROP TABLE IF EXISTS SQL statement for the given table name.
         *
         * @param string $table The table name without prefix.
         * @return string The SQL statement to drop the prefixed table if it exists.
         */
    protected function dropTableIfExists(string $table): string
    {
        return sprintf('DROP TABLE IF EXISTS %s', $this->table($table));
    }

    /**
     * Builds an UPDATE SQL statement that replaces a language code value in a specified column.
     *
     * The table name is passed through the migration's table-prefix resolver before being used.
     *
     * @param string $table The (unprefixed) table name to update.
     * @param string $column The column containing the language code.
     * @param string $oldCode The language code value to replace.
     * @param string $newCode The language code value to set.
     * @return string The constructed UPDATE SQL statement.
     */
    protected function updateLanguageCode(string $table, string $column, string $oldCode, string $newCode): string
    {
        return sprintf(
            "UPDATE %s SET %s='%s' WHERE %s='%s'",
            $this->table($table),
            $column,
            $newCode,
            $column,
            $oldCode,
        );
    }

    /**
     * Get the SQL integer column type for the current database.
     *
     * @return string The SQL type name: 'INT' for MySQL/MariaDB, 'INTEGER' for other databases.
     */
    protected function integerType(): string
    {
        return match ($this->dbType) {
            'mysqli', 'pdo_mysql' => 'INT',
            default => 'INTEGER',
        };
    }

    /**
     * Determine the SQL column type to use for text fields for the current database.
     *
     * @return string The SQL type name for text columns: 'NVARCHAR(MAX)' for SQL Server variants, otherwise 'TEXT'.
     */
    protected function textType(): string
    {
        return match ($this->dbType) {
            'sqlsrv', 'pdo_sqlsrv' => 'NVARCHAR(MAX)',
            default => 'TEXT',
        };
    }

    /**
     * Selects the VARCHAR column type declaration appropriate for the active database.
     *
     * @param int $length The character length for the VARCHAR/NVARCHAR column.
     * @return string The SQL type declaration, e.g. `NVARCHAR(255)` for SQL Server or `VARCHAR(255)` for other databases.
     */
    protected function varcharType(int $length): string
    {
        return match ($this->dbType) {
            'sqlsrv', 'pdo_sqlsrv' => "NVARCHAR($length)",
            default => "VARCHAR($length)",
        };
    }

    /**
         * Selects the appropriate TIMESTAMP/DATETIME column type for the active database.
         *
         * When $withDefault is true, the returned string includes a database-specific default/current-timestamp clause.
         *
         * @param bool $withDefault If true, append the database-specific DEFAULT/current-timestamp clause.
         * @return string The SQL column type (optionally including NOT NULL and DEFAULT clause) suitable for the current DB driver.
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
         * Determine the SQL column type used to represent boolean values for the current database.
         *
         * @return string `TINYINT(1)` for MySQL, `TINYINT` for SQL Server, `INTEGER` for other databases.
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
     * Produce an auto-increment column definition suited to the active database type.
     *
     * @param string $columnName Name of the column; defaults to "id".
     * @return string Column definition fragment including type and auto-increment syntax.
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
     * Retrieve the configuration value for the specified key.
     *
     * @param string $key The configuration key to retrieve.
     * @return mixed The value associated with the configuration key.
     */
    protected function getConfig(string $key): mixed
    {
        return $this->configuration->get(item: $key);
    }

    /**
         * Compute a checksum representing this migration's identity.
         *
         * The checksum is derived from the migration's version, description, and class name.
         *
         * @return string The SHA-256 hash of a JSON object containing the migration's version, description, and class name.
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