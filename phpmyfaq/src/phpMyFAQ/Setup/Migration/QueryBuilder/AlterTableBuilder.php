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

use LogicException;
use phpMyFAQ\Database;

class AlterTableBuilder
{
    private string $tableName;
    private DialectInterface $dialect;

    /** @var array<int, array{action: string, column: string, type: string|null, after: string|null, default: string|null, nullable: bool}> */
    private array $alterations = [];

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
     * Adds a new INTEGER column.
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
     * Adds a new VARCHAR column.
     */
    public function addVarchar(
        string $name,
        int $length,
        bool $nullable = true,
        ?string $default = null,
        ?string $after = null,
    ): self {
        // Escape single quotes in default value per SQL string literal rules (replace ' with '')
        $defaultVal = $default !== null ? "'" . str_replace("'", "''", $default) . "'" : null;
        return $this->addColumn('ADD', $name, $this->dialect->varchar($length), $nullable, $defaultVal, $after);
    }

    /**
     * Adds a new TEXT column.
     */
    public function addText(string $name, bool $nullable = true, ?string $after = null): self
    {
        return $this->addColumn('ADD', $name, $this->dialect->text(), $nullable, null, $after);
    }

    /**
     * Adds a new BOOLEAN column.
     */
    public function addBoolean(string $name, bool $nullable = true, ?bool $default = null, ?string $after = null): self
    {
        $defaultVal = $default !== null ? ($default ? '1' : '0') : null;
        return $this->addColumn('ADD', $name, $this->dialect->boolean(), $nullable, $defaultVal, $after);
    }

    /**
     * Adds a new TIMESTAMP column.
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
     * Modifies an existing column type.
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
     * Drops a column.
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
     * Builds the ALTER TABLE statement(s).
     * Returns an array of statements because some databases require separate statements for each alteration.
     *
     * @return string[]
     */
    public function build(): array
    {
        // Validate that table() was called before building
        if (!isset($this->tableName) || $this->tableName === '') {
            throw new \RuntimeException('Table name not set. Call table() before building ALTER TABLE statements.');
        }

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
     * Builds a single ALTER TABLE statement combining all alterations (MySQL only).
     */
    public function buildCombined(): string
    {
        if (!$this->dialect->supportsCombinedAlter()) {
            throw new LogicException(sprintf(
                'Combined ALTER TABLE is only supported on MySQL. Current dialect: %s. Use build() instead.',
                $this->dialect->getType(),
            ));
        }

        // Validate that table() was called before building
        if (!isset($this->tableName) || $this->tableName === '') {
            throw new \RuntimeException('Table name not set. Call table() before building ALTER TABLE statements.');
        }

        if ($this->alterations === []) {
            throw new LogicException('No alterations defined for combined ALTER TABLE statement.');
        }

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
                    $part = "ADD COLUMN {$alt['column']} {$type}";
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
