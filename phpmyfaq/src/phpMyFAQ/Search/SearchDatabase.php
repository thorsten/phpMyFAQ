<?php

/**
 * phpMyFAQ database based search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     *
     * @var string
     */
    protected $table = '';

    /**
     * Joined searching database table.
     *
     * @var string
     */
    protected $joinedTable = '';

    /**
     * Columns for the result set.
     *
     * @var string[]
     */
    protected $resultColumns = [];

    /**
     * Columns for the joined table.
     *
     * @var string[]
     */
    protected $joinedColumns = [];

    /**
     * Matching columns for the search.
     *
     * @var string[]
     */
    protected $matchingColumns = [];

    /**
     * Conditions columns with their values.
     *
     * @var array<string, array<int>|string>
     */
    protected $conditions = [];

    /**
     * Flag if database supports search relevance.
     *
     * @var bool
     */
    protected $relevanceSupport = false;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * Prepares the search and executes it.
     *
     * @param string $searchTerm Search term
     * @return mixed
     */
    public function search(string $searchTerm)
    {
        $query = sprintf(
            '
            SELECT
                %s
            FROM 
                %s %s %s
            WHERE
                %s = %d',
            $this->getResultColumns(),
            $this->getTable(),
            $this->getJoinedTable(),
            $this->getJoinedColumns(),
            $this->getMatchingColumns(),
            $searchTerm
        );

        $this->resultSet = $this->config->getDb()->query($query);

        return $this->resultSet;
    }

    /**
     * Returns the part of the SQL query with the columns for the result set.
     *
     * @return string
     */
    public function getResultColumns(): string
    {
        $resultColumns = '';

        foreach ($this->resultColumns as $column) {
            if (empty($resultColumns)) {
                $resultColumns = $column;
            } else {
                $resultColumns .= ', ' . $column;
            }
        }

        return $resultColumns;
    }

    /**
     * Sets the part of the SQL query with the columns for the result set.
     *
     * @param string[] $columns Array of columns
     *
     * @return SearchDatabase
     */
    public function setResultColumns(array $columns): SearchDatabase
    {
        $this->resultColumns = $columns;

        return $this;
    }

    /**
     * Returns the search table.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Sets search table.
     *
     * @param string $table Table where search should be performed
     *
     * @return SearchDatabase
     */
    public function setTable($table): SearchDatabase
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Returns the joined table.
     *
     * @return string
     */
    public function getJoinedTable(): string
    {
        if (empty($this->joinedTable)) {
            return '';
        } else {
            return ' LEFT JOIN ' . $this->joinedTable . ' ON ';
        }
    }

    /**
     * Sets joined search table.
     *
     * @param string $joinedTable Joined table where search should be performed
     *
     * @return SearchDatabase
     */
    public function setJoinedTable($joinedTable = ''): SearchDatabase
    {
        $this->joinedTable = $joinedTable;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the columns for the join.
     *
     * @return string
     */
    public function getJoinedColumns(): string
    {
        $joinedColumns = '';

        foreach ($this->joinedColumns as $column) {
            $joinedColumns .= $column . ' AND ';
        }

        return Strings::substr($joinedColumns, 0, -4);
    }

    /**
     * Sets the part of the SQL query with the columns for the join.
     *
     * @param string[] $joinedColumns Array of columns
     *
     * @return SearchDatabase
     */
    public function setJoinedColumns(array $joinedColumns): SearchDatabase
    {
        $this->joinedColumns = $joinedColumns;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the matching columns.
     *
     * @return string
     */
    public function getMatchingColumns(): string
    {
        return implode(', ', $this->matchingColumns);
    }

    /**
     * Sets the part of the SQL query with the matching columns.
     *
     * @param string[] $matchingColumns Array of columns
     *
     * @return SearchDatabase
     */
    public function setMatchingColumns(array $matchingColumns): SearchDatabase
    {
        $this->matchingColumns = $matchingColumns;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the conditions.
     *
     * @return string
     */
    public function getConditions(): string
    {
        $conditions = '';

        if (count($this->conditions)) {
            foreach ($this->conditions as $column => $value) {
                if (is_array($value)) {
                    $conditions .= ' AND ' . $column . ' IN (' . implode(', ', $value) . ')';
                } else {
                    $conditions .= ' AND ' . $column . ' = ' . $value;
                }
            }
        }

        return $conditions;
    }

    /**
     * Sets the part of the SQL query with the conditions.
     *
     * @param array<string, array<int>|string> $conditions Array of columns
     *
     * @return SearchDatabase
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
     *
     * @return string
     */
    public function getMatchClause($searchTerm = ''): string
    {
        $keys = Strings::preg_split("/\s+/", $searchTerm);
        $numKeys = count($keys);
        $numMatch = count($this->matchingColumns);
        $where = '';

        for ($i = 0; $i < $numKeys; ++$i) {
            if (strlen($where) != 0) {
                $where = $where . ' OR';
            }
            $where = $where . ' (';
            for ($j = 0; $j < $numMatch; ++$j) {
                if ($j != 0) {
                    $where = $where . ' OR ';
                }
                $where = sprintf(
                    "%s%s LIKE '%%%s%%'",
                    $where,
                    $this->matchingColumns[$j],
                    $this->config->getDb()->escape($keys[$i])
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
