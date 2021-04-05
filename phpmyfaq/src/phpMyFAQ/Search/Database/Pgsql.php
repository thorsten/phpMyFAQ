<?php

/**
 * phpMyFAQ PostgreSQL search classes.
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

namespace phpMyFAQ\Search\Database;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Search\SearchDatabase;
use stdClass;

/**
 * Class Pgsql
 *
 * @package phpMyFAQ\Search\Database
 */
class Pgsql extends SearchDatabase
{
    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
        $this->relevanceSupport = true;
    }

    /**
     * Prepares the search and executes it.
     *
     * @param  string $searchTerm Search term
     * @throws Exception
     * @return mixed
     */
    public function search(string $searchTerm)
    {
        if (is_numeric($searchTerm) && $this->config->get('search.searchForSolutionId')) {
            parent::search($searchTerm);
        } else {
            $enableRelevance = $this->config->get('search.enableRelevance');

            $columns = $this->getResultColumns();
            $columns .= ($enableRelevance) ? $this->getMatchingColumnsAsResult() : '';
            $orderBy = ($enableRelevance) ? 'ORDER BY ' . $this->getMatchingOrder() : '';

            $query = sprintf(
                "
                SELECT
                    %s
                FROM
                    %s %s %s %s
                WHERE
                    (%s) ILIKE ('%%%s%%')
                    %s
                    %s",
                $columns,
                $this->getTable(),
                $this->getJoinedTable(),
                $this->getJoinedColumns(),
                ($enableRelevance)
                    ? ", plainto_tsquery('" . $this->config->getDb()->escape($searchTerm) . "') query "
                    : '',
                $this->getMatchingColumns(),
                $this->config->getDb()->escape($searchTerm),
                $this->getConditions(),
                $orderBy
            );

            $this->resultSet = $this->config->getDb()->query($query);
        }

        return $this->resultSet;
    }

    /**
     * Add the matching columns into the columns for the resultset.
     *
     * @return string
     */
    public function getMatchingColumnsAsResult(): string
    {
        $resultColumns = '';
        $config = $this->config->get('search.relevance');
        $list = explode(',', $config);

        // Set weight
        $weights = array('A', 'B', 'C', 'D');
        $weight = [];
        foreach ($list as $columnName) {
            $weight[$columnName] = array_shift($weights);
        }

        foreach ($this->matchingColumns as $matchColumn) {
            $columnName = substr(strstr($matchColumn, '.'), 1);

            if (isset($weight[$columnName])) {
                $column = sprintf(
                    "TS_RANK_CD(SETWEIGHT(TO_TSVECTOR(COALESCE(%s, '')), '%s'), query) AS relevance_%s",
                    $matchColumn,
                    $weight[$columnName],
                    $columnName
                );

                $resultColumns .= ', ' . $column;
            }
        }

        return $resultColumns;
    }

    /**
     * Returns the part of the SQL query with the order by.
     *
     * The order is calculate by weight depend on the search.relevance order
     *
     * @return string
     */
    public function getMatchingOrder(): string
    {
        $list = explode(',', $this->config->get('search.relevance'));
        $order = '';

        foreach ($list as $field) {
            $string = sprintf(
                'relevance_%s DESC',
                $field
            );
            if (empty($order)) {
                $order .= $string;
            } else {
                $order .= ', ' . $string;
            }
        }

        return $order;
    }

    /**
     * Returns the part of the SQL query with the matching columns.
     *
     * @return string
     */
    public function getMatchingColumns(): string
    {
        $enableRelevance = $this->config->get('search.enableRelevance');

        if ($enableRelevance) {
            $matchColumns = '';

            foreach ($this->matchingColumns as $matchColumn) {
                $match = sprintf("to_tsvector(coalesce(%s,''))", $matchColumn);
                if (empty($matchColumns)) {
                    $matchColumns .= '(' . $match;
                } else {
                    $matchColumns .= ' || ' . $match;
                }
            }

            // Add the ILIKE since the FULLTEXT looks for the exact phrase only
            $matchColumns .= ') @@ query) OR (' . implode(" || ' ' || ", $this->matchingColumns);
        } else {
            $matchColumns = implode(" || ' ' || ", $this->matchingColumns);
        }

        return $matchColumns;
    }
}
