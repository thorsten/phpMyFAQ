<?php

/**
 * phpMyFAQ database based search classes.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-06-06
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search_Database.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-06-06
 */
class PMF_Search_Database extends PMF_Search_Abstract implements PMF_Search_Interface
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
     * Columns for the resultset.
     *
     * @var array
     */
    protected $resultColumns = [];

    /**
     * Columns for the joined table.
     *
     * @var array
     */
    protected $joinedColumns = [];

    /**
     * Matching columns for the search.
     *
     * @var array
     */
    protected $matchingColumns = [];

    /**
     * Conditions columns with their values.
     *
     * @var array
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
     * @param PMF_Configuration
     */
    public function __construct(PMF_Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * Prepares the search and executes it.
     *
     * @param string $searchTerm Search term
     *
     * @throws PMF_Search_Exception
     */
    public function search($searchTerm)
    {
        $query = sprintf('
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

        $this->resultSet = $this->_config->getDb()->query($query);
    }

    /**
     * Sets search table.
     *
     * @param string $table Table where search should be performed
     *
     * @return PMF_Search_Database
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Returns the search table.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Sets joined search table.
     *
     * @param string $joinedTable Joined table where search should be performed
     *
     * @return PMF_Search_Database
     */
    public function setJoinedTable($joinedTable = '')
    {
        $this->joinedTable = $joinedTable;

        return $this;
    }

    /**
     * Returns the joined table.
     *
     * @return string
     */
    public function getJoinedTable()
    {
        if (empty($this->joinedTable)) {
            return '';
        } else {
            return ' LEFT JOIN '.$this->joinedTable.' ON ';
        }
    }

    /**
     * Sets the part of the SQL query with the columns for the result set.
     *
     * @param array $columns Array of columns
     *
     * @return PMF_Search_Database
     */
    public function setResultColumns(Array $columns)
    {
        $this->resultColumns = $columns;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the columns for the result set.
     *
     * @return string
     */
    public function getResultColumns()
    {
        $resultColumns = '';

        foreach ($this->resultColumns as $column) {
            if (empty($resultColumns)) {
                $resultColumns = $column;
            } else {
                $resultColumns .= ', '.$column;
            }
        }

        return $resultColumns;
    }

    /**
     * Sets the part of the SQL query with the columns for the join.
     *
     * @param array $joinedColumns Array of columns
     *
     * @return PMF_Search_Database
     */
    public function setJoinedColumns(Array $joinedColumns)
    {
        $this->joinedColumns = $joinedColumns;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the columns for the join.
     *
     * @return string
     */
    public function getJoinedColumns()
    {
        $joinedColumns = '';

        foreach ($this->joinedColumns as $column) {
            $joinedColumns .= $column.' AND ';
        }

        return PMF_String::substr($joinedColumns, 0, -4);
    }

    /**
     * Sets the part of the SQL query with the matching columns.
     *
     * @param array $matchingColumns Array of columns
     *
     * @return PMF_Search_Database
     */
    public function setMatchingColumns(Array $matchingColumns)
    {
        $this->matchingColumns = $matchingColumns;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the matching columns.
     *
     * @return string
     */
    public function getMatchingColumns()
    {
        return implode(', ', $this->matchingColumns);
    }

    /**
     * Sets the part of the SQL query with the conditions.
     *
     * @param array $conditions Array of columns
     *
     * @return PMF_Search_Database
     */
    public function setConditions(Array $conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Returns the part of the SQL query with the conditions.
     *
     * @return string
     */
    public function getConditions()
    {
        $conditions = '';

        if (count($this->conditions)) {
            foreach ($this->conditions as $column => $value) {
                if (is_array($value)) {
                    $conditions .= ' AND '.$column.' IN ('.implode(', ', $value).')';
                } else {
                    $conditions .= ' AND '.$column.' = '.$value;
                }
            }
        }

        return $conditions;
    }

    /**
     * Creates the part for the WHERE clause.
     *
     * @param string $searchTerm Search term
     *
     * @return string
     */
    public function getMatchClause($searchTerm = '')
    {
        $keys = PMF_String::preg_split("/\s+/", $searchTerm);
        $numKeys = count($keys);
        $numMatch = count($this->matchingColumns);
        $where = '';

        for ($i = 0; $i < $numKeys; ++$i) {
            if (strlen($where) != 0) {
                $where = $where.' OR';
            }
            $where = $where.' (';
            for ($j = 0; $j < $numMatch; ++$j) {
                if ($j != 0) {
                    $where = $where.' OR ';
                }
                $where = sprintf("%s%s LIKE '%%%s%%'",
                    $where,
                    $this->matchingColumns[$j],
                    $this->_config->getDb()->escape($keys[$i]));
            }
            $where .= ')';
        }

        return $where;
    }

    /**
     * Disables relevance support if we don't need it even if the database
     * supports it.
     */
    public function disableRelevance()
    {
        $this->relevanceSupport = false;
    }
}
