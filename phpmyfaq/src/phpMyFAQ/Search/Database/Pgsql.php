<?php

/**
 * phpMyFAQ PostgreSQL search classes.
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

namespace phpMyFAQ\Search\Database;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Search\SearchDatabase;

/**
 * Class Pgsql
 *
 * @package phpMyFAQ\Search\Database
 */
class Pgsql extends SearchDatabase implements DatabaseInterface
{
    /**
     * Constructor.
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);
        $this->relevanceSupport = true;
    }

    /**
     * Prepares the search and executes it.
     *
     * @param  string $searchTerm Search term
     * @throws Exception
     */
    #[\Override]
    public function search(string $searchTerm): mixed
    {
        if (is_numeric($searchTerm) && $this->configuration->get('search.searchForSolutionId')) {
            parent::search($searchTerm);
        } else {
            $enableRelevance = $this->configuration->get('search.enableRelevance');

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
                    ? ", plainto_tsquery('" . $this->configuration->getDb()->escape($searchTerm) . "') query "
                    : '',
                $this->getMatchingColumns(),
                $this->configuration->getDb()->escape($searchTerm),
                $this->getConditions(),
                $orderBy
            );

            $this->resultSet = $this->configuration->getDb()->query($query);
        }

        return $this->resultSet;
    }

    /**
     * Add the matching columns into the columns for the resultset.
     */
    public function getMatchingColumnsAsResult(): string
    {
        $resultColumns = '';
        $config = $this->configuration->get('search.relevance');
        $list = explode(',', (string) $config);

        // Set weight
        $weights = ['A', 'B', 'C', 'D'];
        $weight = [];
        foreach ($list as $columnName) {
            $weight[$columnName] = array_shift($weights);
        }

        foreach ($this->matchingColumns as $matchingColumn) {
            $columnName = substr(strstr($matchingColumn, '.'), 1);

            if (isset($weight[$columnName])) {
                $column = sprintf(
                    "TS_RANK_CD(SETWEIGHT(TO_TSVECTOR(COALESCE(%s, '')), '%s'), query) AS relevance_%s",
                    $matchingColumn,
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
     * Weight calculates the order depend on the search.relevance order
     */
    public function getMatchingOrder(): string
    {
        $list = explode(',', (string) $this->configuration->get('search.relevance'));
        $order = '';

        foreach ($list as $field) {
            $string = sprintf(
                'relevance_%s DESC',
                $field
            );
            if ($order === '' || $order === '0') {
                $order .= $string;
            } else {
                $order .= ', ' . $string;
            }
        }

        return $order;
    }

    /**
     * Returns the part of the SQL query with the matching columns.
     */
    #[\Override]
    public function getMatchingColumns(): string
    {
        $enableRelevance = $this->configuration->get('search.enableRelevance');

        if ($enableRelevance) {
            $matchColumns = '';

            foreach ($this->matchingColumns as $matchingColumn) {
                $match = sprintf("to_tsvector(coalesce(%s,''))", $matchingColumn);
                if ($matchColumns === '' || $matchColumns === '0') {
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
