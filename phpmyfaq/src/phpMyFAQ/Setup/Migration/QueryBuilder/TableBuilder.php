<?php

/**
 * Fluent builder for CREATE TABLE statements.
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

namespace phpMyFAQ\Setup\Migration\QueryBuilder;

use phpMyFAQ\Database;

class TableBuilder
{
    private string $tableName;
    private bool $ifNotExists = false;
    private DialectInterface $dialect;

    /** @var array<string, array{type: string, nullable: bool, default: string|null, extra: string|null}> */
    private array $columns = [];

    /** @var string[] */
    private array $primaryKey = [];

    /** @var array<string, array{columns: string[], unique: bool}> */
    private array $indexes = [];

    /**
     * Create a TableBuilder using the provided SQL dialect or a default dialect.
     *
     * @param DialectInterface|null $dialect The dialect implementation to use; if null, a default dialect is created.
     */
    public function __construct(?DialectInterface $dialect = null)
    {
        $this->dialect = $dialect ?? DialectFactory::create();
    }

    /**
     * Set the table name for the builder, optionally applying the global table prefix.
     *
     * @param string $name The table name (without prefix).
     * @param bool $withPrefix When true, prepend Database::getTablePrefix() to the name.
     * @return self Fluent instance for method chaining.
     */
    public function table(string $name, bool $withPrefix = true): self
    {
        $this->tableName = $withPrefix ? Database::getTablePrefix() . $name : $name;
        return $this;
    }

    /**
     * Enable the IF NOT EXISTS clause for the CREATE TABLE statement.
     *
     * @return $this The current TableBuilder instance for method chaining.
     */
    public function ifNotExists(): self
    {
        $this->ifNotExists = true;
        return $this;
    }

    /**
         * Add an INTEGER column to the table definition.
         *
         * @param string $name Column name.
         * @param bool $nullable Whether the column allows NULL.
         * @param int|null $default Optional default value for the column.
         * @return self The builder instance.
         */
    public function integer(string $name, bool $nullable = true, ?int $default = null): self
    {
        return $this->addColumn(
            $name,
            $this->dialect->integer(),
            $nullable,
            $default !== null ? (string) $default : null,
        );
    }

    /**
     * Adds a BIGINT column.
     */
    public function bigInteger(string $name, bool $nullable = true, ?int $default = null): self
    {
        return $this->addColumn(
            $name,
            $this->dialect->bigInteger(),
            $nullable,
            $default !== null ? (string) $default : null,
        );
    }

    /**
     * Add a SMALLINT column to the table definition.
     *
     * @param string $name The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param int|null $default The default integer value for the column, or null for no default.
     * @return self The current builder instance for method chaining.
     */
    public function smallInteger(string $name, bool $nullable = true, ?int $default = null): self
    {
        return $this->addColumn(
            $name,
            $this->dialect->smallInteger(),
            $nullable,
            $default !== null ? (string) $default : null,
        );
    }

    /**
     * Add a VARCHAR column to the table definition.
     *
     * @param string $name   Column name.
     * @param int    $length Maximum character length for the column.
     * @param bool   $nullable Whether the column allows NULL.
     * @param string|null $default Default string value for the column (unquoted); `null` means no default.
     * @return self The builder instance for method chaining.
     */
    public function varchar(string $name, int $length, bool $nullable = true, ?string $default = null): self
    {
        $defaultVal = $default !== null ? "'$default'" : null;
        return $this->addColumn($name, $this->dialect->varchar($length), $nullable, $defaultVal);
    }

    /**
     * Add a TEXT column to the table definition.
     *
     * @param string $name The column name.
     * @param bool $nullable Whether the column allows NULL (default: true).
     * @return self The current builder instance for method chaining.
     */
    public function text(string $name, bool $nullable = true): self
    {
        return $this->addColumn($name, $this->dialect->text(), $nullable);
    }

    /**
         * Adds a BOOLEAN/TINYINT column to the table definition.
         *
         * @param string $name The column name.
         * @param bool $nullable Whether the column allows NULL (true) or is NOT NULL (false).
         * @param bool|null $default Optional default value; when provided, `true` is converted to `'1'` and `false` to `'0'` for the column default.
         * @return self The builder instance for chaining.
         */
    public function boolean(string $name, bool $nullable = true, ?bool $default = null): self
    {
        $defaultVal = $default !== null ? ($default ? '1' : '0') : null;
        return $this->addColumn($name, $this->dialect->boolean(), $nullable, $defaultVal);
    }

    /**
     * Add a TIMESTAMP/DATETIME column to the table definition.
     *
     * If $defaultCurrent is true, sets the column default to the dialect's current timestamp expression.
     *
     * @param string $name Column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param bool $defaultCurrent Whether to use the dialect's current timestamp as the column default.
     * @return self The builder instance.
     */
    public function timestamp(string $name, bool $nullable = true, bool $defaultCurrent = false): self
    {
        $default = $defaultCurrent ? $this->dialect->currentTimestamp() : null;
        return $this->addColumn($name, $this->dialect->timestamp(), $nullable, $default);
    }

    /**
     * Add a DATE column to the table definition.
     *
     * If `$defaultCurrent` is true, the column's default value will be the current date as provided by the configured SQL dialect.
     *
     * @param string $name The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param bool $defaultCurrent If true, set the column default to the dialect-specific current date.
     * @return self The TableBuilder instance for method chaining.
     */
    public function date(string $name, bool $nullable = true, bool $defaultCurrent = false): self
    {
        $default = $defaultCurrent ? $this->dialect->currentDate() : null;
        return $this->addColumn($name, $this->dialect->date(), $nullable, $default);
    }

    /**
     * Adds a CHAR column definition to the table.
     *
     * @param string $name Column name.
     * @param int $length Maximum character length for the CHAR column.
     * @param bool $nullable Whether the column allows NULL.
     * @param string|null $default Default string value for the column; if provided it will be used as the SQL default (wrapped in single quotes).
     * @return self The builder instance for method chaining.
     */
    public function char(string $name, int $length, bool $nullable = true, ?string $default = null): self
    {
        $defaultVal = $default !== null ? "'$default'" : null;
        return $this->addColumn($name, $this->dialect->char($length), $nullable, $defaultVal);
    }

    /**
     * Add an auto-incrementing primary key column to the table definition.
     *
     * @param string $name Name of the column; defaults to 'id'.
     * @return self The current builder instance for method chaining.
     */
    public function autoIncrement(string $name = 'id'): self
    {
        $this->columns[$name] = [
            'type' => 'AUTO_INCREMENT',
            'nullable' => false,
            'default' => null,
            'extra' => null,
        ];
        return $this;
    }

    /**
     * Set the table primary key to the given column or columns.
     *
     * @param string|string[] $columns Column name or array of column names for the primary key.
     * @return self The builder instance for chaining.
     */
    public function primaryKey(string|array $columns): self
    {
        $this->primaryKey = (array) $columns;
        return $this;
    }

    /**
     * Register a non-unique index with the given name on the specified column(s).
     *
     * @param string $name The index name.
     * @param string|string[] $columns Column name or array of column names to include in the index.
     * @return self The builder instance for chaining.
     */
    public function index(string $name, string|array $columns): self
    {
        $this->indexes[$name] = [
            'columns' => (array) $columns,
            'unique' => false,
        ];
        return $this;
    }

    /**
     * Register a unique index with the given name on specified column(s).
     *
     * @param string $name The index name.
     * @param string|string[] $columns Column name or list of column names to include in the index.
     * @return self The current builder instance.
     */
    public function uniqueIndex(string $name, string|array $columns): self
    {
        $this->indexes[$name] = [
            'columns' => (array) $columns,
            'unique' => true,
        ];
        return $this;
    }

    /**
     * Constructs the CREATE TABLE SQL statement for the configured table.
     *
     * Includes column definitions (respecting NULL/NOT NULL and DEFAULT), an optional PRIMARY KEY clause,
     * and inline indexes. Delegates dialect-specific pieces such as auto-increment column rendering,
     * table prefixing (including IF NOT EXISTS) and table suffix to the configured dialect.
     *
     * @return string The complete CREATE TABLE SQL statement.
     */
    public function build(): string
    {
        $parts = [];

        foreach ($this->columns as $name => $def) {
            if ($def['type'] === 'AUTO_INCREMENT') {
                $parts[] = $this->dialect->autoIncrement($name);
            } else {
                $col = "$name {$def['type']}";
                if (!$def['nullable']) {
                    $col .= ' NOT NULL';
                } elseif ($def['default'] === null && $def['nullable']) {
                    $col .= ' NULL';
                }
                if ($def['default'] !== null) {
                    $col .= ' DEFAULT ' . $def['default'];
                }
                $parts[] = $col;
            }
        }

        // Add primary key if set and not already added via autoIncrement
        if (!empty($this->primaryKey)) {
            $pkColumns = implode(', ', $this->primaryKey);
            $parts[] = "PRIMARY KEY ($pkColumns)";
        }

        // Add inline indexes (MySQL supports this)
        foreach ($this->indexes as $indexName => $indexDef) {
            $columnList = implode(', ', $indexDef['columns']);
            $indexType = $indexDef['unique'] ? 'UNIQUE INDEX' : 'INDEX';
            $parts[] = "$indexType $indexName ($columnList)";
        }

        $columnDefs = implode(",\n    ", $parts);
        $prefix = $this->dialect->createTablePrefix($this->tableName, $this->ifNotExists);
        $suffix = $this->dialect->createTableSuffix();

        $sql = "$prefix (\n    $columnDefs\n)";
        if ($suffix !== '') {
            $sql .= ' ' . $suffix;
        }

        return $sql;
    }

    /**
     * Build separate CREATE INDEX statements for all defined indexes.
     *
     * @return string[] Array of CREATE INDEX SQL statements, one entry per defined index.
     */
    public function buildIndexStatements(): array
    {
        $statements = [];
        foreach ($this->indexes as $indexName => $indexDef) {
            $statements[] = $this->dialect->createIndex(
                $indexName,
                $this->tableName,
                $indexDef['columns'],
                $this->ifNotExists,
            );
        }
        return $statements;
    }

    /**
     * Store a column definition for later SQL generation.
     *
     * @param string $name The column name.
     * @param string $type The SQL column type or type expression (e.g., "VARCHAR(255)").
     * @param bool $nullable Whether the column allows NULL.
     * @param string|null $default The default value or SQL expression to use for the column, or null if none.
     * @param string|null $extra Optional additional column attributes (e.g., "AUTO_INCREMENT").
     * @return self The builder instance.
     */
    private function addColumn(
        string $name,
        string $type,
        bool $nullable,
        ?string $default = null,
        ?string $extra = null,
    ): self {
        $this->columns[$name] = [
            'type' => $type,
            'nullable' => $nullable,
            'default' => $default,
            'extra' => $extra,
        ];
        return $this;
    }
}