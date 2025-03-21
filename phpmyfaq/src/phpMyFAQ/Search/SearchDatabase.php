<?php

/**
 * phpMyFAQ database based search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-06-06
 */

namespace phpMyFAQ\Search;

use phpMyFAQ\Configuration;
use phpMyFAQ\Strings;
use stdClass;

/**
 * Class SearchDatabase
 *
 * @package phpMyFAQ\Search
 */
class SearchDatabase extends AbstractSearch implements SearchInterface
{
    /**
     * Searching database table.
     */
    protected string $table = '';

    /**
     * Joined searching database table.
     */
    protected string $joinedTable = '';

    /**
     * Columns for the result set.
     *
     * @var string[]
     */
    protected array $resultColumns = [];

    /**
     * Columns for the joined table.
     *
     * @var string[]
     */
    protected array $joinedColumns = [];

    /**
     * Matching columns for the search.
     *
     * @var string[]
     */
    protected array $matchingColumns = [];

    /**
     * Conditions columns with their values.
     *
     * @var array<string, array<int>|string>
     */
    protected array $conditions = [];

    /**
     * Flag if database supports search relevance.
     */
    protected bool $relevanceSupport = false;

    /**
     * Prepares the search and executes it.
     *
     * @param string $searchTerm Search term
     */
    public function search(string $searchTerm): mixed
    {
        $query = sprintf(
            '
            SELECT
                %s
            FROM 
                %s %s %s
            WHERE
                %s = %s',
            $this->getResultColumns(),
            $this->getTable(),
            $this->getJoinedTable(),
            $this->getJoinedColumns(),
            $this->getMatchingColumns(),
            $searchTerm
        );

        $this->resultSet = $this->configuration->getDb()->query($query);

        return $this->resultSet;
    }

    /**
     * Returns the part of the SQL query with the columns for the result set.
     */
    public function getResultColumns(): string
    {
        $resultColumns = '';

        foreach ($this->resultColumns as $resultColumn) {
            if (empty($resultColumns)) {
                $resultColumns = $resultColumn;
            } else {
                $resultColumns .= ', ' . $resultColumn;
            }
        }

        return $resultColumns;
    }

    /**
     * Sets the part of the SQL query with the columns for the result set.
     *
     * @param string[] $columns Array of columns
     */
    public function setResultColumns(array $columns): SearchDatabase
    {
        $this->resultColumns = $columns;

        return $this;
    }

    /**
     * Returns the search table.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Sets search table.
     *
     * @param string $table Table where search should be performed
     */
    public function setTable(string $table): SearchDatabase
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Returns the joined table.
     */
    public function getJoinedTable(): string
    {
        if ($this->joinedTable === '' || $this->joinedTable === '0') {
            return '';
        }
        return ' LEFT JOIN ' . $this->joinedTable . ' ON ';
    }

    /**
     * Sets joined search table.
     *
     * @param string $joinedTable Joined table where search should be performed
     */
    public function setJoinedTable(string $joinedTable = ''): SearchDatabase
    {
        $this->joinedTable = $joinedTable;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the columns for the join.
     */
    public function getJoinedColumns(): string
    {
        $joinedColumns = '';

        foreach ($this->joinedColumns as $joinedColumn) {
            $joinedColumns .= $joinedColumn . ' AND ';
        }

        return Strings::substr($joinedColumns, 0, -4);
    }

    /**
     * Sets the part of the SQL query with the columns for the join.
     *
     * @param string[] $joinedColumns Array of columns
     */
    public function setJoinedColumns(array $joinedColumns): SearchDatabase
    {
        $this->joinedColumns = $joinedColumns;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the matching columns.
     */
    public function getMatchingColumns(): string
    {
        return implode(', ', $this->matchingColumns);
    }

    /**
     * Sets the part of the SQL query with the matching columns.
     *
     * @param string[] $matchingColumns Array of columns
     */
    public function setMatchingColumns(array $matchingColumns): SearchDatabase
    {
        $this->matchingColumns = $matchingColumns;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the conditions.
     */
    public function getConditions(): string
    {
        $conditions = '';

        foreach ($this->conditions as $column => $value) {
            if (is_array($value)) {
                $conditions .= ' AND ' . $column . ' IN (' . implode(', ', $value) . ')';
            } else {
                $conditions .= ' AND ' . $column . ' = ' . $value;
            }
        }

        return $conditions;
    }

    /**
     * Sets the part of the SQL query with the conditions.
     *
     * @param array<string, array<int>|string> $conditions Array of columns
     */
    public function setConditions(array $conditions): SearchDatabase
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Creates the part for the WHERE clause.
     *
     * @param string $searchTerm Search term
     */
    public function getMatchClause(string $searchTerm = ''): string
    {
        $keys = Strings::preg_split("/\s+/", $searchTerm);
        $numKeys = is_countable($keys) ? count($keys) : 0;
        $numMatch = count($this->matchingColumns);
        $where = '';

        for ($i = 0; $i < $numKeys; ++$i) {
            if (strlen($where) != 0) {
                $where .= ' OR';
            }

            $where .= ' (';
            for ($j = 0; $j < $numMatch; ++$j) {
                if ($j != 0) {
                    $where .= ' OR ';
                }

                $where = sprintf(
                    "%s%s LIKE '%%%s%%'",
                    $where,
                    $this->matchingColumns[$j],
                    $this->configuration->getDb()->escape($keys[$i])
                );
            }

            $where .= ')';
        }

        return $where;
    }

    /**
     * Disables relevance support if we don't need it even if the database
     * supports it.
     */
    public function disableRelevance(): void
    {
        $this->relevanceSupport = false;
    }
}
