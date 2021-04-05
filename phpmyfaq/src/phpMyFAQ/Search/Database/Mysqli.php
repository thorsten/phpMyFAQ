<?php

/**
 * phpMyFAQ MySQL (ext/mysqli) search classes.
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

use mysqli_result;
use phpMyFAQ\Configuration;
use phpMyFAQ\Search\Exception;
use phpMyFAQ\Search\SearchDatabase;
use stdClass;

/**
 * Class Mysqli
 *
 * @package phpMyFAQ\Search\Database
 */
class Mysqli extends SearchDatabase
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
     * @throws \Exception
     * @return mixed
     */
    public function search(string $searchTerm)
    {
        if (is_numeric($searchTerm) && $this->config->get('search.searchForSolutionId')) {
            parent::search($searchTerm);
        } else {
            $relevance = $this->config->get('search.enableRelevance');
            $columns = $this->getResultColumns();

            if ($this->relevanceSupport && $relevance) {
                $columns .= $this->getMatchingColumnsAsResult($searchTerm);
                $orderBy = 'ORDER BY ' . $this->getMatchingOrder() . ' DESC';
            } else {
                $orderBy = '';
            }

            $chars = [
                "\xe2\x80\x98",
                "\xe2\x80\x99",
                "\xe2\x80\x9c",
                "\xe2\x80\x9d",
                "\xe2\x80\x93",
                "\xe2\x80\x94",
                "\xe2\x80\xa6",
            ];
            $replace = ["'", "'", '"', '"', '-', '--', '...'];
            $searchTerm = str_replace($chars, $replace, $searchTerm);

            $query = sprintf(
                "
                SELECT
                    %s
                FROM 
                    %s %s %s
                WHERE
                    MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)
                    %s
                    %s",
                $columns,
                $this->getTable(),
                $this->getJoinedTable(),
                $this->getJoinedColumns(),
                $this->getMatchingColumns(),
                $this->config->getDb()->escape($searchTerm),
                $this->getConditions(),
                $orderBy
            );

            $this->resultSet = $this->config->getDb()->query($query);

            // Fallback for searches with less than three characters
            if (false !== $this->resultSet && 0 === $this->config->getDb()->numRows($this->resultSet)) {
                $query = sprintf(
                    '
                    SELECT
                        %s
                    FROM 
                        %s %s %s
                    WHERE
                        %s
                        %s',
                    $this->getResultColumns(),
                    $this->getTable(),
                    $this->getJoinedTable(),
                    $this->getJoinedColumns(),
                    $this->getMatchClause($searchTerm),
                    $this->getConditions()
                );

                $this->resultSet = $this->config->getDb()->query($query);
            }
        }

        return $this->resultSet;
    }

    /**
     * Add the matching columns into the columns for the result set.
     *
     * @param string $searchTerm
     *
     * @return string
     */
    public function getMatchingColumnsAsResult(string $searchTerm): string
    {
        $resultColumns = '';

        foreach ($this->matchingColumns as $matchColumn) {
            $column = sprintf(
                "MATCH (%s) AGAINST ('*%s*' IN BOOLEAN MODE) AS relevance_%s",
                $matchColumn,
                $this->config->getDb()->escape($searchTerm),
                substr(strstr($matchColumn, '.'), 1)
            );

            $resultColumns .= ', ' . $column;
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
        $count = count($list);
        $order = '';

        foreach ($list as $field) {
            $string = sprintf(
                '(relevance_%s * %d)',
                $field,
                $count
            );
            if (empty($order)) {
                $order .= $string;
            } else {
                $order .= ' + ' . $string;
            }
            --$count;
        }

        return '(' . $order . ')';
    }
}
