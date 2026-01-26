<?php

/**
 * Fluent builder for ALTER TABLE statements.
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

class AlterTableBuilder
{
    private string $tableName;
    private DialectInterface $dialect;

    /** @var array<int, array{action: string, column: string, type: string|null, after: string|null, default: string|null, nullable: bool}> */
    private array $alterations = [];

    /**
     * Initialize the builder with an optional SQL dialect.
     *
     * If no dialect is provided, a default dialect is created via DialectFactory::create()
     * and stored for later SQL generation.
     *
     * @param DialectInterface|null $dialect The dialect to use for SQL generation, or null to use the default.
     */
    public function __construct(?DialectInterface $dialect = null)
    {
        $this->dialect = $dialect ?? DialectFactory::create();
    }

    /**
     * Set the target table for subsequent ALTER operations.
     *
     * @param string $name The table name without prefix.
     * @param bool $withPrefix If true, prepend the configured table prefix to $name.
     * @return self The current builder instance.
     */
    public function table(string $name, bool $withPrefix = true): self
    {
        $this->tableName = $withPrefix ? Database::getTablePrefix() . $name : $name;
        return $this;
    }

    /**
     * Adds an INTEGER column definition to the builder for the current table.
     *
     * @param string $name The column name.
     * @param bool $nullable Whether the column allows NULL.
     * @param int|null $default Optional integer default value for the column.
     * @param string|null $after Optional existing column name after which the new column should be placed.
     * @return self The builder instance for method chaining.
     */
    public function addInteger(string $name, bool $nullable = true, ?int $default = null, ?string $after = null): self
    {
        return $this->addColumn(
            'ADD',
            $name,
            $this->dialect->integer(),
            $nullable,
            $default !== null ? (string) $default : null,
            $after,
        );
    }

    /**
     * Add a VARCHAR column definition to the pending alterations for the current table.
     *
     * @param string $name Column name.
     * @param int $length Length of the VARCHAR.
     * @param bool $nullable Whether the column allows NULL.
     * @param string|null $default Default value for the column (provided as an unquoted string; it will be quoted internally) or null for no default.
     * @param string|null $after Optional column name to position the new column AFTER, or null to append.
     * @return self The builder instance for method chaining.
     */
    public function addVarchar(
        string $name,
        int $length,
        bool $nullable = true,
        ?string $default = null,
        ?string $after = null,
    ): self {
        $defaultVal = $default !== null ? "'$default'" : null;
        return $this->addColumn('ADD', $name, $this->dialect->varchar($length), $nullable, $defaultVal, $after);
    }

    /**
     * Add a TEXT column to the target table.
     *
     * @param string $name The column name.
     * @param bool $nullable Whether the column allows NULL values.
     * @param string|null $after Optional column name to position the new column after; ignored if the dialect does not support column positioning.
     * @return self The builder instance for method chaining.
     */
    public function addText(string $name, bool $nullable = true, ?string $after = null): self
    {
        return $this->addColumn('ADD', $name, $this->dialect->text(), $nullable, null, $after);
    }

    /**
     * Add a BOOLEAN column to the current table definition.
     *
     * @param string $name The column name to add.
     * @param bool $nullable Whether the column allows NULL.
     * @param bool|null $default The default value for the column; when provided it will be converted to '1' or '0'.
     * @param string|null $after Optional column name to position the new column after (may be ignored by some dialects).
     * @return self The builder instance for method chaining.
     */
    public function addBoolean(string $name, bool $nullable = true, ?bool $default = null, ?string $after = null): self
    {
        $defaultVal = $default !== null ? ($default ? '1' : '0') : null;
        return $this->addColumn('ADD', $name, $this->dialect->boolean(), $nullable, $defaultVal, $after);
    }

    /**
     * Adds a TIMESTAMP column definition to the builder for the configured table.
     *
     * @param string $name The column name.
     * @param bool $nullable Whether the column allows NULL values.
     * @param bool $defaultCurrent If true, sets the column default to the current timestamp.
     * @param string|null $after Optional column name after which the new column should be placed.
     * @return self The builder instance for method chaining.
     */
    public function addTimestamp(
        string $name,
        bool $nullable = true,
        bool $defaultCurrent = false,
        ?string $after = null,
    ): self {
        $default = $defaultCurrent ? $this->dialect->currentTimestamp() : null;
        return $this->addColumn('ADD', $name, $this->dialect->timestamp(), $nullable, $default, $after);
    }

    /**
     * Queue a modification that changes the specified column's type.
     *
     * @param string $name The name of the column to modify.
     * @param string $type The new SQL column type definition to apply.
     * @return self The current builder instance for method chaining.
     */
    public function modifyColumn(string $name, string $type): self
    {
        $this->alterations[] = [
            'action' => 'MODIFY',
            'column' => $name,
            'type' => $type,
            'after' => null,
            'default' => null,
            'nullable' => true,
        ];
        return $this;
    }

    /**
     * Schedule removal of a column from the target table.
     *
     * @param string $name The name of the column to drop.
     * @return $this The builder instance for method chaining.
     */
    public function dropColumn(string $name): self
    {
        $this->alterations[] = [
            'action' => 'DROP',
            'column' => $name,
            'type' => null,
            'after' => null,
            'default' => null,
            'nullable' => true,
        ];
        return $this;
    }

    /**
     * Build ALTER TABLE statements for all queued alterations.
     *
     * @return string[] An array of SQL ALTER statements, one statement per recorded alteration.
     */
    public function build(): array
    {
        $statements = [];

        foreach ($this->alterations as $alt) {
            switch ($alt['action']) {
                case 'ADD':
                    $type = $alt['type'];
                    if (!$alt['nullable']) {
                        $type .= ' NOT NULL';
                    } elseif ($alt['default'] === null) {
                        $type .= ' NULL';
                    }
                    if ($alt['default'] !== null) {
                        $type .= ' DEFAULT ' . $alt['default'];
                    }
                    $statements[] = $this->dialect->addColumn(
                        $this->tableName,
                        $alt['column'],
                        $type,
                        $this->dialect->supportsColumnPositioning() ? $alt['after'] : null,
                    );
                    break;

                case 'MODIFY':
                    $statements[] = $this->dialect->modifyColumn($this->tableName, $alt['column'], $alt['type']);
                    break;

                case 'DROP':
                    $statements[] = "ALTER TABLE {$this->tableName} DROP COLUMN {$alt['column']}";
                    break;
            }
        }

        return $statements;
    }

    /**
     * Build a single MySQL-style ALTER TABLE statement that combines all queued alterations.
     *
     * Processes ADD, MODIFY and DROP alterations in the order they were added. ADD parts include nullability and DEFAULT clauses, and will include an AFTER clause when the configured dialect supports column positioning.
     *
     * @return string The combined ALTER TABLE SQL statement for the configured table.
     */
    public function buildCombined(): string
    {
        $parts = [];

        foreach ($this->alterations as $alt) {
            switch ($alt['action']) {
                case 'ADD':
                    $type = $alt['type'];
                    if (!$alt['nullable']) {
                        $type .= ' NOT NULL';
                    } elseif ($alt['default'] === null) {
                        $type .= ' NULL';
                    }
                    if ($alt['default'] !== null) {
                        $type .= ' DEFAULT ' . $alt['default'];
                    }
                    $part = "ADD COLUMN {$alt['column']} $type";
                    if ($this->dialect->supportsColumnPositioning() && $alt['after'] !== null) {
                        $part .= " AFTER {$alt['after']}";
                    }
                    $parts[] = $part;
                    break;

                case 'MODIFY':
                    $parts[] = "MODIFY {$alt['column']} {$alt['type']}";
                    break;

                case 'DROP':
                    $parts[] = "DROP COLUMN {$alt['column']}";
                    break;
            }
        }

        return "ALTER TABLE {$this->tableName} " . implode(', ', $parts);
    }

    /**
     * Append a column alteration entry to the builder's pending alterations.
     *
     * @param string $action The alteration action: 'ADD', 'MODIFY', or 'DROP'.
     * @param string $name The column name to be altered.
     * @param string $type The column SQL type or modification specification (e.g., "VARCHAR(255)").
     * @param bool $nullable True if the column should allow NULL, false for NOT NULL.
     * @param string|null $default The default value as an SQL literal (e.g., "'text'", "1") or null for none.
     * @param string|null $after Optional column name to position the new column after, or null to omit positioning.
     * @return self The current AlterTableBuilder instance for chaining.
     */
    private function addColumn(
        string $action,
        string $name,
        string $type,
        bool $nullable,
        ?string $default,
        ?string $after,
    ): self {
        $this->alterations[] = [
            'action' => $action,
            'column' => $name,
            'type' => $type,
            'after' => $after,
            'default' => $default,
            'nullable' => $nullable,
        ];
        return $this;
    }
}