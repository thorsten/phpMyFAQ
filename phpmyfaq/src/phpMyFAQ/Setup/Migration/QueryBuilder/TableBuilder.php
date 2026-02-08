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

use LogicException;
use phpMyFAQ\Database;

class TableBuilder
{
    private string $tableName = '';
    private bool $ifNotExists = false;
    private DialectInterface $dialect;

    /** @var array<string, array{type: string, nullable: bool, default: string|null, extra: string|null}> */
    private array $columns = [];

    /** @var string[] */
    private array $primaryKey = [];

    /** @var array<string, array{columns: string[], unique: bool}> */
    private array $indexes = [];

    /** @var array<string[]> */
    private array $fullTextIndexes = [];

    public function __construct(?DialectInterface $dialect = null)
    {
        $this->dialect = $dialect ?? DialectFactory::create();
    }

    /**
     * Sets the table name.
     */
    public function table(string $name, bool $withPrefix = true): self
    {
        $this->tableName = $withPrefix ? Database::getTablePrefix() . $name : $name;
        return $this;
    }

    /**
     * Adds IF NOT EXISTS clause.
     */
    public function ifNotExists(): self
    {
        $this->ifNotExists = true;
        return $this;
    }

    /**
     * Adds an INTEGER column.
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
     * Adds a SMALLINT column.
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
     * Adds a VARCHAR column.
     */
    public function varchar(string $name, int $length, bool $nullable = true, ?string $default = null): self
    {
        // Escape single quotes in default value per SQL string literal rules (replace ' with '')
        $defaultVal = $default !== null ? "'" . str_replace(search: "'", replace: "''", subject: $default) . "'" : null;
        return $this->addColumn($name, $this->dialect->varchar($length), $nullable, $defaultVal);
    }

    /**
     * Adds a TEXT column.
     */
    public function text(string $name, bool $nullable = true): self
    {
        return $this->addColumn($name, $this->dialect->text(), $nullable);
    }

    /**
     * Adds a LONGTEXT column (or equivalent).
     */
    public function longText(string $name, bool $nullable = true): self
    {
        return $this->addColumn($name, $this->dialect->longText(), $nullable);
    }

    /**
     * Adds a BLOB column (or equivalent).
     */
    public function blob(string $name, bool $nullable = true): self
    {
        return $this->addColumn($name, $this->dialect->blob(), $nullable);
    }

    /**
     * Adds a BOOLEAN/TINYINT column.
     */
    public function boolean(string $name, bool $nullable = true, ?bool $default = null): self
    {
        $defaultVal = match (true) {
            $default === null => null,
            $default => '1',
            default => '0',
        };
        return $this->addColumn($name, $this->dialect->boolean(), $nullable, $defaultVal);
    }

    /**
     * Adds a TIMESTAMP/DATETIME column.
     */
    public function timestamp(string $name, bool $nullable = true, bool $defaultCurrent = false): self
    {
        $default = $defaultCurrent ? $this->dialect->currentTimestamp() : null;
        return $this->addColumn($name, $this->dialect->timestamp(), $nullable, $default);
    }

    /**
     * Adds a DATE column.
     */
    public function date(string $name, bool $nullable = true, bool $defaultCurrent = false): self
    {
        $default = $defaultCurrent ? $this->dialect->currentDate() : null;
        return $this->addColumn($name, $this->dialect->date(), $nullable, $default);
    }

    /**
     * Adds a CHAR column.
     */
    public function char(string $name, int $length, bool $nullable = true, ?string $default = null): self
    {
        // Escape single quotes in default value per SQL string literal rules (replace ' with '')
        $defaultVal = $default !== null ? "'" . str_replace(search: "'", replace: "''", subject: $default) . "'" : null;
        return $this->addColumn($name, $this->dialect->char($length), $nullable, $defaultVal);
    }

    /**
     * Adds an auto-increment primary key column.
     */
    public function autoIncrement(string $name = 'id'): self
    {
        $this->columns[$name] = [
            'type' => 'AUTO_INCREMENT',
            'nullable' => false,
            'default' => null,
            'extra' => null,
        ];

        if ($this->primaryKey === []) {
            $this->primaryKey = [$name];
        }

        return $this;
    }

    /**
     * Sets the primary key column(s).
     *
     * @param string|string[] $columns
     */
    public function primaryKey(string|array $columns): self
    {
        $this->primaryKey = (array) $columns;
        return $this;
    }

    /**
     * Adds an index.
     *
     * @param string|string[] $columns
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
     * Adds a FULLTEXT index (MySQL only, ignored for other dialects).
     *
     * @param string|string[] $columns
     */
    public function fullTextIndex(string|array $columns): self
    {
        $this->fullTextIndexes[] = (array) $columns;
        return $this;
    }

    /**
     * Adds a unique index.
     *
     * @param string|string[] $columns
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
     * Builds the CREATE TABLE statement.
     */
    public function build(): string
    {
        if ($this->tableName === '') {
            throw new LogicException('Table name not set: call table() before build()');
        }

        $parts = [];

        foreach ($this->columns as $name => $def) {
            if ($def['type'] === 'AUTO_INCREMENT') {
                $parts[] = $this->dialect->autoIncrement($name);
            } else {
                $col = "{$name} {$def['type']}";
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
        // For SQLite and MySQL, autoIncrement() already includes PRIMARY KEY, so skip explicit PRIMARY KEY
        $hasAutoIncrement = false;
        foreach ($this->columns as $def) {
            if ($def['type'] !== 'AUTO_INCREMENT') {
                continue;
            }

            $hasAutoIncrement = true;
            break;
        }

        $dialectType = $this->dialect->getType();
        $autoIncrementIncludesPk = in_array($dialectType, ['sqlite3', 'mysqli', 'pdo_mysql'], true);
        if (!empty($this->primaryKey) && !($hasAutoIncrement && $autoIncrementIncludesPk)) {
            $pkColumns = implode(', ', $this->primaryKey);
            $parts[] = "PRIMARY KEY ({$pkColumns})";
        }

        // Add inline indexes only for MySQL (MySQL supports this, other databases don't)
        $isMysql = in_array($this->dialect->getType(), ['mysqli', 'pdo_mysql'], true);
        if ($isMysql) {
            foreach ($this->fullTextIndexes as $ftColumns) {
                $columnList = implode(',', $ftColumns);
                $parts[] = "FULLTEXT ({$columnList})";
            }

            foreach ($this->indexes as $indexName => $indexDef) {
                $columnList = implode(', ', $indexDef['columns']);
                $indexType = $indexDef['unique'] ? 'UNIQUE INDEX' : 'INDEX';
                $parts[] = "{$indexType} {$indexName} ({$columnList})";
            }
        }

        $columnDefs = implode(",\n    ", $parts);
        $prefix = $this->dialect->createTablePrefix($this->tableName, $this->ifNotExists);
        $suffix = $this->dialect->createTableSuffix();

        $sql = "{$prefix} (\n    {$columnDefs}\n)";
        if ($suffix !== '') {
            $sql .= ' ' . $suffix;
        }

        return $sql;
    }

    /**
     * Returns separate CREATE INDEX statements.
     * For MySQL, returns empty array since indexes are inlined in CREATE TABLE.
     *
     * @return string[]
     */
    public function buildIndexStatements(): array
    {
        if ($this->tableName === '') {
            throw new LogicException('Table name not set: call table() before buildIndexStatements()');
        }

        // MySQL already has indexes inlined in CREATE TABLE, so no separate statements needed
        $isMysql = in_array($this->dialect->getType(), ['mysqli', 'pdo_mysql'], true);
        if ($isMysql) {
            return [];
        }

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
